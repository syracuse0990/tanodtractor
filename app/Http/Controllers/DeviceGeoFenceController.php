<?php

namespace App\Http\Controllers;

use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\DeviceGeoFence;
use App\Models\Jimi;
use App\Models\Notification;
use App\Models\TractorGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class DeviceGeoFenceController
 * @package App\Http\Controllers
 */
class DeviceGeoFenceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $deviceGeoFences = DeviceGeoFence::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $deviceGeoFences = $deviceGeoFences->where('created_by', Auth::id());
        }
        $deviceGeoFences = $deviceGeoFences->latest('id')->paginate();

        return view('device-geo-fence.index', compact('deviceGeoFences'))
            ->with('i', (request()->input('page', 1) - 1) * $deviceGeoFences->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $deviceGeoFence = new DeviceGeoFence();
        $devices = Device::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $deviceIds = [];
            $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
            $deviceIds = multiDimToSingleDim($deviceIds);
            $devices = $devices->whereIn('id', $deviceIds);
        }
        $devices = $devices->latest('id')->get();
        $deviceImei = isset($request->imei) ? $request->imei : '';
        return view('device-geo-fence.create', compact('deviceGeoFence', 'devices', 'deviceImei'));
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
            'imei' => 'required',
            'fence_name' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'radius' => 'required',
            'date' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request) {
            $oldGeofence = DeviceGeoFence::where(['state_id' => DeviceGeoFence::STATE_ACTIVE, 'date' => $request->date])->whereIn('imei', $request->imei)->latest('id')->first();
            if ($oldGeofence) {
                if ($request->date == $oldGeofence->date) {
                    $validator->errors()->add(
                        'date',
                        'Geo fence already exists for this date.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $code = $apiMessage = $apiResult = $imeis = $successImeis = $successMessage = $successResults = $errorMessage = [];

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
            $deviceGeoFence = DeviceGeoFence::create($deviceGeoFenceData);
        }

        $response['hasErrors'] = 0;
        if (count($errorMessage)) {
            $response['hasErrors'] = 1;
            $response['errorMessage'] = $errorMessage;
        }
        $response['url'] = route('device-geo-fences.index');

        return $response;

        // return redirect()->route('device-geo-fences.index')->with('error', $errorMessage)->with('success', $successMessage);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $deviceGeoFence = DeviceGeoFence::findorFail($id);
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]) && $deviceGeoFence->created_by != Auth::id()) {
            abort(403, 'You are not allowed to perform this action!!!');
        }
        if ($request->notification_id) {
            $notification = Notification::findorFail($request->notification_id);
            $notification->is_read = Notification::IS_READ;
            $notification->save();
        }

        return view('device-geo-fence.show', compact('deviceGeoFence'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $deviceGeoFence = DeviceGeoFence::findorFail($id);
        $devices = Device::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $deviceIds = [];
            $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
            $deviceIds = multiDimToSingleDim($deviceIds);
            $devices = $devices->whereIn('id', $deviceIds);
        }
        $devices = $devices->latest('id')->get();

        return view('device-geo-fence.edit', compact('deviceGeoFence', 'devices'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  DeviceGeoFence $deviceGeoFence
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DeviceGeoFence $deviceGeoFence)
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
        $validator->after(function ($validator) use ($request) {
            $oldGeofence = DeviceGeoFence::where(['state_id' => DeviceGeoFence::STATE_ACTIVE, 'date' => $request->date])->whereIn('imei', $request->imei)->latest('id')->first();
            if ($oldGeofence) {

                if ($request->date < date('Y-m-d')) {
                    $validator->errors()->add(
                        'date',
                        "The date should be greater than or equal to today's date."
                    );
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
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

        $response['hasErrors'] = 0;
        if (count($errorMessage)) {
            $response['hasErrors'] = 1;
            $response['errorMessage'] = $errorMessage;
        }
        $response['url'] = route('device-geo-fences.index');

        return $response;
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id, Request $request)
    {
        $deviceGeoFence = DeviceGeoFence::findorFail($id);
        if ($deviceGeoFence->state_id != DeviceGeoFence::STATE_DELETED) {
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
        } else {
            $label = 'success';
            $message = 'Device geo fence deleted successfully.';
        }
        if ($request->is_delete) {
            $deviceGeoFence->delete();
        }

        return redirect()->route('device-geo-fences.index')
            ->with('error', $callDeleteApi)->with('success', $message);
    }

    public function deviceData(Request $request)
    {
        if (!$request->imei) {
            $response['status'] = 'NOK';
        }
        $imeis = explode(',', $request->imei);
        $apiCall = (new Jimi())->getDeviceLocation($imeis);
        $response['data'] = $apiCall['result']  ? $apiCall['result']['0'] : [];
        if (empty($apiCall['result'])) {
            $response['status'] = 'No Device';
        }
        return $response;
    }
}
