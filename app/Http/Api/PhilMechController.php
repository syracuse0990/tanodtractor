<?php

namespace App\Http\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\JimiService;

class PhilMechController extends BaseController
{
    private JimiService $jimiService;

    public function __construct(JimiService $jimiService)
    {
        $this->jimiService = $jimiService;
    }

    public function createToken(Request $request){
        $secret = $request->get('secret');

        if ($secret === env('SECRET_DEX')) {
            $email = env('SECRET_EMAIL');
            $password = env('SECRET_PASS');

            if (!Auth::attempt(['email' => $email, 'password' => $password])) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $request->user()->tokens()->delete();

            $token = $request->user()->createToken('philmech-token')->plainTextToken;

            return $this->sendResponse($token, 'Your Token');
        }

        return $this->sendError('Wag po.', [], 422);
    }

    public function getToken()
    {
        try {
            $response = $this->jimiService->getToken();
            return $this->sendResponse($response, 'Token retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to get token', $e->getMessage(), 500);
        }
    }

     public function refreshToken(Request $request){
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation errors', $validator->errors(), 400);
        }

        try {
            $response = $this->jimiService->refreshToken($request->refresh_token);
            return $this->sendResponse($response, 'Token refreshed successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to refresh token', $e->getMessage(), 500);
        }
    }

    public function getDeviceList(Request $request){
        $targetAccount = $request->query('target_account');

        try {
            $response = $this->jimiService->getDeviceList($targetAccount);
            return $this->sendResponse($response, 'Device list retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to get device list', $e->getMessage(), 500);
        }
    }

      public function getDeviceDetail($imei){
        try {
            $response = $this->jimiService->getDeviceDetail($imei);
            return $this->sendResponse($response, 'Device details retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to get device details', $e->getMessage(), 500);
        }
    }

    public function getDeviceLocationList(Request $request){
        $targetAccount = $request->query('target_account');

        try {
            $response = $this->jimiService->getDeviceLocationList($targetAccount);
            return $this->sendResponse($response, 'Device locations retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to get device locations', $e->getMessage(), 500);
        }
    }

    public function getDeviceLocation(Request $request){
        $validator = Validator::make($request->all(), [
            'imeis' => 'required|array',
            'imeis.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation errors', $validator->errors(), 400);
        }

        try {
            $response = $this->jimiService->getDeviceLocation($request->imeis);
            return $this->sendResponse($response, 'Current locations retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to get current locations', $e->getMessage(), 500);
        }
    }

     public function getTagDeviceLocation(Request $request){
        $validator = Validator::make($request->all(), [
            'imeis' => 'required|array',
            'imeis.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation errors', $validator->errors(), 400);
        }

        try {
            $response = $this->jimiService->getTagDeviceLocation($request->imeis);
            return $this->sendResponse($response, 'Tag device locations retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to get tag device locations', $e->getMessage(), 500);
        }
    }

    public function getSharingLocationUrl($imei){
        try {
            $response = $this->jimiService->getSharingLocationUrl($imei);
            return $this->sendResponse($response, 'Sharing URL retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to get sharing URL', $e->getMessage(), 500);
        }
    }

    public function updateExpiration(Request $request){
        $validator = Validator::make($request->all(), [
            'imei_list' => 'required|array',
            'imei_list.*' => 'required|string',
            'new_expiration' => 'required|date'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation errors', $validator->errors(), 400);
        }

        try {
            $response = $this->jimiService->updateExpiration($request->imei_list, $request->new_expiration);
            return $this->sendResponse($response, 'Device expiration updated successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to update device expiration', $e->getMessage(), 500);
        }
    }

    public function getDeviceMileage(Request $request){
        $validator = Validator::make($request->all(), [
            'imeis' => 'required|array',
            'imeis.*' => 'required|string',
            'begin_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:begin_time'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation errors', $validator->errors(), 400);
        }

        try {
            $response = $this->jimiService->getDeviceMileage(
                $request->imeis,
                $request->begin_time,
                $request->end_time
            );
            return $this->sendResponse($response, 'Mileage data retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to get mileage data', $e->getMessage(), 500);
        }
    }
    public function getDeviceTrackData(Request $request){
        $validator = Validator::make($request->all(), [
            'imei' => 'required|string',
            'begin_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:begin_time'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation errors', $validator->errors(), 400);
        }

        try {
            $response = $this->jimiService->getDeviceTrackData(
                $request->imei,
                $request->begin_time,
                $request->end_time
            );
            return $this->sendResponse($response, 'Track data retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to get track data', $e->getMessage(), 500);
        }
    }

    public function updateVehicleInfo(Request $request, $imei){
        try {
            $response = $this->jimiService->updateVehicleInfo(
                $imei,
                $request->device_name,
                $request->vehicle_name,
                $request->vehicle_icon,
                $request->vehicle_number,
                $request->vehicle_models,
                $request->driver_name,
                $request->driver_phone,
                $request->device_status,
                $request->sim,
                $request->remarks,
                $request->oilWear,
                $request->deviceGroupId
            );
            return $this->sendResponse($response, 'Vehicle info updated successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to update vehicle info', $e->getMessage(), 500);
        }
    }

    public function moveDevices(Request $request){
        $validator = Validator::make($request->all(), [
            'src_account' => 'required|string',
            'dest_account' => 'required|string',
            'imeis' => 'required|array',
            'imeis.*' => 'required|string',
            'clean_bind' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation errors', $validator->errors(), 400);
        }

        try {
            $response = $this->jimiService->moveDevices(
                $request->src_account,
                $request->dest_account,
                $request->imeis,
                $request->clean_bind ?? false
            );
            return $this->sendResponse($response, 'Devices moved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to move devices', $e->getMessage(), 500);
        }
    }

    public function getDeviceMediaUrl(Request $request){
        $validator = Validator::make($request->all(), [
            'imei' => 'required|string',
            'camera' => 'required|string',
            'media_type' => 'required|string',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date',
            'token' => 'sometimes|string',
            'page_no' => 'sometimes|integer|min=0',
            'page_size' => 'sometimes|integer|min=1'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation errors', $validator->errors(), 400);
        }

        try {
            $response = $this->jimiService->getDeviceMediaUrl(
                $request->imei,
                $request->camera,
                $request->media_type,
                $request->start_time,
                $request->end_time,
                $request->token,
                $request->page_no ?? 0,
                $request->page_size ?? 10
            );
            return $this->sendResponse($response, 'Media URL retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to get media URL', $e->getMessage(), 500);
        }
    }

      public function createGeoFence(Request $request){
        $validator = Validator::make($request->all(), [
            'imei' => 'required|string',
            'fence_name' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'required|integer',
            'alarm_type' => 'sometimes|string',
            'report_mode' => 'sometimes|string',
            'alarm_switch' => 'sometimes|string',
            'map_type' => 'sometimes|string',
            'zoom_level' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation errors', $validator->errors(), 400);
        }

        try {
            $response = $this->jimiService->createGeoFence(
                $request->imei,
                $request->fence_name,
                $request->lat,
                $request->lng,
                $request->radius,
                $request->alarm_type ?? 'out',
                $request->report_mode ?? '1',
                $request->alarm_switch ?? 'ON',
                $request->map_type ?? 'GOOGLE',
                $request->zoom_level ?? '10'
            );
            return $this->sendResponse($response, 'Geo-fence created successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to create geo-fence', $e->getMessage(), 500);
        }
    }

    public function getCommandList($imei){
        try {
            $response = $this->jimiService->getCommandList($imei);
            return $this->sendResponse($response, 'Command list retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to get command list', $e->getMessage(), 500);
        }
    }

    public function sendCommand(Request $request, $imei){
        $validator = Validator::make($request->all(), [
            'inst_id' => 'required|integer',
            'inst_template' => 'required|string',
            'params' => 'required|array',
            'is_cover' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation errors', $validator->errors(), 400);
        }

        try {
            $response = $this->jimiService->sendCommand(
                $imei,
                $request->inst_id,
                $request->inst_template,
                $request->params,
                $request->is_cover ?? true
            );
            return $this->sendResponse($response, 'Command sent successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to send command', $e->getMessage(), 500);
        }
    }

    public function getParkingIdlingData(Request $request){
        $validator = Validator::make($request->all(), [
            'account' => 'required|string',
            'imeis' => 'required|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time',
            'acc_type' => 'required|string',
            'start_row' => 'sometimes|integer|min=1',
            'page_size' => 'sometimes|integer|min=1'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation errors', $validator->errors(), 400);
        }

        try {
            $response = $this->jimiService->getParkingIdlingData(
                $request->account,
                $request->imeis,
                $request->start_time,
                $request->end_time,
                $request->acc_type,
                $request->start_row ?? 1,
                $request->page_size ?? 10
            );
            return $this->sendResponse($response, 'Parking/idling data retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to get parking/idling data', $e->getMessage(), 500);
        }
    }

    public function createDeviceGroup(Request $request){
        $validator = Validator::make($request->all(), [
            'account' => 'required|string',
            'group_name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation errors', $validator->errors(), 400);
        }

        try {
            $response = $this->jimiService->createDeviceGroup(
                $request->account,
                $request->group_name
            );
            return $this->sendResponse($response, 'Device group created successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to create device group', $e->getMessage(), 500);
        }
    }

    public function bindAppUser(Request $request, $imei){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation errors', $validator->errors(), 400);
        }

        try {
            $response = $this->jimiService->bindAppUser(
                $imei,
                $request->user_id
            );
            return $this->sendResponse($response, 'User bound to device successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to bind user to device', $e->getMessage(), 500);
        }
    }




}

