<?php

namespace App\Services;

use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class TrackSolidProService
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

    /* Authentication Methods */

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
     * Refresh access token
     */
    public function refreshToken(string $refreshToken): array
    {
        return $this->authenticatedRequest('jimi.oauth.token.refresh', [
            'refresh_token' => $refreshToken,
            'expires_in' => '7200',
        ]);
    }

    /* User Management Methods */

    /**
     * List all sub-accounts
     */
    public function listSubAccounts(string $targetAccount): array
    {
        return $this->authenticatedRequest('jimi.user.child.list', [
            'target' => $targetAccount,
        ]);
    }

    /**
     * Create sub-account
     */
    public function createSubAccount(
        string $accountId,
        string $nickName,
        int $accountType,
        string $passwordMd5,
        string $email,
        string $permissions,
        ?string $superAccount = null,
        ?string $telephone = null,
        ?string $contactPerson = null,
        ?string $companyName = null
    ): array {
        $params = [
            'account_id' => $accountId,
            'nick_name' => $nickName,
            'account_type' => $accountType,
            'password' => $passwordMd5,
            'email' => $email,
            'permissions' => $permissions,
        ];

        if ($superAccount) {
            $params['super_account'] = $superAccount;
        }
        if ($telephone) {
            $params['telephone'] = $telephone;
        }
        if ($contactPerson) {
            $params['contact_person'] = $contactPerson;
        }
        if ($companyName) {
            $params['company_name'] = $companyName;
        }

        return $this->authenticatedRequest('jimi.user.child.create', $params);
    }

    /**
     * Remove sub-account
     */
    public function removeSubAccount(string $accountId, ?string $superAccount = null): array
    {
        $params = ['account_id' => $accountId];
        if ($superAccount) {
            $params['super_account'] = $superAccount;
        }
        return $this->authenticatedRequest('jimi.user.child.del', $params);
    }

    /**
     * Move account
     */
    public function moveAccount(string $account, string $targetAccount): array
    {
        return $this->authenticatedRequest('jimi.user.child.move', [
            'account' => $account,
            'target_account' => $targetAccount,
        ]);
    }

    /**
     * Edit user information
     */
    public function editUserInfo(
        string $editAccount,
        string $nickName,
        string $email,
        string $permissions,
        ?string $telephone = null,
        ?string $contactPerson = null,
        ?string $companyName = null
    ): array {
        $params = [
            'edit_account' => $editAccount,
            'nick_name' => $nickName,
            'email' => $email,
            'permissions' => $permissions,
        ];

        if ($telephone) {
            $params['telephone'] = $telephone;
        }
        if ($contactPerson) {
            $params['contact_person'] = $contactPerson;
        }
        if ($companyName) {
            $params['company_name'] = $companyName;
        }

        return $this->authenticatedRequest('jimi.user.child.update', $params);
    }

    /* Device Management Methods */

    /**
     * Get list of devices
     */
    public function getDeviceList(string $targetAccount = self::TARGET_USER): array
    {
        return $this->authenticatedRequest('jimi.user.device.list', [
            'target' => $targetAccount,
        ]);
    }

    /**
     * Get device details by IMEI
     */
    public function getDeviceDetail(string $imei): array
    {
        return $this->authenticatedRequest('jimi.track.device.detail', [
            'imei' => $imei,
        ]);
    }

    /**
     * Get device location list
     */
    public function getDeviceLocationList(string $targetAccount = self::TARGET_USER): array
    {
        return $this->authenticatedRequest('jimi.user.device.location.list', [
            'map_type' => 'GOOGLE',
            'target' => $targetAccount,
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
     * Get the location of TAG device
     */
    public function getTagDeviceLocation(array $imeis): array
    {
        return $this->authenticatedRequest('jimi.device.location.getTagMsg', [
            'imeis' => implode(',', $imeis),
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
    public function updateVehicleInfo(
        string $imei,
        ?string $deviceName = null,
        ?string $vehicleName = null,
        ?string $vehicleIcon = null,
        ?string $vehicleNumber = null,
        ?string $vehicleModels = null,
        ?string $driverName = null,
        ?string $driverPhone = null,
        ?string $deviceStatus = null,
        ?string $sim = null,
        ?string $remarks = null,
        ?string $oilWear = null,
        ?string $deviceGroupId = null
    ): array {
        $params = ['imei' => $imei];

        if ($deviceName) $params['device_name'] = $deviceName;
        if ($vehicleName) $params['vehicle_name'] = $vehicleName;
        if ($vehicleIcon) $params['vehicle_icon'] = $vehicleIcon;
        if ($vehicleNumber) $params['vehicle_number'] = $vehicleNumber;
        if ($vehicleModels) $params['vehicle_models'] = $vehicleModels;
        if ($driverName) $params['driver_name'] = $driverName;
        if ($driverPhone) $params['driver_phone'] = $driverPhone;
        if ($deviceStatus) $params['device_status'] = $deviceStatus;
        if ($sim) $params['sim'] = $sim;
        if ($remarks) $params['remarks'] = $remarks;
        if ($oilWear) $params['oilWear'] = $oilWear;
        if ($deviceGroupId) $params['deviceGroupId'] = $deviceGroupId;

        return $this->authenticatedRequest('jimi.open.device.update', $params);
    }

    /**
     * Move devices between accounts
     */
    public function moveDevices(string $srcAccount, string $destAccount, array $imeis, bool $cleanBind = false): array
    {
        return $this->authenticatedRequest('jimi.open.device.move', [
            'src_account' => $srcAccount,
            'dest_account' => $destAccount,
            'imeis' => implode(',', $imeis),
            'cleanBindFlag' => $cleanBind ? '1' : '0',
        ]);
    }

    /* Media Methods */

    /**
     * Get device media URL
     */
    public function getDeviceMediaUrl(
        string $imei,
        string $camera,
        string $mediaType,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $token = null,
        int $pageNo = 0,
        int $pageSize = 10
    ): array {
        $params = [
            'imei' => $imei,
            'camera' => $camera,
            'media_type' => $mediaType,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];

        if ($startTime) $params['start_time'] = $startTime;
        if ($endTime) $params['end_time'] = $endTime;
        if ($token) $params['token'] = $token;

        return $this->authenticatedRequest('jimi.device.media.URL', $params);
    }

    /**
     * Get device Jimi photo or video URL
     */
    public function getDeviceJimiMediaUrl(
        string $imei,
        string $camera,
        string $mediaType,
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $token = null,
        int $pageNo = 0,
        int $pageSize = 10
    ): array {
        $params = [
            'imei' => $imei,
            'camera' => $camera,
            'media_type' => $mediaType,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];

        if ($startTime) $params['start_time'] = $startTime;
        if ($endTime) $params['end_time'] = $endTime;
        if ($token) $params['token'] = $token;

        return $this->authenticatedRequest('jimi.device.jimi.media.URL', $params);
    }

    /**
     * Get device live URL
     */
    public function getDeviceLiveUrl(string $imei, string $type = '1', string $voice = '1'): array
    {
        return $this->authenticatedRequest('jimi.device.live.page.url', [
            'imei' => $imei,
            'type' => $type,
            'voice' => $voice,
        ]);
    }

    /**
     * Get video RTMP URL
     */
    public function getVideoRtmpUrl(string $imei): array
    {
        return $this->authenticatedRequest('jimi.open.video.rtmp.url', [
            'imei' => $imei,
        ]);
    }

    /**
     * Get history video list
     */
    public function getHistoryVideoList(string $imei, string $type = '1'): array
    {
        return $this->authenticatedRequest('jimi.device.history.file.list', [
            'imei' => $imei,
            'type' => $type,
        ]);
    }

    /**
     * Send media instruction
     */
    public function sendMediaInstruction(
        string $imei,
        string $camera,
        string $mediaType,
        ?string $shootTime = null
    ): array {
        $params = [
            'imei' => $imei,
            'camera' => $camera,
            'mediaType' => $mediaType,
        ];

        if ($shootTime) $params['shootTime'] = $shootTime;

        return $this->authenticatedRequest('jimi.device.meida.cmd.send', $params);
    }

    /**
     * Send history video instruction
     */
    public function sendHistoryVideoInstruction(
        string $imei,
        string $type,
        string $camera,
        ?string $fileName = null,
        ?string $time = null
    ): array {
        $params = [
            'imei' => $imei,
            'type' => $type,
            'camera' => $camera,
        ];

        if ($fileName) $params['fileName'] = $fileName;
        if ($time) $params['time'] = $time;

        return $this->authenticatedRequest('jimi.device.history.cmd.send', $params);
    }

    /* Location Methods */

    /**
     * Get LBS address
     */
    public function getLbsAddress(string $imei, ?string $lbs = null, ?string $wifi = null): array
    {
        $params = ['imei' => $imei];
        if ($lbs) $params['lbs'] = $lbs;
        if ($wifi) $params['wifi'] = $wifi;
        return $this->authenticatedRequest('jimi.lbs.address.get', $params);
    }

    /* Geo-fence Methods */

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
     * Create platform geo-fence
     */
    public function createPlatformGeoFence(
        string $account,
        string $fenceName,
        string $fenceType,
        string $geom,
        ?string $fenceColor = null,
        ?string $radius = null,
        ?string $description = null
    ): array {
        $params = [
            'account' => $account,
            'fence_name' => $fenceName,
            'fence_type' => $fenceType,
            'geom' => $geom,
        ];

        if ($fenceColor) $params['fence_color'] = $fenceColor;
        if ($radius) $params['radius'] = $radius;
        if ($description) $params['description'] = $description;

        return $this->authenticatedRequest('jimi.open.platform.fence.create', $params);
    }

    /**
     * Edit platform geo-fence
     */
    public function editPlatformGeoFence(
        string $account,
        string $fenceId,
        string $fenceName,
        string $fenceType,
        string $geom,
        ?string $fenceColor = null,
        ?string $radius = null,
        ?string $description = null
    ): array {
        $params = [
            'account' => $account,
            'fence_id' => $fenceId,
            'fence_name' => $fenceName,
            'fence_type' => $fenceType,
            'geom' => $geom,
        ];

        if ($fenceColor) $params['fence_color'] = $fenceColor;
        if ($radius) $params['radius'] = $radius;
        if ($description) $params['description'] = $description;

        return $this->authenticatedRequest('jimi.open.platform.fence.edit', $params);
    }

    /**
     * Delete platform geo-fence
     */
    public function deletePlatformGeoFence(string $account, string $fenceId): array
    {
        return $this->authenticatedRequest('jimi.open.platform.fence.delete', [
            'account' => $account,
            'fence_id' => $fenceId,
        ]);
    }

    /**
     * Geo-fence related device
     */
    public function geoFenceRelatedDevice(
        string $fenceId,
        ?string $imeis = null,
        ?string $alertType = null,
        ?int $stayTimeIn = null,
        ?int $stayTimeOut = null
    ): array {
        $params = ['fence_id' => $fenceId];

        if ($imeis) $params['imeis'] = $imeis;
        if ($alertType) $params['alert_type'] = $alertType;
        if ($stayTimeIn) $params['stay_time_in'] = $stayTimeIn;
        if ($stayTimeOut) $params['stay_time_out'] = $stayTimeOut;

        return $this->authenticatedRequest('jimi.open.platform.fence.bind', $params);
    }

    /**
     * List platform geofences of an account
     */
    public function listPlatformGeoFences(string $account, int $pageNo = 1, int $pageSize = 10): array
    {
        return $this->authenticatedRequest('jimi.open.platform.fence.list', [
            'account' => $account,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * Query single fence information
     */
    public function getGeoFenceDetail(string $fenceId): array
    {
        return $this->authenticatedRequest('jimi.open.platform.fence.detail', [
            'fence_id' => $fenceId,
        ]);
    }

    /* Command Methods */

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
     * Send command to device
     */
    public function sendCommand(
        string $imei,
        int $instId,
        string $instTemplate,
        array $params,
        bool $isCover = true
    ): array {
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
     * Get command execution result
     */
    public function commandExecResult(string $imei): array
    {
        return $this->authenticatedRequest('jimi.open.instruction.result', [
            'imei' => $imei,
        ]);
    }

    /**
     * Send raw command data to device
     */
    public function sendRawCommand(string $imei, string $rawCmd): array
    {
        return $this->authenticatedRequest('jimi.open.instruction.raw.send', [
            'imei' => $imei,
            'raw_cmd' => $rawCmd,
        ]);
    }

    /* Alarm Methods */

    /**
     * Get alarm list
     */
    public function getAlarmList(
        string $imei,
        ?string $alertTypeId = null,
        ?string $beginTime = null,
        ?string $endTime = null,
        int $pageNo = 1,
        int $pageSize = 50
    ): array {
        $params = [
            'imei' => $imei,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];

        if ($alertTypeId) $params['alertTypeId'] = $alertTypeId;
        if ($beginTime) $params['begin_time'] = $beginTime;
        if ($endTime) $params['end_time'] = $endTime;

        return $this->authenticatedRequest('jimi.device.alarm.list', $params);
    }

    /* Reporting Methods */

    /**
     * Get parking/idling data of devices
     */
    public function getParkingIdlingData(
        string $account,
        string $imeis,
        string $startTime,
        string $endTime,
        string $accType,
        int $startRow = 1,
        int $pageSize = 10
    ): array {
        return $this->authenticatedRequest('jimi.open.platform.report.parking', [
            'account' => $account,
            'imeis' => $imeis,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_row' => $startRow,
            'page_size' => $pageSize,
            'acc_type' => $accType,
        ]);
    }

    /**
     * Get the trips report data of devices
     */
    public function getTripsReportData(
        string $account,
        string $imeis,
        string $type,
        string $startTime,
        string $endTime,
        int $startRow = 1,
        int $pageSize = 10
    ): array {
        return $this->authenticatedRequest('jimi.open.platform.report.trips', [
            'account' => $account,
            'imeis' => $imeis,
            'type' => $type,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_row' => $startRow,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * Get entry and exit fence data of devices
     */
    public function getFenceEntryExitData(
        string $account,
        string $imeis,
        string $startTime,
        string $endTime,
        int $startRow = 1,
        int $pageSize = 10
    ): array {
        return $this->authenticatedRequest('jimi.open.platform.fence.duration', [
            'account' => $account,
            'imeis' => $imeis,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'start_row' => $startRow,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * Get the OBD data of devices
     */
    public function getObdData(
        string $account,
        string $imeis,
        string $startTime,
        string $endTime,
        int $pageNo = 1,
        int $pageSize = 10
    ): array {
        return $this->authenticatedRequest('jimi.device.obd.list', [
            'account' => $account,
            'imeis' => $imeis,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * Get the OBD fault data of devices
     */
    public function getObdFaultData(
        string $account,
        string $imeis,
        string $startTime,
        string $endTime,
        int $pageNo = 1,
        int $pageSize = 10
    ): array {
        return $this->authenticatedRequest('jimi.device.obd.fault', [
            'account' => $account,
            'imeis' => $imeis,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * Get RFID reporting information
     */
    public function getRfidReportingInfo(
        string $account,
        string $startTime,
        string $endTime,
        ?string $imeis = null,
        ?string $cardIds = null,
        int $pageNo = 1,
        int $pageSize = 10
    ): array {
        $params = [
            'account' => $account,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];

        if ($imeis) $params['imeis'] = $imeis;
        if ($cardIds) $params['card_ids'] = $cardIds;

        return $this->authenticatedRequest('jimi.open.device.rfid.list', $params);
    }

    /* Device Group Methods */

    /**
     * Create device group
     */
    public function createDeviceGroup(string $account, string $groupName): array
    {
        return $this->authenticatedRequest('jimi.device.group.create', [
            'account' => $account,
            'group_name' => $groupName,
        ]);
    }

    /**
     * Edit device group
     */
    public function editDeviceGroup(string $groupId, string $groupName): array
    {
        return $this->authenticatedRequest('jimi.device.group.update', [
            'group_id' => $groupId,
            'group_name' => $groupName,
        ]);
    }

    /**
     * Delete device group
     */
    public function deleteDeviceGroup(string $groupId): array
    {
        return $this->authenticatedRequest('jimi.device.group.delete', [
            'group_id' => $groupId,
        ]);
    }

    /**
     * Get device group list of an account
     */
    public function getDeviceGroupList(string $account): array
    {
        return $this->authenticatedRequest('jimi.device.group.list', [
            'account' => $account,
        ]);
    }

    /* User Binding Methods */

    /**
     * Bind app user
     */
    public function bindAppUser(string $imei, string $userId): array
    {
        return $this->authenticatedRequest('jimi.open.device.bind', [
            'imei' => $imei,
            'user_id' => $userId,
        ]);
    }

    /**
     * Unbind app user
     */
    public function unbindAppUser(string $imei, string $userId): array
    {
        return $this->authenticatedRequest('jimi.open.device.unbind', [
            'imei' => $imei,
            'user_id' => $userId,
        ]);
    }

    /* Helper Methods */

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

        return strtoupper(md5($signString));
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
}
