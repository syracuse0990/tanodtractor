<?php

namespace App\Http\Controllers;

use App\Helpers\CommonHelper;
use App\Helpers\NotificationHelper;
use App\Jobs\ExportDeviceData;
use App\Jobs\ExportOverview;
use App\Models\Device;
use App\Models\Jimi;
use App\Models\TractorGroup;
use App\Models\User;
use App\Models\WebhookDetails;
use App\Models\Alert;
use App\Models\AssignedDevice;
use App\Models\AssignedGroup;
use App\Models\DeviceGeoFence;
use App\Models\Export;
use App\Models\Notification;
use App\Models\Tractor;
use App\Models\TractorBooking;
use DateTime;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        $devices = Device::latest('id');
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
            $deviceIds = multiDimToSingleDim($deviceIds);
            $devices = $devices->whereIn('id', $deviceIds);
        }
        $search = null;
        if ($request->search) {
            $search = $request->search;
            $devices =  $devices->where(function (Builder $query) use ($request) {
                return $query->where('imei_no', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('device_name', 'LIKE', '%' . $request->search . '%')->orWhere('device_modal', 'LIKE', '%' . $request->search . '%');
            })->paginate();
        } else {
            $devices = $devices->paginate();
        }

        $deviceList = Device::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $deviceList = $deviceList->whereIn('id', $deviceIds);
        }
        $deviceList = $deviceList->latest('id')->get();
        return view('device.index', compact('devices', 'search', 'deviceList'))
            ->with('i', (request()->input('page', 1) - 1) * $devices->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            abort(403, 'You are not allowed to perform this action!!');
        }
        $device = new Device();
        $device->state_id = Device::STATE_ACTIVE;
        return view('device.create', compact('device'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'imei_no' => 'required|unique:devices,imei_no',
            'device_modal' => 'required',
            'device_name' => 'required',
            'subscription_expiration' => 'numeric',
            'sim' => 'required|numeric|unique:devices,sim'
        ], [
            'device_modal.required' => 'The device model field is required.',
            'imei_no.required' => 'The IMEI number field is required.',
            'imei_no.unique' => 'The IMEI number has already been taken.'
        ]);

        $device = Device::create($request->all());

        return redirect()->route('devices.index')
            ->with('success', 'Device created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        if ($request->notification_id) {
            $notification = Notification::findorFail($request->notification_id);
            $notification->is_read = Notification::IS_READ;
            $notification->save();
        }
        $device = Device::findorFail($id);

        $alerts = Alert::where(['imei' => $device->imei_no])->latest('id')->paginate();

        return view('device.show', compact('device', 'alerts'))->with('i', (request()->input('page', 1) - 1) * $alerts->perPage());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            abort(403, 'You are not allowed to perform this action!!');
        }
        $device = Device::findorFail($id);

        return view('device.edit', compact('device'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Device $device
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Device $device)
    {
        $request->validate([
            'imei_no' => 'required|unique:devices,imei_no,' . $device->id,
            'device_modal' => 'required',
            'device_name' => 'required',
            'subscription_expiration' => 'numeric',
            'sim' => 'required|numeric|unique:devices,sim,' . $device->id,

        ], [
            'device_modal.required' => 'The device model field is required.',
            'imei_no.required' => 'The IMEI number field is required.',
            'imei_no.unique' => 'The IMEI number has already been taken.'
        ]);

        $device->update($request->all());

        return redirect()->route('devices.index')
            ->with('success', 'Device updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $device = Device::findorFail($id);
        if ($device) {
            $groups = TractorGroup::get();
            foreach ($groups as $group) {
                $device_ids = $group->device_ids ? json_decode($group->device_ids, true) : [];
                if (!empty($device_ids) && in_array($device->id, $device_ids)) {
                    $key = array_search($device->id, $device_ids);
                    unset($device_ids[$key]);
                    $device_ids = array_values($device_ids);
                    $group->device_ids = json_encode($device_ids);
                    if (!$group->save()) {
                        return redirect()->back()->with('error', 'Device not deleted, please try again later.');
                    }
                    $device->delete();
                } else {
                    $device->delete();
                }
            }
        }

        return redirect()->back()->with('success', 'Device deleted successfully');
    }

    public function syncDevices()
    {
        $admin = User::where('role_id', User::ROLE_ADMIN)->select('id')->first();
        $apiData = (new Jimi())->getDeviceList();
        if (count($apiData['result'])) {
            foreach ($apiData['result'] as $key => $value) {
                $deviceExists = Device::where('imei_no', $value['imei'])->first();
                // if (is_null($value['activationTime'])) {
                //     // $deviceExists->delete();
                //     continue;
                // }
                if ($deviceExists) {
                    continue;
                }
                $device = new Device();
                $device->imei_no = $value['imei'];
                $device->device_modal = $value['mcType'];
                $device->device_name = $value['deviceName'];
                $device->sales_time = null;
                $device->subscription_expiration = null;
                $device->expiration_date = $value['expiration'];
                $device->mc_type = null;
                $device->mc_type_use_scope = $value['mcTypeUseScope'];
                $device->sim = $value['sim'];
                $device->activation_time = $value['activationTime'];
                $device->remark = $value['reMark'];
                $device->state_id = 1;
                $device->type_id = 0;
                $device->created_at = date('Y-m-d h:i:s');
                $device->updated_at = date('Y-m-d h:i:s');
                $device->created_by = $admin?->id;
                $device->save();
            }
        }
        return redirect()->back()->with('success', 'Device added successfully');
    }

    public function geoFenceWebhook(Request $request)
    {
        $data = $request->all();
        $webhookDetails = new WebhookDetails();
        $webhookDetails->data = json_encode($data);
        $webhookDetails->created_at = now();
        $webhookDetails->updated_at = now();
        if ($webhookDetails->save()) {
            // Insert Data in Alert Table..........

            $msgType = $data['msgType'];
            $alarmData = json_decode($data['data'], true);
            $alarmType = $alarmData['alarmType'];
            $alarmTime = $alarmData['alarmTime'];
            $imei = $alarmData['imei'];
            $alarmName = $alarmData['alarmName'];
            $deviceName = $alarmData['deviceName'];
            $lng = $alarmData['lng'];
            $lat = $alarmData['lat'];

            $device =  Device::where('imei_no', $imei)->first();
            $tractorBooking = TractorBooking::where(['device_id' => $device->id, 'state_id' => TractorBooking::STATE_ACCEPTED])->whereDate('date', date('Y-m-d', strtotime($alarmTime)))->first();
            $geoFenceId = DeviceGeoFence::where(['imei' => $imei, 'state_id' => DeviceGeoFence::STATE_ACTIVE])->whereDate('date', date('Y-m-d', strtotime($alarmTime)))->latest('id')->value('id');
            $notifyUsers = User::whereIn('role_id', [User::ROLE_ADMIN, User::ROLE_GOVERNMENT, User::ROLE_SUB_ADMIN])->get();
            $tractorName = $tractorBooking?->tractor ? $tractorBooking?->tractor?->id_no . ' (' . $tractorBooking?->tractor?->model . ')' : null;
            if ($alarmType == '1007') {
                $message = $device->imei_no . ' device has exited the Geofence.';
                if ($tractorBooking) {
                    $message = $tractorName . ' tractor has exited the Geofence.';

                    $notification = new Notification();
                    $notification->user_id = $tractorBooking?->created_by;
                    $notification->title = 'Exit Geofence';
                    $notification->message =  $message;
                    $notification->tractor_id = $tractorBooking?->tractor_id;
                    $notification->booking_id = $tractorBooking?->id;
                    $notification->device_id = $device->id;
                    $notification->is_read = Notification::IS_NOT_READ;
                    $notification->type_id = Notification::TYPE_EXIT_GEOFENCE;
                    $notification->geofence_id = $geoFenceId;
                    $notification->save();

                    $notificationdata = [
                        'body' => $tractorName . ' tractor has exited the geofence.',
                        'message' => 'Exit Geofence',
                        'notification_type' => Notification::TYPE_EXIT_GEOFENCE,
                        'user_id' => $tractorBooking?->created_by,
                        'notification_id' => $notification->id,
                        'geofence_id' => $geoFenceId,
                        'imei' => $imei
                    ];

                    $fcm_token = $tractorBooking->createdBy->fcm_token;
                    if ($fcm_token) {
                        NotificationHelper::sendPushNotification($fcm_token, $notificationdata);
                    }

                    $twilioResponse = CommonHelper::sendSms($tractorBooking?->createdBy?->phone_country . $tractorBooking?->createdBy?->phone, $message);
                    if ($twilioResponse['is_sent'] != true) {
                        Log::info('An error occurred:' . $twilioResponse['error']);
                    }
                }
                if (count($notifyUsers)) {
                    foreach ($notifyUsers as $admin) {
                        if (in_array($admin->role_id, [User::ROLE_SUB_ADMIN])) {
                            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                            $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
                            $deviceIds = multiDimToSingleDim($deviceIds);
                            if (!in_array($device->id, $deviceIds)) {
                                continue;
                            }
                        }
                        $adminNotification = new Notification();
                        $adminNotification->user_id = $admin->id;
                        $adminNotification->title = 'Exit Geofence';
                        $adminNotification->message = $message;
                        $adminNotification->tractor_id = $tractorBooking?->tractor_id;
                        $adminNotification->booking_id = $tractorBooking?->id;
                        $adminNotification->device_id = $device->id;
                        $adminNotification->is_read = Notification::IS_NOT_READ;
                        $adminNotification->type_id = Notification::TYPE_EXIT_GEOFENCE;
                        $adminNotification->geofence_id = $geoFenceId;
                        $adminNotification->save();

                        $adminNotificationdata = [
                            'body' => $message,
                            'message' => 'Exit Geofence',
                            'notification_type' => Notification::TYPE_EXIT_GEOFENCE,
                            'user_id' => $admin->id,
                            'notification_id' => $adminNotification->id,
                            'geofence_id' => $geoFenceId,
                            'imei' => $imei
                        ];


                        $admin_fcm_token = $admin->fcm_token;
                        if ($admin_fcm_token) {
                            NotificationHelper::sendPushNotification($admin_fcm_token, $adminNotificationdata);
                        }

                        $twilioResponse = CommonHelper::sendSms($admin?->phone_country . $admin?->phone, $message);
                        if ($twilioResponse['is_sent'] != true) {
                            Log::info('An error occurred:' . $twilioResponse['error']);
                        }
                    }
                }
            } elseif ($alarmType == '1006') {
                $message = $device->imei_no . ' device has entered the geofence.';
                if ($tractorBooking) {
                    $message = $tractorName . ' tractor has entered the geofence.';
                    $notification = new Notification();
                    $notification->user_id = $tractorBooking?->created_by;
                    $notification->title = 'Enter Geofence';
                    $notification->message = $message;
                    $notification->tractor_id = $tractorBooking?->tractor_id;
                    $notification->booking_id = $tractorBooking?->id;
                    $notification->device_id = $device->id;
                    $notification->is_read = Notification::IS_NOT_READ;
                    $notification->type_id = Notification::TYPE_ENTER_GEOFENCE;
                    $notification->geofence_id = $geoFenceId;
                    if ($notification->save()) {
                        $exitNotification = Notification::where(['device_id' => $notification->device_id, 'type_id' => Notification::TYPE_EXIT_GEOFENCE, 'is_closed' => Notification::IS_NOT_CLOSED, 'user_id' => $admin->id])->where('created_at', '<=', $notification->created_at)->latest('id')->first();
                        if ($exitNotification) {
                            $notification->exit_id = $exitNotification->id;
                            $notification->save();
                        }
                    }
                    $notificationdata = [
                        'body' => $message,
                        'message' => 'Enter Geofence',
                        'notification_type' => Notification::TYPE_ENTER_GEOFENCE,
                        'user_id' => $tractorBooking?->created_by,
                        'notification_id' => $notification->id,
                        'geofence_id' => $geoFenceId,
                        'imei' => $imei
                    ];

                    $fcm_token = $tractorBooking->createdBy?->fcm_token;
                    if ($fcm_token) {
                        NotificationHelper::sendPushNotification($fcm_token, $notificationdata);
                    }
                }
                if (count($notifyUsers)) {
                    foreach ($notifyUsers as $admin) {
                        if (in_array($admin->role_id, [User::ROLE_SUB_ADMIN])) {
                            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                            $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
                            $deviceIds = multiDimToSingleDim($deviceIds);
                            if (!in_array($device->id, $deviceIds)) {
                                continue;
                            }
                        }
                        $adminNotification = new Notification();
                        $adminNotification->user_id = $admin->id;
                        $adminNotification->title = 'Enter Geofence';
                        $adminNotification->message = $message;
                        $adminNotification->tractor_id = $tractorBooking?->tractor_id;
                        $adminNotification->booking_id = $tractorBooking?->id;
                        $adminNotification->device_id = $device->id;
                        $adminNotification->is_read = Notification::IS_NOT_READ;
                        $adminNotification->type_id = Notification::TYPE_ENTER_GEOFENCE;
                        $adminNotification->geofence_id = $geoFenceId;
                        if ($adminNotification->save()) {
                            $exitNotification = Notification::where(['device_id' => $adminNotification->device_id, 'type_id' => Notification::TYPE_EXIT_GEOFENCE, 'is_closed' => Notification::IS_NOT_CLOSED, 'user_id' => $admin->id])->where('created_at', '<=', $adminNotification->created_at)->latest('id')->first();
                            if ($exitNotification) {
                                $adminNotification->exit_id = $exitNotification->id;
                                $adminNotification->save();
                            }
                        }

                        $adminNotificationdata = [
                            'body' => $message,
                            'message' => 'Enter Geofence',
                            'notification_type' => Notification::TYPE_ENTER_GEOFENCE,
                            'user_id' => $admin->id,
                            'notification_id' => $adminNotification->id,
                            'geofence_id' => $geoFenceId,
                            'imei' => $imei
                        ];

                        $admin_fcm_token = $admin->fcm_token;
                        if ($admin_fcm_token) {
                            NotificationHelper::sendPushNotification($admin_fcm_token, $adminNotificationdata);
                        }
                    }
                }
            }

            $alert =  new Alert();
            $alert->user_id = $tractorBooking->created_by ?? null;
            $alert->alarm_type = $alarmType;
            $alert->alarm_time = $alarmTime;
            $alert->imei = $imei;
            $alert->alarm_name = $alarmName;
            $alert->device_name = $deviceName;
            $alert->latitude = $lat;
            $alert->longitude = $lng;
            $alert->details = $webhookDetails->data;
            $alert->webhook_id = $webhookDetails->id;
            $alert->save();
            \Log::info('Webhook Data inserted successfully ');
        }
    }

    public function export(Request $request)
    {
        if (!Device::count()) {
            return redirect()->route('devices.index')->with('error', 'No data found.');
        }
        $deviceIds = $request->device_ids;
        $fileName = 'motion_overview_' . date('Ymdhis') . '.csv';
        Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_DEVICE])->latest('id')->delete();
        $export = Export::create([
            'file_name' => $fileName,
            'type_id' => Export::TYPE_DEVICE
        ]);
        $devices = Device::query();
        if ($deviceIds) {
            $devices = $devices->whereIn('id', $deviceIds);
        }
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();

            $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
            $deviceIds = multiDimToSingleDim($deviceIds);
            $devices = $devices->whereIn('id', $deviceIds);
        }
        $devices = $devices->get();
        ExportDeviceData::dispatch($devices, $fileName);
        return redirect()->back()->with('success', 'Export added to queue. Please wait!!');
    }

    public function download(Request $request)
    {
        try {
            if ($request->filename) {
                $originalFileName = $request->filename;
            } else {
                $export = Export::where(['created_by' => Auth::user()->id, 'type_id' => $request->type_id])->latest('id')->first();
                $originalFileName = $export->file_name;
            }
            $filePath = storage_path('app/public/csv/' . $originalFileName);

            if (!file_exists($filePath)) {
                return redirect()->back()->with('error', 'File not found.');
            }
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($filePath));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));

            readfile($filePath);

            // Delete the file after download
            unlink($filePath);

            exit;
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function checkFile(Request $request)
    {
        $response['status'] = 'NOK';
        $export = Export::where(['created_by' => Auth::user()->id, 'type_id' => $request->type_id])->latest('id')->first();
        if ($export) {
            $fileName = $export->file_name;
            $filePath = storage_path('app/public/csv/' . $fileName);
            if (file_exists($filePath)) {
                $response['status'] = 'OK';
            }
        }
        return $response;
    }

    public function assignIndex(Request $request)
    {
        $userId = $request->id;
        $assignedIds = AssignedDevice::where('user_id', '!=', $userId)->pluck('device_id')->toArray();
        $devices = Device::whereNotIn('id', $assignedIds)->latest('id');
        $search = null;
        if ($request->search) {
            $search = $request->search;
            $devices =  $devices->where(function (Builder $query) use ($request) {
                return $query->where('imei_no', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('device_name', 'LIKE', '%' . $request->search . '%')->orWhere('device_modal', 'LIKE', '%' . $request->search . '%');
            })->paginate();
        } else {
            $devices = $devices->paginate();
        }

        $assignedDevices = AssignedDevice::where('user_id', $userId)->pluck('device_id')->toArray();

        return view('device.assignIndex', compact('devices', 'userId', 'search', 'assignedDevices'))
            ->with('i', (request()->input('page', 1) - 1) * $devices->perPage());
    }

    public function assignDevice(Request $request)
    {
        if ($request->state == '1') {
            $assignedDevice = AssignedDevice::create([
                'user_id' => $request->user_id,
                'device_id' => $request->id,
            ]);
        } else {
            $assignedDevice = AssignedDevice::where(['device_id' => $request->id, 'user_id' => $request->user_id])->delete();
        }
        return redirect()->back();
    }

    public function overview(Request $request)
    {
        $counts = $ids = $kms = $data = [];

        $bookingData = tractorBooking::get();
        foreach ($bookingData as $booking) {
            if (is_null($booking->kilometer)) {
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
                $booking->save();
            }
        }

        $bookingQuery = TractorBooking::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
            $deviceIds = multiDimToSingleDim($deviceIds);
            $bookingQuery =  $bookingQuery->whereIn('device_id', $deviceIds);
        }
        if ($request->search) {
            $device = Device::where('imei_no', $request->search)->first();
            $bookingQuery =  $bookingQuery->where('device_id', $device?->id);
        }
        $uniqueBookings = $bookingQuery->get()->groupBy('device_id')->toArray();

        $counts = array_map('count', array_values($uniqueBookings));
        if (!empty($request->sort_by)) {
            if ($request->sort_by == 'booking_date') {
                $bookingQuery->orderby('date', $request->sort_order ?? 'asc');
            } elseif ($request->sort_by == 'km') {
                $bookingQuery->orderby('kilometer', $request->sort_order ?? 'asc');
            }
        } else {
            $bookingQuery->orderBy('device_id', 'ASC');
        }
        $bookings =  $bookingQuery->paginate();

        // $data = [
        //     'total_devices' => round(count($uniqueBookings), 0),
        //     'trips' => round(array_sum($counts), 0),
        //     'total_kilometers' => round(array_sum($kms), 2),
        // ];
        // if (array_sum($counts)) {
        //     $data['trips_percantage'] = round((count($uniqueBookings) / array_sum($counts)) * 100, 0);
        // } else {
        //     $data['trips_percantage'] = 0;
        // }
        // if (Device::count()) {
        //     $data['total_device_percantage'] = round((count($uniqueBookings) / Device::count()) * 100, 0);
        // } else {
        //     $data['total_device_percantage'] = 0;
        // }
        // if (array_sum($kms)) {
        //     $data['kilometer_percantage'] = round((count($kms) / array_sum($kms)) * 100, 0);
        // } else {
        //     $data['kilometer_percantage'] = 0;
        // }
        return view('device.overview', compact('bookings', 'data'))->with('i', (request()->input('page', 1) - 1) * $bookings->perPage());
    }

    public function exportOverview(Request $request)
    {
        $fileName = 'overview_' . date('Ymdhis') . '.csv';
        Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_OVERVIEW])->latest('id')->delete();
        Export::create([
            'file_name' => $fileName,
            'type_id' => Export::TYPE_OVERVIEW
        ]);

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
        $bookings =  $bookings->orderBy('device_id', 'ASC')->get();
        ExportOverview::dispatch($bookings, $fileName);
        return redirect()->back()->with('success', 'Export added to queue. Please wait!!');
    }
}
