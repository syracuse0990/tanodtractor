<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\AssignedDevice;
use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\Jimi;
use App\Models\Tractor;
use App\Models\TractorBooking;
use App\Models\TractorGroup;
use App\Models\User;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class DeviceController
 * @package App\Http\Controllers
 */
class DeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $rules = [
            'records_per_page' => 'required',
            'page_no' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {

            if (in_array(Auth::user()->role_id, [User::ROLE_ADMIN, User::ROLE_SUB_ADMIN, User::ROLE_GOVERNMENT])) {
                if ($request->allData) {
                    $device = Device::query();
                    if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                        $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                        $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                        $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
                        $deviceIds = multiDimToSingleDim($deviceIds);
                        $device = $device->whereIn('id', $deviceIds);
                    }
                    if ($request->search) {
                        $device->where('imei', 'LIKE', '%' . $request->search . '%');
                    }
                    $device = $device->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                    $totalCount = $device->total();
                    $total_pages = ceil($totalCount / $request->records_per_page);

                    $returnArrData = [
                        'devices' => $device->all(),
                        'page_no' => $request->page_no,
                        'total_entries' => $totalCount,
                        'total_pages' => $total_pages
                    ];
                } else {
                    if ($request->group_id) {
                        $deviceData = TractorGroup::where('id', '!=', $request->group_id)->pluck('device_ids')->toArray();
                        $device_ids = multiDimToSingleDim($deviceData);
                        $device = Device::whereNotIn('id', $device_ids)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                        $totalCount = $device->total();
                        $total_pages = ceil($totalCount / $request->records_per_page);

                        $returnArrData = [
                            'devices' => $device->all(),
                            'page_no' => $request->page_no,
                            'total_entries' => $totalCount,
                            'total_pages' => $total_pages
                        ];
                    } else {
                        $deviceData = TractorGroup::pluck('device_ids')->toArray();
                        $device_ids = multiDimToSingleDim($deviceData);
                        $device = Device::whereNotIn('id', $device_ids)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                        $totalCount = $device->total();
                        $total_pages = ceil($totalCount / $request->records_per_page);

                        $returnArrData = [
                            'devices' => $device->all(),
                            'page_no' => $request->page_no,
                            'total_entries' => $totalCount,
                            'total_pages' => $total_pages
                        ];
                    }
                }
                return returnSuccessResponse('Get all device list successfully', $returnArrData);
            } elseif (Auth::user()->role_id == User::ROLE_FARMER) {
                $currentUserGroup = $farmerGroup = null;
                $user_id = Auth::user()->id;
                $groups = TractorGroup::get();
                foreach ($groups as $group) {
                    $farmerIds = $group->farmer_ids ? json_decode($group->farmer_ids, true) : [];
                    if (in_array($user_id, $farmerIds)) {
                        $currentUserGroup = $group;
                    }
                }
                $farmerGroup = $currentUserGroup;
                if (!empty($farmerGroup->device_ids)) {
                    $device = Device::whereIn('id', json_decode($farmerGroup->device_ids, true))->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                    $totalCount = $device->total();
                    $total_pages = ceil($totalCount / $request->records_per_page);
                    $listArr = array();
                    foreach ($device as $key => $value) {
                        array_push($listArr, $value);
                    }
                    $returnArrData = [
                        'devices' => $device->all(),
                        'page_no' => $request->page_no,
                        'total_entries' => $totalCount,
                        'total_pages' => $total_pages
                    ];
                    return returnSuccessResponse('Get all device list successfully ', $returnArrData);
                } else {
                    return returnSuccessResponse('No record found!!');
                }
            }
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'imei_no' => 'required',
            'device_modal' => 'required',
            'device_name' => 'required',
            'sim' => 'required|numeric|digits:10|unique:devices,sim'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {

            $deviceData = $request->all();
            $deviceData['state_id'] = (!empty($request->state_id)) ? $request->state_id : Device::STATE_ACTIVE;
            $device = Device::create($deviceData);
            return returnSuccessResponse('Device created successfully', $device);
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
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
            if (in_array(Auth::user()->role_id, [User::ROLE_ADMIN, User::ROLE_SUB_ADMIN, User::ROLE_GOVERNMENT])) {
                $device = Device::findorFail($request->id);
                if (empty($device)) {
                    return returnNotFoundResponse('No device found');
                }
                return returnSuccessResponse('Get device detail successfully', $device);
            } elseif (Auth::user()->role_id == User::ROLE_FARMER) {
                $currentUserGroup = $farmerGroup = null;
                $user_id = Auth::user()->id;
                $groups = TractorGroup::get();
                foreach ($groups as $group) {
                    $farmerIds = $group->farmer_ids ? json_decode($group->farmer_ids, true) : [];
                    if (in_array($user_id, $farmerIds)) {
                        $currentUserGroup = $group;
                    }
                }
                $farmerGroup = $currentUserGroup;
                if (!empty($farmerGroup->device_ids)) {
                    if (in_array($request->id, json_decode($farmerGroup->device_ids, true))) {

                        $device = Device::findorFail($request->id);
                        if (empty($device)) {
                            return returnSuccessResponse('No slots available.');
                        }
                        return returnSuccessResponse('Get device detail successfully. ', $device);
                    } else {
                        return returnNotFoundResponse('This device not found in your group.');
                    }
                } else {
                    return returnSuccessResponse('No record found!!');
                }
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Device $device
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $rules = [
            'id' => 'required',
            'imei_no' => 'required',
            'device_modal' => 'required',
            'device_name' => 'required',
            'sim' => 'required|numeric|digits:10|unique:devices,sim,' . $request->id
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        try {
            $device = Device::findorFail($request->id);
            if (empty($device)) {
                return returnNotFoundResponse('No device found.');
            }
            $deviceData = $request->all();
            $deviceData['state_id'] = (!empty($request->state_id)) ? $request->state_id : Device::STATE_ACTIVE;

            $device->update($deviceData);
            return returnSuccessResponse('Device updated successfully', $device);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(Request $request)
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
            $device = device::findorFail($request->id);
            if (!empty($device)) {
                $device->delete();
                return response()->json(['status' => true, 'message' => 'Device deleted successfully', 'data' => null]);
            } else {
                return returnNotFoundResponse('No device found.');
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function alertsList(Request $request)
    {
        $rules = [
            'imei' => 'required',
            'records_per_page' => 'required',
            'page_no' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        try {
            $alerts = Alert::where('imei', $request->imei);
            if ($request->alarm_type) {
                $alerts = $alerts->where('alarm_type', $request->alarm_type);
            }
            $alerts = $alerts->with('createdBy')->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
            $totalCount = $alerts->total();
            $total_pages = ceil($totalCount / $request->records_per_page);


            $returnArrData = [
                'alerts' => $alerts->all(),
                'page_no' => $request->page_no,
                'total_entries' => $totalCount,
                'total_pages' => $total_pages
            ];
            return returnSuccessResponse('Get alerts list successfully', $returnArrData);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function assignIndex(Request $request)
    {
        $rules = [
            'records_per_page' => 'required',
            'page_no' => 'required',
            'user_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        try {
            $user = User::findorFail($request->user_id);
            if (!$user) {
                return returnNotFoundResponse('Invalid user id.');
            }
            if ($user && $user->role_id != User::ROLE_SUB_ADMIN) {
                return returnNotFoundResponse('No sub admin found with given user id.');
            }
            $assignedIds = AssignedDevice::where('user_id', '!=', $request->user_id)->pluck('device_id')->toArray();
            $assignedDevices = AssignedDevice::where('user_id', $request->user_id)->pluck('device_id')->toArray();

            $devices = Device::whereNotIn('id', $assignedIds)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
            $totalCount = $devices->total();
            $total_pages = ceil($totalCount / $request->records_per_page);
            foreach ($devices as $device) {
                $assigned = false;
                if (in_array($device->id, $assignedDevices)) {
                    $assigned = true;
                }
                $device->assign = $assigned;
            }
            $returnArrData = [
                'devices' => $devices->all(),
                'page_no' => $request->page_no,
                'total_entries' => $totalCount,
                'total_pages' => $total_pages
            ];
            return returnSuccessResponse('Get all device list successfully', $returnArrData);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function assignDevice(Request $request)
    {
        $rules = [
            'user_id' => 'required',
            'id' => 'required',
            'state' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            if ($request->state == '1') {
                $assignedDevice = AssignedDevice::create([
                    'user_id' => $request->user_id,
                    'device_id' => $request->id,
                ]);
                $status = 'assigned';
            } else {
                $assignedDevice = AssignedDevice::where(['device_id' => $request->id, 'user_id' => $request->user_id])->delete();
                $status = 'un assigned';
            }
            return returnSuccessResponse('Device ' . $status . ' successfully ', []);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function overview(Request $request)
    {
        $rules = [
            'records_per_page' => 'required',
            'page_no' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        try {
            $bookings = TractorBooking::query();
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
                $deviceIds = multiDimToSingleDim($deviceIds);
                $bookings =  $bookings->whereIn('device_id', $deviceIds);
            }
            if ($request->search) {
                $device = Device::where('imei_no', $request->search)->first();
                $bookings =  $bookings->where('device_id', $device->id);
            }
            $bookings =  $bookings->with('device', 'createdBy', 'tractor')->orderBy('device_id', 'ASC')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
            $totalCount = $bookings->total();
            $total_pages = ceil($totalCount / $request->records_per_page);
            foreach ($bookings as $booking) {
                $kilometer = 0;
                $begin_time = $booking->date . ' 00:00:00';
                $end_time = $booking->date . ' 23:59:59';
                $imeis = $booking->device?->imei_no ? explode(',', $booking->device?->imei_no) : [];
                if ($imeis) {
                    $callApi = (new Jimi())->getDeviceMilage($imeis, $begin_time, $end_time);
                    $meter = $callApi['data'] ? $callApi['data'][0]['totalMileage'] : null;
                    $kilometer = $meter ? $meter / 1000 : 0;
                }
                $booking->kilometer = $kilometer;
            }
            $returnArrData = [
                'devices' => $bookings->all(),
                'page_no' => $request->page_no,
                'total_entries' => $totalCount,
                'total_pages' => $total_pages
            ];
            return returnSuccessResponse('Get overview list successfully ', $returnArrData);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function getDevices()
    {
        try {
            $gmtDate = gmdate('Y-m-d H:i:s');
            $userId = Auth::id();
            $roleId = Auth::user()->role_id;
            $responseData = [];

            // Base query for devices
            $query = Device::select('id', 'imei_no', 'device_modal', 'device_name', 'subscription_expiration', 'expiration_date', 'sim')
                ->whereNotNull('activation_time');

            // Role-based filtering
            if (in_array($roleId, [User::ROLE_ADMIN, User::ROLE_GOVERNMENT])) {
                $devices = $query->get();
            } elseif ($roleId == User::ROLE_SUB_ADMIN) {
                $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();
                $deviceIds = TractorGroup::whereIn('id', $assignedGroups)->pluck('device_ids')->flatten()->unique()->toArray();
                $devices = $query->whereIn('id', $deviceIds)->get();
            } elseif ($roleId == User::ROLE_FARMER) {
                $groupId = Tractor::where('farmer_id', $userId)->value('group_id');
                $group = TractorGroup::find($groupId);
                if (!$group) {
                    return  response()->json(['status' => false, 'message' => 'No devices found', 'data' => []]);
                }
                $deviceIds = json_decode($group->device_ids, true);
                $devices = $query->whereIn('id', $deviceIds)->get();
            } else {
                return  response()->json(['status' => false, 'message' => 'Unauthorized access', 'data' => []]);
            }

            if ($devices->isEmpty()) {
                return  response()->json(['status' => false, 'message' => 'No devices found', 'data' => []]);
            }

            // Fetch IMEIs and get API Data in batches
            $imeis = $devices->pluck('imei_no')->toArray();
            $apiData = [];
            foreach (array_chunk($imeis, 99) as $chunk) {
                $batchData = (new Jimi())->getDeviceLocation($chunk)['result'] ?? [];
                $apiData += array_column($batchData, null, 'imei');
            }

            foreach ($devices as $device) {
                if (!isset($apiData[$device->imei_no])) {
                    continue;
                }

                $tractor = Tractor::where([
                    'device_id' => $device->id
                ])->first();

                $device['apiData'] = $apiData[$device->imei_no];
                $device['tractor'] = $tractor;
                $device['user'] = User::where([
                    'id' => $tractor?->driver_id
                ])->first();
                $device['group'] = TractorGroup::where([
                    'id' => $tractor?->group_id
                ])->first();

                $diffInSeconds = strtotime($gmtDate) - strtotime($device['apiData']['hbTime']);
                $days = floor($diffInSeconds / 86400);
                $hours = floor(($diffInSeconds % 86400) / 3600);
                $minutes = floor($diffInSeconds / 60);
                $diff = "0 min";
                if ($days > 1) {
                    $diff = "{$days} day+";
                } elseif ($hours > 1) {
                    $diff = "{$hours} hr+";
                } elseif ($minutes > 1) {
                    $diff = "{$minutes} min";
                }

                $device['diff'] = $diff;
                $device['minutes'] = $minutes;

                $responseData[] = $device;
            }

            return returnSuccessResponse('Device list retrieved successfully.', $responseData);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
}
