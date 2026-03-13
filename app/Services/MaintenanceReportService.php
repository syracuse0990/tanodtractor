<?php

namespace App\Services;

use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Optimized service for Maintenance Reports.
 * 
 * Uses Jimi TrackSolidPro API endpoints specifically chosen
 * to minimize API calls and maximize response speed:
 * 
 * - jimi.user.device.location.list  (3.1) → ALL devices + status/hbTime/currentMileage in 1 call
 * - jimi.device.track.mileage       (3.4) → Trip records with runTimeSecond for hours calculation
 * 
 * Note: jimi.open.platform.report.trips (9.2) was evaluated but ALL devices have
 *       openFlag=false (trip reporting disabled on platform), so it cannot provide data.
 * 
 * Note: The mileage API returns null for date ranges > ~18 months. We use a sliding
 *       window of 365 days to stay within the API limit.
 * 
 * API Docs: https://tracksolidprodocs.jimicloud.com/integration/integration.html
 */
class MaintenanceReportService
{
    private const API_SECRET = 'ca41d3577eb2494f9030ace810cf7772';
    private const APP_KEY = '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558';
    private const BASE_URL = 'https://hk-open.tracksolidpro.com/route/rest';
    private const TARGET_USER = 'Admin_LAPC';
    private const USER_PWD_MD5 = 'f0f560f1b1be459ffc8ce6979fe7979d';
    private const TOKEN_EXPIRY_HOURS = 2;

    /** IMEIs per batch — keep small to avoid the 100-record-per-call API cap */
    private const BATCH_SIZE = 10;

    /** Cache duration in minutes */
    private const CACHE_MINUTES = 30;

    private Client $client;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 60,
            'connect_timeout' => 10,
            'verify' => false,
        ]);
    }

    /**
     * Get the full cached device location map (IMEI => raw API data).
     *
     * Returns all fields from jimi.user.device.location.list including
     * lat, lng, status, accStatus, speed, hbTime, posType, gpsNum, etc.
     * Cached for 10 minutes. Callers can use this to render map markers
     * without making additional Jimi API calls.
     *
     * @param  bool  $forceRefresh  Ignore cache
     * @return array<string, array>  IMEI => device data from API
     */
    public function getDeviceLocationMap(bool $forceRefresh = false): array
    {
        $cacheKey = 'maintenance_device_location_map';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return $this->fetchAllDevicesWithLocation();
        });
    }

    /**
     * Get a mapping of IMEI => group name from jimi.user.device.list.
     *
     * This API returns deviceGroupId and deviceGroup per device, which the
     * location API (jimi.user.device.location.list) does NOT include.
     * Cached for 30 minutes (groups rarely change).
     *
     * @param  bool  $forceRefresh  Ignore cache
     * @return array{groups: array<string, array{id: string, name: string, devices: string[]}>, imeiGroup: array<string, string>}
     */
    public function getDeviceGroupMap(bool $forceRefresh = false): array
    {
        $cacheKey = 'jimi_device_group_map';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(30), function () {
            return $this->fetchDeviceGroupMapping();
        });
    }

    /**
     * Get device status counts (total, online, offline) from the cached device
     * location API call.  Accepts an optional list of IMEIs to filter on so the
     * caller can scope results to a group / sub-admin.
     *
     * Cached separately from the full maintenance data with a shorter TTL
     * (10 min) because online/offline status changes more frequently.
     *
     * @param  string[]  $filterImeis  Only count these IMEIs (empty = all)
     * @param  bool      $forceRefresh Ignore cache
     * @return array{total: int, online: int, offline: int}
     */
    public function getDeviceStatusCounts(array $filterImeis = [], bool $forceRefresh = false): array
    {
        /** @var array<string, array> $deviceMap  IMEI => device data from API */
        $deviceMap = $this->getDeviceLocationMap($forceRefresh);

        $online  = 0;
        $offline = 0;

        // When filterImeis is provided, only iterate over those IMEIs
        $imeis = !empty($filterImeis)
            ? $filterImeis
            : array_keys($deviceMap);

        foreach ($imeis as $imei) {
            if (!isset($deviceMap[$imei])) {
                continue;           // IMEI not returned by API – skip
            }

            $status = (int) ($deviceMap[$imei]['status'] ?? 0);

            if ($status === 1) {
                $online++;
            } else {
                $offline++;
            }
        }

        return [
            'total'   => $online + $offline,
            'online'  => $online,
            'offline' => $offline,
        ];
    }

    /**
     * Count devices that need PMS (currentMileage >= 1000 km) from the
     * cached device-location map (always available, cheap single API call).
     *
     * @param  string[]  $filterImeis  Only count these IMEIs (empty = all)
     * @param  bool      $cacheOnly    Kept for signature compatibility (unused)
     * @return int
     */
    public function getPmsCount(array $filterImeis = [], bool $cacheOnly = false): int
    {
        $deviceMap = $this->getDeviceLocationMap();

        $count = 0;
        $filterSet = !empty($filterImeis) ? array_flip($filterImeis) : null;

        foreach ($deviceMap as $imei => $device) {
            if ($filterSet !== null && !isset($filterSet[$imei])) {
                continue;
            }
            $mileage = (float) ($device['currentMileage'] ?? 0);
            if ($mileage >= 1000) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get all maintenance report data: devices with total hours, distance, status.
     * Results are sorted by total_hours DESC and cached for 30 minutes.
     *
     * @param bool $forceRefresh  Force re-fetch from API (ignore cache)
     * @return array  Sorted array of device maintenance data
     */
    public function getMaintenanceData(bool $forceRefresh = false): array
    {
        $cacheKey = 'maintenance_reports_data';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_MINUTES), function () {
            set_time_limit(0); // Unlimited — API calls for ~120 devices across multiple windows take time

            // Step 1: Get ALL devices with status, hbTime, currentMileage in ONE call
            $deviceMap = $this->fetchAllDevicesWithLocation();

            if (empty($deviceMap)) {
                return [];
            }

            // Step 2: Get hours & distance data using the most efficient API available
            $mileageTotals = $this->fetchMileageTotals($deviceMap);

            // Step 3: Build and sort final data
            return $this->buildSortedResult($deviceMap, $mileageTotals);
        });
    }

    /**
     * Step 1: Fetch all devices with location data in a single API call.
     * Uses jimi.user.device.location.list which returns status, hbTime, currentMileage.
     *
     * @return array  Map of IMEI => device data
     */
    private function fetchAllDevicesWithLocation(): array
    {
        try {
            $response = $this->authenticatedRequest('jimi.user.device.location.list', [
                'target' => self::TARGET_USER,
                'map_type' => 'GOOGLE',
            ]);

            $allDevices = $response['result'] ?? [];
            $deviceMap = [];

            foreach ($allDevices as $device) {
                $imei = $device['imei'] ?? null;
                if ($imei) {
                    $deviceMap[$imei] = $device;
                }
            }

            Log::info("MaintenanceReportService: Fetched " . count($deviceMap) . " devices in 1 API call");
            return $deviceMap;
        } catch (Exception $e) {
            Log::error("MaintenanceReportService: Failed to fetch device list: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch device-to-group mapping from jimi.user.device.list.
     *
     * Returns an array with two keys:
     *   - 'groups': groupName => { id, name, devices[] }
     *   - 'imeiGroup': IMEI => groupName
     */
    private function fetchDeviceGroupMapping(): array
    {
        try {
            $response = $this->authenticatedRequest('jimi.user.device.list', [
                'target' => self::TARGET_USER,
            ]);

            $devices = $response['result'] ?? [];
            $groups = [];
            $imeiGroup = [];

            foreach ($devices as $device) {
                $imei      = $device['imei']          ?? null;
                $groupName = $device['deviceGroup']   ?? 'Default Group';
                $groupId   = $device['deviceGroupId'] ?? '';

                if (!$imei) {
                    continue;
                }

                $imeiGroup[$imei] = $groupName;

                if (!isset($groups[$groupName])) {
                    $groups[$groupName] = [
                        'id'      => $groupId,
                        'name'    => $groupName,
                        'devices' => [],
                    ];
                }
                $groups[$groupName]['devices'][] = $imei;
            }

            // Sort groups alphabetically, but keep "Default group" first
            uksort($groups, function ($a, $b) {
                $aDefault = stripos($a, 'default') !== false;
                $bDefault = stripos($b, 'default') !== false;
                if ($aDefault && !$bDefault) return -1;
                if (!$aDefault && $bDefault) return 1;
                return strcasecmp($a, $b);
            });

            Log::info("MaintenanceReportService: Fetched group mapping — " . count($groups) . " groups, " . count($imeiGroup) . " devices");
            return [
                'groups'    => $groups,
                'imeiGroup' => $imeiGroup,
            ];
        } catch (Exception $e) {
            Log::error("MaintenanceReportService: Failed to fetch device group mapping: " . $e->getMessage());
            return [
                'groups'    => [],
                'imeiGroup' => [],
            ];
        }
    }

    /**
     * Step 2: Fetch hours & distance totals for all devices.
     * 
     * Uses jimi.device.track.mileage API with multiple 365-day sliding windows
     * to cover the full history (API returns null for ranges > ~18 months).
     * Hours come from trip records (result[] → runTimeSecond).
     * Distance uses the hardware odometer (currentMileage from device location API).
     *
     * IMPORTANT: The API caps result[] at 100 records per call. We query each
     * IMEI individually (concurrency = 5) to avoid truncation.
     *
     * @param array $deviceMap  IMEI => device data
     * @return array  IMEI => ['hours' => float, 'distance' => float, 'odometer' => float]
     */
    private function fetchMileageTotals(array $deviceMap): array
    {
        // Initialize: distance from hardware odometer, hours will be summed from trip records
        // odometer tracks the highest endMileage from trip records (cumulative hardware odometer)
        $totals = [];
        foreach ($deviceMap as $imei => $device) {
            $totals[$imei] = [
                'hours' => 0,
                'distance' => round((float)($device['currentMileage'] ?? 0), 2),
                'odometer' => 0,
            ];
        }

        // Build 365-day sliding windows from 2023-01-01 to now
        $windows = $this->buildDateWindows();

        // Small batches of 10 IMEIs to avoid result[] truncation (API cap = 100 records)
        $batches = array_chunk(array_keys($deviceMap), self::BATCH_SIZE);
        $batchCount = count($batches);
        $windowCount = count($windows);

        Log::info("MaintenanceReportService: {$batchCount} batches × {$windowCount} windows = " . ($batchCount * $windowCount) . " API calls for " . count($deviceMap) . " devices");

        $rateLimited = false;

        foreach ($windows as $wi => $window) {
            if ($rateLimited) break;

            Log::info("MaintenanceReportService: Window " . ($wi + 1) . "/{$windowCount}: {$window['start']} to {$window['end']}");

            foreach ($batches as $bi => $batch) {
                $status = $this->fetchMileageBatch($batch, $window['start'], $window['end'], $totals);

                if ($status === 'rate_limited') {
                    Log::error("MaintenanceReportService: Daily API quota exceeded — aborting. Hours data will be incomplete.");
                    $rateLimited = true;
                    break;
                }

                // Rate-limit protection: 0.5s delay between batches
                if ($bi < $batchCount - 1) {
                    usleep(500000);
                }
            }
        }

        return $totals;
    }

    /**
     * Build sliding date windows of max 365 days each, from 2023-01-01 to now.
     *
     * @return array  List of ['start' => string, 'end' => string]
     */
    private function buildDateWindows(): array
    {
        $windows = [];
        $origin = \Carbon\Carbon::create(2023, 1, 1, 0, 0, 0);
        $now = now();

        $cursor = $origin->copy();
        while ($cursor->lt($now)) {
            $windowEnd = $cursor->copy()->addDays(365);
            if ($windowEnd->gt($now)) {
                $windowEnd = $now->copy();
            }

            $windows[] = [
                'start' => $cursor->format('Y-m-d H:i:s'),
                'end' => $windowEnd->format('Y-m-d H:i:s'),
            ];

            $cursor = $windowEnd->copy();
        }

        return $windows;
    }

    /**
     * Fetch hours data for a batch of IMEIs using the Mileage API.
     * 
     * Uses batches of 10 IMEIs so the 100-result cap has enough room for all trips.
     * Hours = sum of trip runTimeSecond fields from result[] (seconds → hours)
     * 
     * Returns 'ok', 'rate_limited', or 'error'.
     */
    private function fetchMileageBatch(array $imeis, string $startDate, string $endDate, array &$totals): string
    {
        try {
            $response = $this->authenticatedRequest('jimi.device.track.mileage', [
                'imeis' => implode(',', $imeis),
                'begin_time' => $startDate,
                'end_time' => $endDate,
            ]);

            $code = $response['code'] ?? 0;

            // Immediately signal rate limit — caller will abort all remaining batches
            if ($code == 1006) {
                return 'rate_limited';
            }

            if ($code != 0) {
                Log::warning("MaintenanceReportService: API code {$code}: " . ($response['message'] ?? 'unknown'));
            }

            $resultCount = is_array($response['result'] ?? null) ? count($response['result']) : 0;

            if (isset($response['result']) && is_array($response['result'])) {
                $hoursByImei = [];
                foreach ($response['result'] as $trip) {
                    $imei = $trip['imei'] ?? null;
                    if ($imei && isset($totals[$imei])) {
                        $hoursByImei[$imei] = ($hoursByImei[$imei] ?? 0) + (((int)($trip['runTimeSecond'] ?? 0)) / 3600);

                        // Track cumulative hardware odometer (endMileage in meters → km)
                        $endMileage = (float) ($trip['endMileage'] ?? 0) / 1000;
                        if ($endMileage > $totals[$imei]['odometer']) {
                            $totals[$imei]['odometer'] = round($endMileage, 2);
                        }
                    }
                }
                foreach ($hoursByImei as $imei => $hours) {
                    $totals[$imei]['hours'] = round($totals[$imei]['hours'] + $hours, 2);
                }
            }

            if ($resultCount >= 100) {
                Log::warning("MaintenanceReportService: Batch hit 100-result cap — some trips may be truncated.");
            }

            return 'ok';

        } catch (Exception $e) {
            Log::error("MaintenanceReportService: Mileage batch failed: " . $e->getMessage());
            return 'error';
        }
    }

    /**
     * Step 3: Build the final sorted result array.
     */
    private function buildSortedResult(array $deviceMap, array $mileageTotals): array
    {
        $data = [];

        foreach ($deviceMap as $imei => $device) {
            $totalHours = $mileageTotals[$imei]['hours'] ?? 0;
            $totalDistance = $mileageTotals[$imei]['distance'] ?? 0;

            $data[] = [
                'device_name'    => $device['deviceName'] ?? $imei,
                'imei'           => $imei,
                'vehicleNumber'  => $device['vehicleNumber'] ?? null,
                'total_hours'    => $totalHours,
                'total_distance' => $totalDistance,
                'odometer_distance' => $mileageTotals[$imei]['odometer'] ?? 0,
                'needs_pms'      => $totalHours >= 5 && $totalHours > 0,
                'status'         => $device['status'] ?? '0',
                'last_active'    => $device['hbTime'] ?? null,
            ];
        }

        // Sort by total hours descending (biggest first)
        usort($data, function ($a, $b) {
            return $b['total_hours'] <=> $a['total_hours'];
        });

        return $data;
    }

    // ─────────────────────────────────────────────────────────────
    //  Authentication & HTTP (self-contained, does not touch JimiService)
    // ─────────────────────────────────────────────────────────────

    /**
     * Get authentication token and store it in the admin user.
     */
    public function getToken(): array
    {
        try {
            $gmtDate = $this->getGmtDate();

            $data = [
                'app_key' => self::APP_KEY,
                'expires_in' => '7200',
                'format' => 'json',
                'method' => 'jimi.oauth.token.get',
                'sign_method' => 'md5',
                'timestamp' => $gmtDate,
                'user_id' => self::TARGET_USER,
                'user_pwd_md5' => self::USER_PWD_MD5,
                'v' => '1.0',
            ];

            $data['sign'] = $this->generateSignature($data);
            $response = $this->makeRequest($data);

            if (isset($response['result']['accessToken'])) {
                $this->storeToken($response['result']['accessToken'], $gmtDate);
                $this->accessToken = $response['result']['accessToken'];
            }

            return $response;
        } catch (Exception $e) {
            Log::error('MaintenanceReportService: Failed to get token: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Make an authenticated request to Jimi API.
     */
    private function authenticatedRequest(string $method, array $params = [], string $version = '1.0'): array
    {
        try {
            $this->ensureValidToken();

            $data = array_merge([
                'access_token' => $this->accessToken,
                'app_key' => self::APP_KEY,
                'format' => 'json',
                'method' => $method,
                'sign_method' => 'md5',
                'timestamp' => $this->getGmtDate(),
                'v' => $version,
            ], $params);

            $data['sign'] = $this->generateSignature($data);

            return $this->makeRequest($data);
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                $this->getToken();
                return $this->authenticatedRequest($method, $params, $version);
            }

            Log::error("MaintenanceReportService: API request [{$method}] failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function ensureValidToken(): void
    {
        if ($this->accessToken) {
            return;
        }

        $user = User::where('role_id', User::ROLE_ADMIN)->first();

        if (!$user || !$user->api_access_token) {
            $this->getToken();
            return;
        }

        $gmtDate = $this->getGmtDate();
        $diff = round((strtotime($gmtDate) - strtotime($user->api_token_time)) / 3600, 1);

        if ($diff >= self::TOKEN_EXPIRY_HOURS) {
            $this->getToken();
        } else {
            $this->accessToken = $user->api_access_token;
        }
    }

    private function storeToken(string $token, string $timestamp): void
    {
        $user = User::where('role_id', User::ROLE_ADMIN)->first();

        if ($user) {
            $user->api_access_token = $token;
            $user->api_token_time = $timestamp;
            $user->save();
        }
    }

    private function generateSignature(array $data): string
    {
        ksort($data);

        $signString = self::API_SECRET;
        foreach ($data as $key => $value) {
            $signString .= $key . $value;
        }
        $signString .= self::API_SECRET;

        return md5($signString);
    }

    private function makeRequest(array $data): array
    {
        $response = $this->client->request('POST', self::BASE_URL, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => $data,
        ]);

        $responseData = $response->getBody()->getContents();
        return json_decode($responseData, true) ?? [];
    }

    private function getGmtDate(): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}
