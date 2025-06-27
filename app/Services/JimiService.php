<?php

namespace App\Services;

use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class JimiService
{
    private const API_SECRET = 'ca41d3577eb2494f9030ace810cf7772';
    private const APP_KEY = '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558';
    private const BASE_URL = 'https://hk-open.tracksolidpro.com/route/rest';
    private const TARGET_USER = 'Admin_LAPC';
    private const USER_PWD_MD5 = 'f0f560f1b1be459ffc8ce6979fe7979d';
    private const TOKEN_EXPIRY_HOURS = 2;

    private Client $client;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Get authentication token and store it in the admin user
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
            Log::error('Failed to get Jimi token: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get list of devices
     */
    public function getDeviceList(): array
    {
        return $this->authenticatedRequest('jimi.user.device.list', [
            'target' => self::TARGET_USER,
        ]);
    }

    /**
     * Get device details by IMEI
     */
    public function getDeviceDetail(string $imei): array
    {
        return $this->authenticatedRequest('jimi.track.device.detail', [
            'imei' => $imei,
            'target' => self::TARGET_USER,
        ]);
    }

    /**
     * Get device location list
     */
    public function getDeviceLocationList(): array
    {
        return $this->authenticatedRequest('jimi.user.device.location.list', [
            'map_type' => 'GOOGLE',
            'target' => self::TARGET_USER,
        ]);
    }

    /**
     * Get current location for specific devices
     */
    public function getDeviceLocation(array $imeis): array
    {
        return $this->authenticatedRequest('jimi.device.location.get', [
            'imeis' => implode(',', $imeis),
            'map_type' => 'GOOGLE',
        ]);
    }

    /**
     * Get sharing location URL for a device
     */
    public function getSharingLocationUrl(string $imei): array
    {
        return $this->authenticatedRequest('jimi.device.location.URL.share', [
            'imei' => $imei,
        ]);
    }

    /**
     * Update device expiration
     */
    public function updateExpiration(array $imeiList, string $newExpiration): array
    {
        return $this->authenticatedRequest('jimi.user.device.expiration.update', [
            'imei_list' => implode(',', $imeiList),
            'new_expiration' => $newExpiration,
        ]);
    }

    /**
     * Get device mileage data
     */
    public function getDeviceMileage(array $imeis, string $beginTime, string $endTime): array
    {
        return $this->authenticatedRequest('jimi.device.track.mileage', [
            'imeis' => implode(',', $imeis),
            'begin_time' => $beginTime,
            'end_time' => $endTime,
        ]);
    }

    /**
     * Get device track data
     */
    public function getDeviceTrackData(string $imei, string $beginTime, string $endTime): array
    {
        return $this->authenticatedRequest('jimi.device.track.list', [
            'imei' => $imei,
            'begin_time' => $beginTime,
            'end_time' => $endTime,
        ]);
    }

    /**
     * Update vehicle info
     */
    public function updateVehicleInfo(string $imei): array
    {
        return $this->authenticatedRequest('jimi.open.device.update', [
            'imei' => $imei,
        ]);
    }

    /**
     * Get device media URL
     */
    public function getDeviceMediaUrl(string $imei, string $camera, string $mediaType, int $pageNo = 0, int $pageSize = 10): array
    {
        return $this->authenticatedRequest('jimi.device.media.URL', [
            'imei' => $imei,
            'camera' => $camera,
            'media_type' => $mediaType,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * Get device live URL
     */
    public function getDeviceLiveUrl(string $imei): array
    {
        return $this->authenticatedRequest('jimi.device.live.page.url', [
            'imei' => $imei,
        ]);
    }

    /**
     * Get LBS address
     */
    public function getLbsAddress(string $imei): array
    {
        return $this->authenticatedRequest('jimi.lbs.address.get', [
            'imei' => $imei,
        ]);
    }

    /**
     * Create geo fence
     */
    public function createGeoFence(
        string $imei,
        string $fenceName,
        float $lat,
        float $lng,
        int $radius,
        string $alarmType = 'out',
        string $reportMode = '1',
        string $alarmSwitch = 'ON',
        string $mapType = 'GOOGLE',
        string $zoomLevel = '10'
    ): array {
        return $this->authenticatedRequest('jimi.open.device.fence.create', [
            'imei' => $imei,
            'fence_name' => $fenceName,
            'lat' => $lat,
            'lng' => $lng,
            'radius' => $radius,
            'alarm_type' => $alarmType,
            'report_mode' => $reportMode,
            'alarm_switch' => $alarmSwitch,
            'map_type' => $mapType,
            'zoom_level' => $zoomLevel,
        ]);
    }

    /**
     * Delete geo fence
     */
    public function deleteGeoFence(string $imei, string $instructNo): array
    {
        return $this->authenticatedRequest('jimi.open.device.fence.delete', [
            'imei' => $imei,
            'instruct_no' => $instructNo,
        ]);
    }

    /**
     * Get command list
     */
    public function getCommandList(string $imei): array
    {
        return $this->authenticatedRequest('jimi.open.instruction.list', [
            'imei' => $imei,
        ]);
    }

    /**
     * Get command execution result
     */
    public function commandExecResult(string $imei): array
    {
        return $this->authenticatedRequest('jimi.open.instruction.result', [
            'imei' => $imei,
        ]);
    }

    /**
     * Get alarm list
     */
    public function getAlarmList(string $imei, ?string $alertTypeId = null, ?string $beginTime = null, ?string $endTime = null): array
    {
        $params = ['imei' => $imei];

        if ($alertTypeId) {
            $params['alertTypeId'] = $alertTypeId;
        }
        if ($beginTime) {
            $params['begin_time'] = $beginTime;
        }
        if ($endTime) {
            $params['end_time'] = $endTime;
        }

        return $this->authenticatedRequest('jimi.open.instruction.result', $params);
    }

    /**
     * Send command to device
     */
    public function sendCommand(string $imei, int $instId, string $instTemplate, array $params, bool $isCover = true): array
    {
        $instParamData = [
            'inst_id' => $instId,
            'inst_template' => $instTemplate,
            'params' => $params,
            'is_cover' => $isCover,
        ];

        return $this->authenticatedRequest('jimi.open.instruction.send', [
            'imei' => $imei,
            'inst_param_json' => json_encode($instParamData),
        ]);
    }

    /**
     * Trigger alarm
     */
    public function alarm(string $imei): array
    {
        return $this->authenticatedRequest('jimi.push.device.alarm', [
            'imei' => $imei,
        ], '0.9');
    }

    /**
     * Make an authenticated request to Jimi API
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
                // Token might be expired, try refreshing it once
                $this->getToken();
                return $this->authenticatedRequest($method, $params, $version);
            }

            Log::error('Jimi API request failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ensure we have a valid access token
     */
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

    /**
     * Store the token in the admin user
     */
    private function storeToken(string $token, string $timestamp): void
    {
        $user = User::where('role_id', User::ROLE_ADMIN)->first();

        if ($user) {
            $user->api_access_token = $token;
            $user->api_token_time = $timestamp;
            $user->save();
        }
    }

    /**
     * Generate signature for request
     */
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


    /**
     * Make the actual HTTP request
     */
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

    /**
     * Get current GMT date
     */
    private function getGmtDate(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    public function getTagDeviceLocation(array $imeis): array
{
    return $this->authenticatedRequest('jimi.device.location.getTagMsg', [
        'imeis' => implode(',', $imeis)
    ]);
}
}
