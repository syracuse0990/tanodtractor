<?php

namespace App\Http\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\DeviceGeoFence;
use App\Models\IssueType;
use App\Models\Jimi;
use App\Models\Notification;
use App\Models\TractorBooking;
use App\Models\TractorGroup;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

class JimiController extends Controller
{

    public function authToken()
    {
        try {
            $date = date('Y-m-d H:i:s');
            $gmt_date = gmdate('Y-m-d H:i:s', strtotime($date));
            $user = User::where('role_id', User::ROLE_ADMIN)->first();
            $diff = round((strtotime($gmt_date) - strtotime($user->api_token_time)) / 3600, 1);

            if (empty($user->api_access_token) || $diff >= 2) {
                (new Jimi())->getToken();
                $user = User::where('role_id', User::ROLE_ADMIN)->first();
            }

            $returnArrData = [
                'auth_token' => $user->api_access_token,
                'auth_token_time' => $user->api_token_time
            ];

            return returnSuccessResponse('Get auth token successfully', $returnArrData);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function deviceLocation(Request $request)
    {
        $rules = [
            'imeis' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $returnArrData = [];
            if ($request->notification_id) {
                $notification  = Notification::findorFail($request->notification_id);
                if ($notification) {
                    $notification->is_read = Notification::IS_READ;
                    $notification->save();
                }
            }
            $imeis = is_array($request->imeis) ? $request->imeis : explode(',', $request->imeis);

            $batchSize = 100;
            $imeisChunks = array_chunk($imeis, $batchSize);

            foreach ($imeisChunks as $chunk) {
                $callApi = (new Jimi())->getDeviceLocation($chunk);
                $callAPiData[] = $callApi;
            }
            $returnArrData = array_reduce($callAPiData, function ($value, $resultArray) {
                return array_merge_recursive($value, $resultArray);
            }, []);

            if (is_array($returnArrData['code']) && !in_array(0, $returnArrData['code'])) {
                return notAuthorizedResponse($returnArrData['message'], $returnArrData, 'error');
            }
            if (!is_array($returnArrData['code']) && $returnArrData['code'] == 0) {
                return returnSuccessResponse($returnArrData['message'], $returnArrData, 'error');
            }
            return returnSuccessResponse('Get device location successfully', $returnArrData);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function sharingLoation(Request $request)
    {
        $rules = [
            'imei' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $returnArrData = (new Jimi())->getSharingLocationUrl($request->imei);

            return returnSuccessResponse('Get device sharing location url successfully', $returnArrData);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function deviceMilage(Request $request)
    {
        $rules = [
            'imeis' => 'required',
            'begin_time' => 'required',
            'end_time' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        try {
            $imeis = is_array($request->imeis) ? $request->imeis : explode(',', $request->imeis);
            $returnArrData = (new Jimi())->getDeviceMilage($imeis, $request->begin_time, $request->end_time);

            return returnSuccessResponse('Get device sharing location url successfully', $returnArrData);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function deviceTrackData(Request $request)
    {
        $rules = [
            'id' => 'required',
            'period' => 'required',
            'date_range' => Rule::requiredIf($request->period == 8)
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {

            $dataArr = [];
            $output = null;

            $imei = Device::where('id', $request->id)->value('imei_no');

            $cacheKey = 'deviceData_' . $imei . '_' . $request->period . '_' . ($request->date_range ?? 'range');

            if (!is_null($request->period)) {
                if (Cache::has($cacheKey)) {
                    $deviceData = Cache::get($cacheKey);
                } else {
                    if ($request->period == 1) {
                        //Today
                        $begin_time = gmdate('Y-m-d');
                        $end_time = gmdate('Y-m-d');
                    } elseif ($request->period == 2) {
                        //Yesterday
                        $begin_time = gmdate('Y-m-d', strtotime('-1 day'));
                        $end_time = gmdate('Y-m-d', strtotime('-1 day'));
                    } elseif ($request->period == 3) {
                        //Last 3 days
                        $begin_time = gmdate('Y-m-d', strtotime('-2 day'));
                        $end_time = gmdate('Y-m-d');
                    } elseif ($request->period == 4) {
                        //This week
                        $thisWeek = Carbon::now()->startOfWeek();
                        $begin_time = $thisWeek->format('Y-m-d');
                        $end_time = gmdate('Y-m-d');
                    } elseif ($request->period == 5) {
                        //Last week
                        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek();
                        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek();
                        $begin_time = $lastWeekStart->format('Y-m-d');
                        $end_time = $lastWeekEnd->format('Y-m-d');
                    } elseif ($request->period == 6) {
                        //This Month
                        $thisMonth = Carbon::now()->startOfMonth();
                        $begin_time = $thisMonth->format('Y-m-d');
                        $end_time = gmdate('Y-m-d');
                        $weeks = getWeeksOfMonth($begin_time, $end_time);
                    } elseif ($request->period == 7) {
                        //Last Month
                        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
                        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
                        $begin_time = $lastMonthStart->format('Y-m-d');
                        $end_time = $lastMonthEnd->format('Y-m-d');
                        $weeks = getWeeksOfMonth($begin_time, $end_time);
                    } elseif ($request->period == 8) {
                        $date = $request->date_range ? explode(' - ', $request->date_range) : null;
                        if (!empty($date)) {
                            $begin_time = gmdate('Y-m-d', strtotime($date[0]));
                            $end_time = gmdate('Y-m-d', strtotime($date[1]));
                            $weeks = getWeeksOfMonth($begin_time, $end_time);
                        } else {
                            $response['error'] = 'Please select date range.';
                            return $response;
                        }
                    }

                    if (isset($weeks)) {
                        foreach ($weeks as $week) {
                            $time = "23:59:59";
                            if ($week['end'] == gmdate('Y-m-d')) {
                                $time = gmdate('H:i:s');
                            }
                            $begin_time  = $week['start'] . ' 00:00:00';
                            $end_time  = $week['end'] . ' ' . $time;
                            $output = (new Jimi())->getDeviceTrackData($imei, $begin_time, $end_time);
                            if ($output['code'] === 0 && isset($output['result'])) {
                                $deviceData[] = $output['result'];
                            }
                        }
                        $deviceData = singleArray($deviceData);
                    } else {
                        $time = '23:59:59';
                        if ($end_time == gmdate('Y-m-d')) {
                            $time = gmdate('H:i:s');
                        }
                        $begin_time  = $begin_time . ' 00:00:00';
                        $end_time  = $end_time . ' ' . $time;
                        $output = (new Jimi())->getDeviceTrackData($imei, $begin_time, $end_time);
                        if ($output['code'] === 0 && isset($output['result']) && !empty($output['result'])) {
                            $deviceData = $output['result'];
                        }
                    }
                }
                if (isset($deviceData)) {
                    Cache::put($cacheKey, $deviceData, now()->addMinutes(5));
                }
            }
            if (isset($deviceData)) {
                foreach ($deviceData as $data) {
                    $dataArr[] = [
                        'lat_lng' => ['lat' => $data['lat'], 'lng' => $data['lng']],
                        'gpsTime' => $data['gpsTime'],
                        'gpsSpeed' => $data['gpsSpeed'],
                        'direction' => $data['direction'],
                    ];
                }

                return  response()->json(['status' => true, 'message' => 'Get device track successfully', 'data' => $dataArr]);
            } else {
                return  response()->json(['status' => true, 'message' => 'No data found', 'data' => null]);
            }
            return  response()->json(['status' => true, 'message' => 'Get device track successfully', 'data' => null]);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => null]);
        }
    }

    public function deviceTrackDataOld(Request $request)
    {
        $rules = [
            'id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        try {
            $booking = TractorBooking::findorFail($request->id);
            if (!$booking) {
                return returnNotFoundResponse('No booking found');
            }
            // if (Auth::user()->role_id != User::ROLE_ADMIN) {
            //     return returnNotAllowedResponse('403, you are not allowed to perform this acton!.');
            // }
            $device = Device::findorFail($booking->device_id);
            if (!$device) {
                return returnNotFoundResponse('No device found');
            }
            $begin_time = $booking->date . ' 00:00:00';
            $end_time = $booking->date . ' 23:59:59';
            $returnArrData = (new Jimi())->getDeviceTrackData($device->imei_no, $begin_time, $end_time);

            return returnSuccessResponse('Get device track successfully', $returnArrData);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function createGeoFence(Request $request)
    {
        $rules = [
            'imei' => 'required',
            'fence_name' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'radius' => 'required',
            'date' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $requestImeis = is_array($request->imei) ? $request->imei : [$request->imei];


            $code = $apiMessage = $apiResult = $imeis = $successImeis = $successMessage = $successResults = $errorMessage = [];

            foreach ($requestImeis as $imei) {
                $deviceGeoFenceOld = DeviceGeoFence::with('createdBy')->where(['imei' => $imei, 'state_id' => DeviceGeoFence::STATE_ACTIVE, 'date' => $request->date])->first();
                if ($deviceGeoFenceOld) {
                    continue;
                }
                $apiCall = (new Jimi())->createGeoFence($imei, $request->fence_name, $request->latitude, $request->longitude, $request->radius);
                $code[] = $apiCall['code'];
                $apiMessage[] = $apiCall['message'];
                $apiResult[] = isset($apiCall['result']) ? $apiCall['result'] : null;
                $imeis[] = $imei;
            }

            foreach ($code as $key => $status) {
                if ($status == 0) {
                    $successImeis[] = $imeis[$key];
                    $successMessage[] = $imeis[$key] . ' ' . $apiMessage[$key];
                    $successResults[] = $apiResult[$key];
                } elseif ($status == 500) {
                    $errorMessage[] = $imeis[$key] . ' 500 Internal Server Error';
                } else {
                    $errorMessage[] = $imeis[$key] . ' ' . $apiCall['message'];
                }
            }

            if (count($successImeis)) {
                $deviceGeoFenceData = $request->all();
                $deviceGeoFenceData['date'] = date('Y-m-d', strtotime($request->date));
                $deviceGeoFenceData['imei'] = implode(',', $successImeis);
                $deviceGeoFenceData['geo_fence_id'] = count($successResults) ? implode(',', $successResults) : null;
                // $deviceGeoFenceData['geo_fence_id'] = 1;
                $deviceGeoFence = DeviceGeoFence::create($deviceGeoFenceData);
            }

            if (count($errorMessage)) {
                return notAuthorizedResponse($errorMessage[0], null, 'Bad Request');
            }
            return returnSuccessResponse('Geo fence created successfully', $deviceGeoFence->createJsonResponse());
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function deleteGeoFence(Request $request)
    {
        $rules = [
            'imei' => 'required',
            'instruct_no' => 'required',

        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $deviceGeoFence = DeviceGeoFence::where(['geo_fence_id' => $request->instruct_no, 'state_id' => DeviceGeoFence::STATE_ACTIVE])->first();

            $existImeis = $deviceGeoFence->imei ? explode(',', $deviceGeoFence->imei) : [];
            $existGeoFenceIds = $deviceGeoFence->geo_fence_id ? explode(',', $deviceGeoFence->geo_fence_id) : [];

            foreach ($existImeis as $key => $imei) {
                $callDeleteApi = (new Jimi())->deleteGeoFence($imei, $existGeoFenceIds[$key]);
                if ($callDeleteApi['code'] == 500) {
                    $errorMessage[] = $imei . ' 500 Internal Server Error';
                } elseif ($callDeleteApi['code']) {
                    $errorMessage[] = $imei . ' ' . $callDeleteApi['message'];
                }
            }
            if ($errorMessage) {
                return notAuthorizedResponse('Delete fence failure', $errorMessage, 'Bad Request');
            }
            $deviceGeoFence->state_id = DeviceGeoFence::STATE_DELETED;
            $deviceGeoFence->save();
            return returnSuccessResponse('Geo fence deleted successfully', $deviceGeoFence->createJsonResponse());
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function updateGeoFence(Request $request)
    {
        $rules = [
            'id' => 'required',
            'imei' => 'required',
            'fence_name' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'radius' => 'required',
            'zoom_level' => 'required',
            'date' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $deviceGeoFence = DeviceGeoFence::findorFail($request->id);

            if (!$deviceGeoFence) {
                return returnNotFoundResponse("Geo fence not found");
            }

            $code = $apiMessage = $apiResult = $imeis = $successImeis = $successMessage = $successResults = $errorMessage = [];

            $existImeis = $deviceGeoFence->imei ? explode(',', $deviceGeoFence->imei) : [];
            $existGeoFenceIds = $deviceGeoFence->geo_fence_id ? explode(',', $deviceGeoFence->geo_fence_id) : [];

            foreach ($existImeis as $key => $imei) {
                $callDeleteApi = (new Jimi())->deleteGeoFence($imei, $existGeoFenceIds[$key]);
                if ($callDeleteApi['code'] == 500) {
                    $errorMessage[] = $imei . ' 500 Internal Server Error';
                } elseif ($callDeleteApi['code']) {
                    $errorMessage[] = $imei . ' ' . $callDeleteApi['message'];
                }
            }

            foreach ($request->imei as $imei) {
                $apiCall = (new Jimi())->createGeoFence($imei, $request->fence_name, $request->latitude, $request->longitude, $request->radius);
                $code[] = $apiCall['code'];
                $apiMessage[] = $apiCall['message'];
                $apiResult[] = isset($apiCall['result']) ? $apiCall['result'] : null;
                $imeis[] = $imei;
            }

            foreach ($code as $key => $status) {
                if ($status == 0) {
                    $successImeis[] = $imeis[$key];
                    $successMessage[] = $imeis[$key] . ' ' . $apiMessage[$key];
                    $successResults[] = $apiResult[$key];
                } elseif ($status == 500) {
                    $errorMessage[] = $imeis[$key] . ' 500 Internal Server Error';
                } else {
                    $errorMessage[] = $imeis[$key] . ' ' . $apiCall['message'];
                }
            }

            if (count($successImeis)) {
                $deviceGeoFenceData = $request->all();
                $deviceGeoFenceData['date'] = date('Y-m-d', strtotime($request->date));
                $deviceGeoFenceData['imei'] = implode(',', $successImeis);
                $deviceGeoFenceData['geo_fence_id'] = count($successResults) ? implode(',', $successResults) : null;
                // $deviceGeoFenceData['geo_fence_id'] = 1;
                $deviceGeoFence->update($deviceGeoFenceData);
            }

            if (count($errorMessage)) {
                return notAuthorizedResponse('Something went wrong!', $errorMessage, 'Bad Request');
            }

            return returnSuccessResponse('Geo fence updatee successfully', $deviceGeoFence->createJsonResponse());
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function deviceList(Request $request)
    {
        $rules = [
            'state' => 'required',

        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $allDevices = $returnData = [];
            $deviceImeis = Device::query();
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
                $deviceIds = multiDimToSingleDim($deviceIds);
                $deviceImeis = $deviceImeis->whereIn('id', $deviceIds);
            }
            $deviceImeis = $deviceImeis->pluck('imei_no')->toArray();
            $imeis = is_array($deviceImeis) ? $deviceImeis : explode(',', $deviceImeis);
            $apiData = (new Jimi())->getDeviceLocationList();
            foreach ($apiData['result'] as $apiData) {
                $deviceData = Device::query();
                if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                    $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                    $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                    $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
                    $deviceIds = multiDimToSingleDim($deviceIds);
                    $deviceData = $deviceData->whereIn('id', $deviceIds);
                }
                $deviceData = $deviceData->where('imei_no', $apiData['imei'])->first();
                if ($request->state == Device::ALL_DEVICES) {
                    array_push($allDevices, $deviceData);
                } elseif ($request->state == Device::ONLINE_DEVICES) {
                    if ($apiData['status'] == Device::STATE_ACTIVE) {
                        array_push($allDevices, $deviceData);
                    }
                } elseif ($request->state == Device::OFFLINE_DEVICES) {
                    if ($apiData['status'] == Device::STATE_INACTIVE) {
                        array_push($allDevices, $deviceData);
                    }
                }
            }
            foreach ($allDevices as $value) {
                if (is_null($value)) {
                    continue;
                }
                array_push($returnData, $value);
            }

            if (!$returnData) {
                return response()->json(['statusCode' => 200, 'status' => 'success', 'message' => 'Get Device list successfully', 'data' => []], 200);
            }
            return returnSuccessResponse('Get Device list successfully', $returnData);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function getData(Request $request)
    {
        $period = strtolower($request->period);
        $rules = [
            'imei' => 'required',
            'period' => 'required',
            'date_range' => [Rule::requiredIf($period == 'custom')],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            return response()->json(['status' => false, 'message' => $errorMessages[0], 'data' => []]);
        }

        $imei = $request->imei;
        $cacheKey = 'deviceData_' . $imei . '_' . $period . '_' . ($request->date_range ?? '');

        // Check if data is cached
        if (Cache::has($cacheKey)) {
            $deviceData = Cache::get($cacheKey);
        } else {
            // Data is not cached, retrieve it from the API
            switch ($period) {
                case 'today':
                    $begin_time = gmdate('Y-m-d 00:00:00');
                    $end_time = gmdate('Y-m-d 23:59:59');
                    break;
                case 'this week':
                    $begin_time = Carbon::now()->startOfWeek()->format('Y-m-d 00:00:00');
                    $end_time = gmdate('Y-m-d 23:59:59');
                    break;
                case 'this month':
                    $begin_time = Carbon::now()->startOfMonth()->format('Y-m-d 00:00:00');
                    $end_time = gmdate('Y-m-d 23:59:59');
                    break;
                case 'custom':
                    $data = explode(' - ', $request->date_range);
                    if (count($data) == 2) {
                        $begin_time = gmdate('Y-m-d 00:00:00', strtotime($data[0]));
                        $end_time = gmdate('Y-m-d 23:59:59', strtotime($data[1]));
                    } else {
                        return response()->json(['status' => false, 'message' => 'Invalid date range format', 'data' => []]);
                    }
                    break;
                default:
                    return response()->json(['status' => false, 'message' => 'Invalid period', 'data' => []]);
            }

            $deviceData = [];

            if ($period == 'this month' || $period == 'custom') {
                $weeks = getWeeksOfMonth($begin_time, $end_time);
                foreach ($weeks as $week) {
                    $output = (new Jimi())->getDeviceTrackData($imei, $week['start'] . ' 00:00:00', $week['end'] . ' 23:59:59');
                    if (isset($output['code']) && $output['code'] === 0 && !empty($output['result'])) {
                        $deviceData = array_merge($deviceData, $output['result']);
                    }
                }
            } else {
                $output = (new Jimi())->getDeviceTrackData($imei, $begin_time, $end_time);
                if (isset($output['code']) && $output['code'] === 0 && !empty($output['result'])) {
                    $deviceData = $output['result'];
                }
            }

            Cache::put($cacheKey, $deviceData, now()->addMinutes(30)); // Cache data for 30 minutes
        }

        if (empty($deviceData)) {
            return response()->json(['status' => false, 'message' => 'No device data found for the selected period', 'data' => []]);
        }

        $formattedData = [];
        foreach ($deviceData as $key => $device) {
            $positionType = match ($device['posType'] ?? 0) {
                1 => 'GPS',
                2 => 'LBS',
                3 => 'WIFI',
                default => 'N/A',
            };

            $formattedData[] = [
                'No' => $key + 1,
                'Position Time' => gmdate('Y-m-d H:i:s', strtotime($device['gpsTime'] ?? '')),
                'Speed' => $device['gpsSpeed'] ?? 'N/A',
                'Azimuth' => $device['direction'] ?? 'N/A',
                'Position type' => $positionType,
                'No. of satellites' => $device['satellite'] ?? 'N/A',
                'latitude' => $device['lat'] ?? 'N/A',
                'longitude' =>  $device['lng'] ?? 'N/A'
            ];
        }

        return response()->json(['status' => true, 'message' => 'Device data retrieved successfully', 'data' => $formattedData]);
    }
}
