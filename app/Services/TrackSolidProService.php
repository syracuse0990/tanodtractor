<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TrackSolidProService
{
    protected $client;
    protected $baseUrl;
    protected $appKey;
    protected $appSecret;
    protected $account;
    protected $password;
    protected $token;

    public function __construct()
    {
        $this->client = new Client();
        $this->baseUrl = config('tracksolidpro.api_url');
        $this->appKey = config('tracksolidpro.app_key');
        $this->appSecret = config('tracksolidpro.app_secret');
        $this->account = config('tracksolidpro.account');
        $this->password = md5(config('tracksolidpro.password'));

        $this->authenticate();
    }

    protected function authenticate()
    {
        $this->token = Cache::remember('tracksolidpro_token', now()->addHours(1), function () {
            $params = [
                'method' => 'jimi.oauth.token.get',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'app_key' => $this->appKey,
                'user_id' => $this->account,
                'user_pwd_md5' => $this->password,
                'expires_in' => 7200,
                'format' => 'json',
                'v' => '1.0',
                'sign_method' => 'md5'
            ];

            $params['sign'] = $this->generateSignature($params);

            try {
                $response = $this->client->post($this->baseUrl, [
                    'form_params' => $params
                ]);

                $data = json_decode($response->getBody(), true);
                return $data['result']['accessToken'] ?? null;
            } catch (\Exception $e) {
                Log::error('TrackSolidPro Auth Error: ' . $e->getMessage());
                return null;
            }
        });
    }

    protected function generateSignature($params)
    {
        unset($params['sign']);

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

    protected function makeApiRequest($method, $params = [])
    {
        if (!$this->token) {
            throw new \Exception('TrackSolidPro authentication failed.');
        }

        $commonParams = [
            'method' => $method,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'app_key' => $this->appKey,
            'access_token' => $this->token,
            'format' => 'json',
            'v' => '1.0',
            'sign_method' => 'md5'
        ];

        $requestParams = array_merge($commonParams, $params);
        $requestParams['sign'] = $this->generateSignature($requestParams);

        try {
            $response = $this->client->post($this->baseUrl, [
                'form_params' => $requestParams
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['code'] != 0) {
                throw new \Exception($data['message'] ?? 'API request failed');
            }

            return $data['result'] ?? $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('TrackSolidPro API Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getDevices($account = null)
    {
        return $this->makeApiRequest('jimi.user.device.list', [
            'target' => $account ?? $this->account
        ]);
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

    public function getDeviceStats($account = null)
    {
        $devices = $this->getDevices($account);
        $stats = [
            'totalDevices' => count($devices),
            'activeDevices' => 0,
            'inactiveDevices' => 0,
            'expiredDevices' => 0,
            'expiringSoonDevices' => 0
        ];

        $now = now();
        $oneMonthFromNow = $now->copy()->addMonth();

        foreach ($devices as $device) {
            $expiration = $device['expiration'] ?? null;

            if (empty($device['activationTime'])) {
                $stats['inactiveDevices']++;
            } elseif ($expiration && strtotime($expiration) < $now->timestamp) {
                $stats['expiredDevices']++;
            } elseif ($expiration && strtotime($expiration) > $now->timestamp) {
                $stats['activeDevices']++;

                if (strtotime($expiration) < $oneMonthFromNow->timestamp) {
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
            'account' => $this->account,
            'imeis' => $imeis,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'type' => 'list'
        ]);
    }
}
