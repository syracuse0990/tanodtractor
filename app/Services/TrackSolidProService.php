<?php

namespace App\Services;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class TrackSolidProService
{
    protected $client;
    protected $baseUrl;
    protected $appKey;
    protected $appSecret;
    protected $target;

    public function __construct()
    {
        $this->client = new Client();
        $this->baseUrl = 'https://hk-open.tracksolidpro.com/route/rest';
        $this->appKey = '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558';
        $this->appSecret = 'ca41d3577eb2494f9030ace810cf7772';
        $this->target = 'Admin_LAPC';
    }

    protected function getToken($forceRefresh = false)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));

        $data = [
            'app_key' => $this->appKey,
            'format' => 'json',
            'method' => 'jimi.oauth.token.get',
            'sign_method' => 'md5',
            'target' => $this->target,
            'timestamp' => $gmt_date,
            'user_id' => $this->target,
            'user_pwd_md5' => md5('admin@123'),

            'v' => '1.0',
        ];

        $sign = $this->generateSignature($data);
        $data['sign'] = $sign;

        try {
            $response = $this->client->post($this->baseUrl, [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'form_params' => $data
            ]);

            $response_data = $response->getBody()->getContents();
            $response = json_decode($response_data, true);

            if (isset($response['result']['accessToken'])) {
                $user = User::where('role_id', User::ROLE_ADMIN)->first();
                $user->api_access_token = $response['result']['accessToken'];
                $user->api_token_time = $gmt_date;
                $user->save();
                return $response['result']['accessToken'];
            }

            throw new \Exception('Failed to get access token');
        } catch (\Exception $e) {
            Log::error('TrackSolidPro Auth Error: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function generateSignature($params)
    {
        ksort($params);
        $stringToSign = $this->appSecret;

        foreach ($params as $key => $value) {
            if ($value !== null) {
                $stringToSign .= $key . $value;
            }
        }

        $stringToSign .= $this->appSecret;
        return strtoupper(md5($stringToSign));
    }

    protected function checkAndRefreshToken()
    {
        $user = User::where('role_id', User::ROLE_ADMIN)->first();

        if (!$user || !$user->api_access_token) {
            return $this->getToken();
        }

        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);

        if ($diff >= 2) {
            return $this->getToken();
        }

        return $user->api_access_token;
    }

    public function makeApiRequest($method, $params = [])
    {
        $access_token = $this->checkAndRefreshToken();
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));

        $commonParams = [
            'access_token' => $access_token,
            'app_key' => $this->appKey,
            'format' => 'json',
            'method' => $method,
            'sign_method' => 'md5',
            'target' => $this->target,
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];

        $requestParams = array_merge($commonParams, $params);
        $requestParams['sign'] = $this->generateSignature($requestParams);

        try {
            $response = $this->client->post($this->baseUrl, [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'form_params' => $requestParams
            ]);

            $response_data = $response->getBody()->getContents();
            $data = json_decode($response_data, true);

            if (isset($data['code']) && $data['code'] == 401) {
                // Token might be expired, try refreshing
                $this->getToken(true);
                return $this->makeApiRequest($method, $params);
            }

            if (isset($data['code']) && $data['code'] != 0) {
                throw new \Exception($data['message'] ?? 'API request failed');
            }

            return $data['result'] ?? $data['data'] ?? $data;
        } catch (\Exception $e) {
            Log::error('TrackSolidPro API Error: ' . $e->getMessage());

            if ($e->getCode() == 401) {
                $this->getToken(true);
                return $this->makeApiRequest($method, $params);
            }

            throw $e;
        }
    }

    public function getDevices()
    {
        return $this->makeApiRequest('jimi.user.device.list');
    }

    public function getDeviceDetails($imei)
    {
        return $this->makeApiRequest('jimi.track.device.detail', [
            'imei' => $imei
        ]);
    }

    public function getDeviceLocations($imeis)
    {
        if (is_array($imeis)) {
            $imeis = implode(',', $imeis);
        }

        return $this->makeApiRequest('jimi.device.location.get', [
            'imeis' => $imeis
        ]);
    }

    public function getDeviceStats()
    {
        $devices = $this->getDevices();
        $stats = [
            'totalDevices' => count($devices),
            'activeDevices' => 0,
            'inactiveDevices' => 0,
            'expiredDevices' => 0,
            'expiringSoonDevices' => 0
        ];

        $now = time();
        $oneMonthFromNow = strtotime('+1 month');

        foreach ($devices as $device) {
            $expiration = $device['expiration'] ?? null;

            if (empty($device['activationTime'])) {
                $stats['inactiveDevices']++;
            } elseif ($expiration && strtotime($expiration) < $now) {
                $stats['expiredDevices']++;
            } elseif ($expiration && strtotime($expiration) > $now) {
                $stats['activeDevices']++;

                if (strtotime($expiration) < $oneMonthFromNow) {
                    $stats['expiringSoonDevices']++;
                }
            }
        }

        return $stats;
    }

    public function getDeviceMileage($imeis, $startTime, $endTime)
    {
        if (is_array($imeis)) {
            $imeis = implode(',', $imeis);
        }

        return $this->makeApiRequest('jimi.device.track.mileage', [
            'imeis' => $imeis,
            'begin_time' => $startTime,
            'end_time' => $endTime
        ]);
    }

    public function getDeviceTrips($imeis, $startTime, $endTime)
    {
        if (is_array($imeis)) {
            $imeis = implode(',', $imeis);
        }

        return $this->makeApiRequest('jimi.open.platform.report.trips', [
            'imeis' => $imeis,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'type' => 'list'
        ]);
    }
}
