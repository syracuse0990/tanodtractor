<?php

namespace App\Models;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jimi extends Model
{
    use HasFactory;

    public function getToken()
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));

        $data = [
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'expires_in' => '7200',
            'format' => 'json',
            'method' => 'jimi.oauth.token.get',
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'user_id' => 'Admin_LAPC',
            'user_pwd_md5' => 'f0f560f1b1be459ffc8ce6979fe7979d',
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772app_key' . $data['app_key'] . 'expires_in' . $data['expires_in'] . 'format' . $data['format'] . 'method' . $data['method'] . 'sign_method' . $data['sign_method'] . 'timestamp' . $data['timestamp'] . 'user_id' . $data['user_id'] . 'user_pwd_md5' . $data['user_pwd_md5'] . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $response_data = $res->getBody()->getContents();
            $response = json_decode($response_data, true);
            if ($response_data) {
                $date = date('Y-m-d H:i:s');
                $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
                $user = User::where('role_id', User::ROLE_ADMIN)->first();
                if ($user) {
                    $user->api_access_token = $response['result']['accessToken'];
                    $user->api_token_time = $gmt_date;
                    $user->save();
                }
                return $response;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getDeviceList()
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);

        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'method' => 'jimi.user.device.list',
            'sign_method' => 'md5',
            'target' => 'Admin_LAPC',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] . 'format' . $data['format'] . 'method' . $data['method'] . 'sign_method' . $data['sign_method'] . 'target' . $data['target'] . 'timestamp' . $data['timestamp']  .  'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $response_data = $res->getBody()->getContents();
            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                $this->getToken(true);
                $this->getDeviceList();
            }
            return $e->getMessage();
        }
    }

    public function getDeviceDetail($imei)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);

        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'imei' => $imei,
            'method' => 'jimi.track.device.detail',
            'sign_method' => 'md5',
            'target' => 'Admin_LAPC',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] . 'format' . $data['format'] . 'imei' . $data['imei'] . 'method' . $data['method'] . 'sign_method' . $data['sign_method'] . 'target' . $data['target']  . 'timestamp' . $data['timestamp']  .  'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $response_data = $res->getBody()->getContents();
            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                $this->getToken(true);
                $this->getDeviceDetail($imei);
            }
            $response['code'] = $e->getCode();
            $response['message'] = $e->getMessage();
            return $response;
        }
    }

    //Get the latest location for all devices under an account
    public function getDeviceLocationList()
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);

        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'map_type' => 'GOOGLE',
            'method' => 'jimi.user.device.location.list',
            'sign_method' => 'md5',
            'target' => 'Admin_LAPC',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] . 'format' . $data['format'] . 'map_type' . $data['map_type'] . 'method' . $data['method'] . 'sign_method' . $data['sign_method'] . 'target' . $data['target'] . 'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();
            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                $this->getToken(true);
                $this->getDeviceLocationList();
            }
            return $e->getMessage();
        }
    }

    //Get current location
    public function getDeviceLocation($imeis)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'imeis' => implode(',', $imeis),
            'map_type' => 'GOOGLE',
            'method' => 'jimi.device.location.get',
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] . 'format' . $data['format'] . 'imeis' . $data['imeis']  . 'map_type' . $data['map_type'] . 'method' . $data['method'] . 'sign_method' . $data['sign_method'] . 'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();
            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                $this->getToken(true);
                $this->getDeviceLocation($imeis);
            }
            $this->getDeviceLocation($imeis);
            return $e->getMessage();
        }
    }

    public function getSharingLocationUrl($imei)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'imei' => $imei,
            'method' => 'jimi.device.location.URL.share',
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] . 'format' . $data['format'] . 'imei' . $data['imei']  .  'method' . $data['method'] . 'sign_method' . $data['sign_method'] . 'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                $this->getToken(true);
                $this->getSharingLocationUrl($imei);
            }
            return $e->getMessage();
        }
    }

    public function updateExpiration($imei_list, $new_expiration)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'imei_list' => implode(',', $imei_list),
            'method' => 'jimi.user.device.expiration.update',
            'new_expiration' => $new_expiration,
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] . 'format' . $data['format'] . 'imei_list' . $data['imei_list']  .  'method' . $data['method'] .  'new_expiration' . $data['new_expiration'] . 'sign_method' . $data['sign_method'] . 'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                $this->getToken(true);
                $this->updateExpiration($imei_list, $new_expiration);
            }
            return $e->getMessage();
        }
    }
    // $page_size = 20, $start_row = 1
    public function getDeviceMilage($imeis, $begin_time, $end_time)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'begin_time' => $begin_time,
            'end_time' => $end_time,
            'format' => 'json',
            'imeis' => implode(',', $imeis),
            'method' => 'jimi.device.track.mileage',
            // 'page_size' => $page_size,
            'sign_method' => 'md5',
            // 'start_row' => $start_row,
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] .  'begin_time' . $data['begin_time'] . 'end_time' . $data['end_time'] . 'format' . $data['format'] . 'imeis' . $data['imeis']  .  'method' . $data['method'] . 'sign_method' . $data['sign_method'] . 'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                $this->getToken(true);
                $this->getDeviceMilage($imeis, $begin_time, $end_time);
            }
            return $e->getMessage();
        }
    }

    // Get historical data
    // Get device track data of not more than 2 days, within 3 months. 
    public function getDeviceTrackData($imei, $begin_time, $end_time)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'begin_time' => $begin_time,
            'end_time' => $end_time,
            'format' => 'json',
            'imei' => $imei,
            'method' => 'jimi.device.track.list',
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] .  'begin_time' . $data['begin_time'] . 'end_time' . $data['end_time'] . 'format' . $data['format'] . 'imei' . $data['imei']  .  'method' . $data['method'] .  'sign_method' . $data['sign_method'] .  'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;
        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
             if ($e->getCode() == 401) {
                $this->getToken(true);
                $this->getDeviceTrackData($imei, $begin_time, $end_time);
            }
            if ($e->getCode() == 500) {
                return [
                    'code' => 500,
                    'message' => $e->getMessage()
                ];
            }
            return $e->getMessage();
        }
    }

    public function updateVehicleInfo($imei)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'imei' => $imei,
            'method' => 'jimi.open.device.update',
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] .  'format' . $data['format'] . 'imei' . $data['imei']  .  'method' . $data['method'] .  'sign_method' . $data['sign_method'] .  'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                $this->getToken(true);
                $this->updateVehicleInfo($imei);
            }
            return $e->getMessage();
        }
    }

    public function getDeviceMediaUrl($imei, $camera, $media_type, $page_no = 0, $page_size = 10)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'camera' => $camera,
            'format' => 'json',
            'imei' => $imei,
            'media_type' => $media_type,
            'method' => 'jimi.device.media.URL',
            'page_no' => $page_no,
            'page_size' => $page_size,
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] .  'camera' . $data['camera'] . 'format' . $data['format'] . 'imei' . $data['imei']  . 'media_type' . $data['media_type'] . 'method' . $data['method'] .  'page_no' . $data['page_no'] . 'page_size' . $data['page_size'] . 'sign_method' . $data['sign_method'] .  'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                $this->getToken(true);
                $this->getDeviceMediaUrl($imei, $camera, $media_type, $page_no = 0, $page_size = 10);
            }
            return $e->getMessage();
        }
    }

    /**"code": 1002,
     * "message": "illegal device",
     * "result": null,
     * "data": null
     */

    public function getDeviceLiveUrl($imei)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'imei' => $imei,
            'method' => 'jimi.device.live.page.url',
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] .  'format' . $data['format'] . 'imei' . $data['imei']  .  'method' . $data['method'] .  'sign_method' . $data['sign_method'] .  'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * "code": 1001,
     * "message": "Parameter validation is not legal",
     * "result": null,
     * "data": null
     */
    public function getLbsAddress($imei)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }

        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'imei' => $imei,
            'method' => 'jimi.lbs.address.get',
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] .  'format' . $data['format'] . 'imei' . $data['imei']  .  'method' . $data['method'] .  'sign_method' . $data['sign_method'] .  'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function createGeoFence($imei, $fence_name, $lat, $lng,  $radius, $alarm_type = 'out', $report_mode = '1', $alarm_switch = 'ON', $map_type = 'GOOGLE', $zoom_level = '10')
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'alarm_switch' => $alarm_switch,
            'alarm_type' => $alarm_type,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'fence_name' => $fence_name,
            'format' => 'json',
            'imei' => $imei,
            'lat' => $lat,
            'lng' => $lng,
            'map_type' => $map_type,
            'method' => 'jimi.open.device.fence.create',
            'radius' => $radius,
            'report_mode' => $report_mode,
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '1.0',
            'zoom_level' => $zoom_level
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772' . 'access_token' . $data['access_token'] . 'alarm_switch' . $data['alarm_switch'] . 'alarm_type' . $data['alarm_type'] . 'app_key' . $data['app_key'] . 'fence_name' . $data['fence_name'] . 'format' . $data['format'] . 'imei' . $data['imei']  . 'lat' . $data['lat']  . 'lng' . $data['lng']  . 'map_type' . $data['map_type'] . 'method' . $data['method'] . 'radius' . $data['radius'] . 'report_mode' . $data['report_mode'] . 'sign_method' . $data['sign_method'] . 'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'zoom_level' . $data['zoom_level'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                $this->getToken(true);
                $this->createGeoFence($imei, $fence_name, $lat, $lng,  $radius, $zoom_level, $alarm_type = 'out', $report_mode = '1', $alarm_switch = 'ON', $map_type = 'GOOGLE');
            }
            $errorData['code'] = $e->getCode();
            $errorData['message'] = $e->getMessage();
            return $errorData;
        }
    }

    public function deleteGeoFence($imei, $instruct_no)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'imei' => $imei,
            'instruct_no' => $instruct_no,
            'method' => 'jimi.open.device.fence.delete',
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772' . 'access_token' . $data['access_token'] . 'app_key' . $data['app_key'] . 'format' . $data['format'] . 'imei' . $data['imei']  . 'instruct_no' . $data['instruct_no']  . 'method' . $data['method'] . 'sign_method' . $data['sign_method'] . 'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                $this->getToken(true);
                $this->deleteGeoFence($imei, $instruct_no);
            }
            $errorData['code'] = $e->getCode();
            $errorData['message'] = $e->getMessage();
            return $errorData;
        }
    }

    public function getCommandList($imei)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'imei' => $imei,
            'method' => 'jimi.open.instruction.list',
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] .  'format' . $data['format'] . 'imei' . $data['imei']  .  'method' . $data['method'] .  'sign_method' . $data['sign_method'] .  'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function commandExecResult($imei)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'imei' => $imei,
            'method' => 'jimi.open.instruction.result',
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] .  'format' . $data['format'] . 'imei' . $data['imei']  .  'method' . $data['method'] .  'sign_method' . $data['sign_method'] .  'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAlarmList($imei, $alertTypeId = null, $begin_time = null, $end_time = null)
    {
        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }
        $data = [
            'access_token' => $user->api_access_token,
            // 'alertTypeId' => $alertTypeId,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            // 'begin_time' => $begin_time,
            // 'end_time' => $end_time,
            'format' => 'json',
            'imei' => $imei,
            'method' => 'jimi.open.instruction.result',
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] . 'format' . $data['format'] . 'imei' . $data['imei']  .  'method' . $data['method'] .  'sign_method' . $data['sign_method'] .  'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    /**
     * command_id, command_orderContent
     * 106, 'SPEED,ON,{0},{1},{2}#', ['SPEED'], true/false
     * $is_cover is set to false if you do not want to cover offline commands.
     */
    public function sendCommand($imei, $inst_id, $inst_template, $params, $is_cover = true)
    {

        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }

        $inst_param_data = [
            'inst_id' => $inst_id,
            'inst_template' => $inst_template,
            'params' => $params,
            'is_cover' => $is_cover
        ];
        $inst_param_json = json_encode($inst_param_data);

        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'imei' => $imei,
            'inst_param_json' => $inst_param_json,
            'method' => 'jimi.open.instruction.send',
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '1.0',
        ];
        $sign = md5('ca41d3577eb2494f9030ace810cf7772access_token' . $data['access_token'] . 'app_key' . $data['app_key'] . 'format' . $data['format'] . 'imei' . $data['imei']  . 'inst_param_json' . $data['inst_param_json']  . 'method' . $data['method'] .  'sign_method' . $data['sign_method'] .  'timestamp' . $data['timestamp']  . 'v' . $data['v'] . 'ca41d3577eb2494f9030ace810cf7772');
        $data['sign'] = $sign;

        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function alarm($imei)
    {

        $date = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
        $user = User::where('role_id', User::ROLE_ADMIN)->first();
        $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);
        if (empty($user->api_access_token) || $diff >= 2) {
            $this->getToken();
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
        }

        $data = [
            'access_token' => $user->api_access_token,
            'app_key' => '8FB345B8693CCD0033FB45E2E5335788339A22A4105B6558',
            'format' => 'json',
            'imei' => $imei,
            'method' => 'jimi.push.device.alarm',
            'sign_method' => 'md5',
            'timestamp' => $gmt_date,
            'v' => '0.9',
        ];


        $url = "https://hk-open.tracksolidpro.com/route/rest";
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        try {
            $client = new Client();
            $res = $client->request('POST', $url, [
                'headers' => $headers,
                'form_params' => $data
            ]);
            $status_code = $res->getStatusCode();
            $response_data = $res->getBody()->getContents();

            $response = json_decode($response_data, true);
            return $response;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
