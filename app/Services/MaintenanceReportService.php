<?php

namespace App\Services;

use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
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

    /** Max concurrent API requests */
    private const CONCURRENCY = 5;

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
            set_time_limit(300);

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
     * @return array  IMEI => ['hours' => float, 'distance' => float]
     */
    private function fetchMileageTotals(array $deviceMap): array
    {
        // Initialize: distance from hardware odometer, hours will be summed from trip records
        $totals = [];
        foreach ($deviceMap as $imei => $device) {
            $totals[$imei] = [
                'hours' => 0,
                'distance' => round((float)($device['currentMileage'] ?? 0), 2),
            ];
        }

        // Build 365-day sliding windows from 2023-01-01 to now
        $windows = $this->buildDateWindows();

        $imeis = array_keys($deviceMap);

        foreach ($windows as $wi => $window) {
            Log::info("MaintenanceReportService: Window " . ($wi + 1) . "/" . count($windows) . ": {$window['start']} to {$window['end']}");
            $this->fetchMileageWindowConcurrent($imeis, $window['start'], $window['end'], $totals);
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
     * Fetch hours data for all IMEIs for a single date window using Guzzle Pool.
     * 
     * Each IMEI is queried individually (1 IMEI per request) to avoid the
     * 100-result cap truncation. Concurrency is limited to 5 to prevent 429 errors.
     *
     * @param array  $imeis     List of IMEIs
     * @param string $startDate Window start
     * @param string $endDate   Window end
     * @param array  &$totals   Running totals (modified in place)
     */
    private function fetchMileageWindowConcurrent(array $imeis, string $startDate, string $endDate, array &$totals): void
    {
        $this->ensureValidToken();

        $requests = function () use ($imeis, $startDate, $endDate) {
            foreach ($imeis as $imei) {
                $data = array_merge([
                    'access_token' => $this->accessToken,
                    'app_key' => self::APP_KEY,
                    'format' => 'json',
                    'method' => 'jimi.device.track.mileage',
                    'sign_method' => 'md5',
                    'timestamp' => $this->getGmtDate(),
                    'v' => '1.0',
                ], [
                    'imeis' => $imei,
                    'begin_time' => $startDate,
                    'end_time' => $endDate,
                ]);

                $data['sign'] = $this->generateSignature($data);

                yield $imei => new Request('POST', self::BASE_URL, [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ], http_build_query($data));
            }
        };

        $pool = new Pool($this->client, $requests(), [
            'concurrency' => self::CONCURRENCY,
            'fulfilled' => function ($response, $imei) use (&$totals) {
                $body = json_decode($response->getBody()->getContents(), true);

                if (isset($body['result']) && is_array($body['result'])) {
                    $hours = 0;
                    foreach ($body['result'] as $trip) {
                        $hours += ((int)($trip['runTimeSecond'] ?? 0)) / 3600;
                    }
                    $totals[$imei]['hours'] = round($totals[$imei]['hours'] + $hours, 2);
                }
            },
            'rejected' => function ($reason, $imei) {
                Log::warning("MaintenanceReportService: Request failed for IMEI {$imei}: " . $reason->getMessage());
            },
        ]);

        $pool->promise()->wait();
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
                'needs_pms'      => $totalHours >= 100,
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
