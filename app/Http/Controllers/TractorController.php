<?php

namespace App\Http\Controllers;

use App\Jobs\ExportReport;
use App\Jobs\ImportTractors;
use App\Models\AssignedDevice;
use App\Models\AssignedGroup;
use App\Models\AssignedTractor;
use App\Models\Device;
use App\Models\DeviceGeoFence;
use App\Models\Export;
use App\Models\Image;
use App\Models\Jimi;
use App\Models\Notification;
use App\Models\Tractor;
use App\Models\TractorBooking;
use App\Models\TractorGroup;
use App\Models\TaggingHistory;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Imports\TractorImport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class TractorController
 * @package App\Http\Controllers
 */
class TractorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $tractors = Tractor::latest('id');
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
            $tractorIds = multiDimToSingleDim($tractorIds);
            $tractors = $tractors->whereIn('id', $tractorIds);
        }
        $search =  null;
        if ($request->search) {
            $search = $request->search;
            $tractors->where(function ($query) use ($search) {
                $query->where('id_no', 'LIKE', '%' . $search . '%')
                    ->orWhere('model', 'LIKE', '%' . $search . '%')
                    ->orWhere('no_plate', 'LIKE', '%' . $search . '%')
                    ->orWhere('brand', 'LIKE', '%' . $search . '%');
            });
        }
        $tractors =  $tractors->paginate();

        $tractorList = Tractor::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $tractorList = $tractorList->whereIn('id', $tractorIds);
        }
        $tractorList = $tractorList->latest('id')->get();

        $importInfo = Export::where([
            'created_by' => Auth::id(),
            'type_id' => Export::TYPE_TRACTOR_IMPORT
        ])->first();

        $exportInfo = Export::Where([
            'created_by' => Auth::id(),
            'type_id' => Export::TYPE_TRACTOR
        ])->first();
        return view('tractor.index', compact('tractors', 'search', 'tractorList', 'importInfo', 'exportInfo'))
            ->with('i', (request()->input('page', 1) - 1) * $tractors->perPage());
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
        $tractor = new Tractor();
        $tractor->state_id = Tractor::STATE_ACTIVE;
        return view('tractor.create', compact('tractor'));
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
            'imei' => 'required|unique:tractors,imei',
            'no_plate' => 'required',
            'id_no' => 'required',
            'engine_no' => 'required',
            'fuel_consumption' => 'required',
            'brand' => 'required',
            'model' => 'required',
            'installation_time' => 'required',
            'installation_address' => 'required',
            'first_maintenance_hr' => 'nullable|numeric',
            'maintenance_kilometer' => 'nullable|numeric',
            'path' => 'required|array|min:1|max:5',
            'path.*' => 'image|mimes:jpeg,png,jpg',
        ], [
            'no_plate.required' => 'The number plate field is required.',
            'id_no.required' => 'The ID Number field is required.',
            'engine_no.required' => 'The engine number field is required.',
            'fuel_consumption.required' => 'The  Fuel/100km field is required.',
            'brand.required' => 'The tractor brand field is required.',
            'model.required' => 'The tractor model field is required.',
            'installation_time.required' => 'The  installation time field is required.',
            'installation_address.required' => 'The  installation address field is required.',
            'path.required' => 'File Attribute is required',
            'path.*.image' => 'File must be Image',
            'path.*.mimes' => 'File must be an Image(jpeg,png,jpg)',
        ], [
            'imei' => 'IMEI',
            'first_maintenance_hr' => 'first maintenacen hours',
            'maintenance_kilometer' => 'subsequent maintenacen hours',
        ]);

        $data = $request->all();
        if ($request->running_km) {
            $data['total_distance'] = $request->running_km;
        }
        $tractor = Tractor::create($data);
        if ($request->file('path')) {
            foreach ($request->file('path') as $imagefile) {
                $image = new Image();
                $path = $imagefile->store('image', 'public');
                $image->path = $path;
                $image->model_id = $tractor->id;
                $image->model_type = Tractor::class;
                $image->save();
            }
        }
        return redirect()->route('tractors.index')
            ->with('success', 'Tractor created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $tractor = Tractor::findorFail($id);
        $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
        $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
        $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
        $tractorIds = multiDimToSingleDim($tractorIds);
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]) && !in_array($tractor->id, $tractorIds)) {
            abort(403, 'You are not allowed to perform this action!!!');
        }
        $assignIds = AssignedTractor::where('user_id', Auth::id())->pluck('tractor_id')->toArray();
        $images = Image::where(['model_id' => $tractor->id, 'model_type' => Tractor::class])->get();
        $bookings = TractorBooking::where('tractor_id', $id)->orderBy('state_id', 'ASC')->orderBy('id', 'DESC')->paginate();
        if ($request->notification_id) {
            $notification = Notification::findorFail($request->notification_id);
            $notification->is_read = Notification::IS_READ;
            $notification->save();
        }

        return view('tractor.show', compact('tractor', 'images', 'bookings'));
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
        $tractor = Tractor::findorFail($id);
        return view('tractor.edit', compact('tractor'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Tractor $tractor
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tractor $tractor)
    {
        $request->validate([
            'imei' => 'required|unique:tractors,imei,' . $tractor->id,
            'no_plate' => 'required',
            'id_no' => 'required',
            'engine_no' => 'required',
            'fuel_consumption' => 'required',
            'brand' => 'required',
            'model' => 'required',
            'installation_time' => 'required',
            'installation_address' => 'required',
            'maintenance_kilometer' => 'required|numeric',
            'path' => 'array|min:1|max:5',
            'path.*' => 'image|mimes:jpeg,png,jpg',
        ], [
            'no_plate.required' => 'The number plate field is required.',
            'id_no.required' => 'The ID Number field is required.',
            'engine_no.required' => 'The engine number field is required.',
            'fuel_consumption.required' => 'The  Fuel/100km field is required.',
            'brand.required' => 'The tractor brand field is required.',
            'model.required' => 'The tractor model field is required.',
            'installation_time.required' => 'The  installation time field is required.',
            'installation_address.required' => 'The  installation address field is required.',
            'maintenance_kilometer.required' => 'The  maintenance kilometer field is required.',
            'path.*.image' => 'File must be Image',
            'path.*.mimes' => 'File must be an Image(jpeg,png,jpg)',
        ], [
            'imei' => 'IMEI',
            'first_maintenance_hr' => 'first maintenacen hours',
            'maintenance_kilometer' => 'subsequent maintenacen hours',
        ]);

        $data = $request->all();
        if ($request->running_km) {
            if ($tractor->total_distance) {
                $tractor->total_distance = $tractor->total_distance;
            } else {
                $tractor->total_distance = 0;
            }
            $data['total_distance'] = $tractor->total_distance + $request->running_km;
        }
        $original = $tractor->getOriginal();
        $tractor->update($data);
        if ($original['first_maintenance_hr'] != $tractor->first_maintenance_hr && $tractor->first_alert == Tractor::STATE_ACTIVE) {
            $tractor->first_alert = Tractor::STATE_INACTIVE;
            $tractor->save();
        }
        if ($request->file('path')) {
            $oldImages = Image::where(['model_id' => $tractor->id, 'model_type' => Tractor::class]);
            if ($oldImages) {
                $oldImages->delete();
            }
            foreach ($request->file('path') as $imagefile) {
                $image = new Image();
                $path = $imagefile->store('image', 'public');
                $image->path = $path;
                $image->model_id = $tractor->id;
                $image->model_type = Tractor::class;
                $image->save();
            }
        }
        return redirect()->route('tractors.show', [$tractor->id])
            ->with('success', 'Tractor updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $tractor = Tractor::findorFail($id);
        if ($tractor) {
            $groups = TractorGroup::get();
            foreach ($groups as $group) {
                $tractor_ids = !empty($group->tractor_ids) ? json_decode($group->tractor_ids, true) : [];
                if (!empty($tractor_ids) && in_array($tractor->id, $tractor_ids)) {
                    $key = array_search($tractor->id, $tractor_ids);
                    unset($tractor_ids[$key]);
                    $tractor_ids = array_values($tractor_ids);
                    $group->tractor_ids = json_encode($tractor_ids);
                    if (!$group->save()) {
                        return redirect()->back()->with('error', 'Tractor not deleted, please try again later.');
                    }
                    $tractor->delete();
                } else {
                    $tractor->delete();
                }
            }
        }
        return redirect()->back()->with('success', 'Tractor deleted successfully');
    }

    public function assignGroup(Request $request)
    {
        $tractorGroups = TractorGroup::paginate();
        $tractor_id = $request->id;

        return view('tractor.assign-group', compact('tractorGroups', 'tractor_id'))
            ->with('i', (request()->input('page', 1) - 1) * $tractorGroups->perPage());
    }

    public function tagging()
    {
        $history = TaggingHistory::latest('id')->paginate();

        return view('tractor.tagging', compact('history'))
            ->with('i', (request()->input('page', 1) - 1) * $history->perPage());
    }

    public function tagUnit(Request $request)
    {
        $request->validate([
            'group_id'   => 'required|exists:tractor_groups,id',
            'user_id'    => 'required',
            'tractor_id' => 'required',
            'device_id'  => 'required',
        ]);

        TractorGroup::where('id', '!=', $request->group_id)->chunk(50, function ($groups) use ($request) {
            foreach ($groups as $grp) {
                $device_ids  = $grp->device_ids ?? [];
                $tractor_ids = $grp->tractor_ids ?? [];
                $farmer_ids  = json_decode($grp->farmer_ids, true) ?? [];

                $device_ids  = array_values(array_diff($device_ids, [$request->device_id]));
                $tractor_ids = array_values(array_diff($tractor_ids, [$request->tractor_id]));
                $farmer_ids  = array_values(array_diff($farmer_ids, [$request->user_id]));

                $grp->device_ids  = $device_ids;
                $grp->tractor_ids = $tractor_ids;
                $grp->farmer_ids  = $farmer_ids;
                $grp->save();
            }
        });

        $group = TractorGroup::find($request->group_id);
        if ($group) {
            $device_ids  = $group->device_ids ?? [];
            $tractor_ids = $group->tractor_ids ?? [];
            $farmer_ids  = json_decode($group->farmer_ids, true) ?? [];

            if (!in_array($request->device_id, $device_ids)) {
                $device_ids[] = $request->device_id;
            }

            if (!in_array($request->tractor_id, $tractor_ids)) {
                $tractor_ids[] = $request->tractor_id;
            }

            if (!in_array($request->user_id, $farmer_ids)) {
                $farmer_ids[] = $request->user_id;
            }

            $group->device_ids  = $device_ids;
            $group->tractor_ids = $tractor_ids;
            $group->farmer_ids  = $farmer_ids;
            $group->save();

            TaggingHistory::create([
                'group_id' => $request->group_id,
                'user_id' => $request->user_id,
                'tractor_id' => $request->tractor_id,
                'device_id' => $request->device_id,
            ]);
        }

        return redirect()->back()->with('success', 'Tagging updated successfully.');
    }


    public function reassign(Request $request)
    {
        $tractor = Tractor::findorFail($request->id);
        if ($tractor) {
            $tractor->group_id = $request->group_id;
            $tractor->save();
        }
        return view('tractor.show', compact('tractor'));
    }

    public function jimiData()
    {
        $dateTime = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($dateTime));
        $userId = Auth::id();
        $roleId = Auth::user()->role_id;
        $query = Device::select('imei_no');
        if ($roleId == User::ROLE_SUB_ADMIN) {
            $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();
            $deviceIds = TractorGroup::whereIn('id', $assignedGroups)
                ->pluck('device_ids')
                ->flatten()
                ->toArray();
            $deviceIds = array_unique($deviceIds);
            $query->whereIn('id', $deviceIds);
        }

        $deviceImeis = $query->pluck('imei_no')->toArray();
        $imeis = array_values($deviceImeis);

        $batchSize = 100;
        $imeisChunks = array_chunk($imeis, $batchSize);

        // Assuming Jimi API supports asynchronous requests

        $apiData = array_merge(
            ...array_map(
                fn($chunk) => (new Jimi())->getDeviceLocation($chunk)['result'] ?? [],
                $imeisChunks
            )
        );
        $date = date('Y-m-d');
        $deviceData = [];
        $bookingArr = [];
        foreach ($apiData as $data) {
            if (!isset($data['imei'])) continue;

            $device = Device::where('imei_no', $data['imei'])->first();
            if (!$device) continue;

            $bookingData = TractorBooking::where([
                'device_id' => $device->id,
                'state_id' => TractorBooking::STATE_ACCEPTED,
                'date' => $date
            ])->orderBy('date', 'ASC')->first();

            $data['device'] = $device;
            $diffInSeconds = strtotime($gmt_date) - strtotime($data['hbTime']);
            $days = floor($diffInSeconds / 86400);
            $hours = floor(($diffInSeconds % 86400) / 3600);
            $minutes = floor(($diffInSeconds % 3600) / 60);
            $data['diff'] = "0 min";
            if ($days > 1) {
                $data['diff'] = "{$days} day+";
            } elseif ($hours > 1) {
                $data['diff'] = "{$hours} hr+";
            } elseif ($minutes > 1) {
                $data['diff'] = "{$minutes} min";
            }
            if ($bookingData) {
                $data['bookingData'] = $bookingData;
                $data['tractor'] = $bookingData->tractor;
                $data['createdBy'] = $bookingData->createdBy;

                $group = TractorGroup::whereJsonContains('farmer_ids', $bookingData->createdBy->id)
                    ->first(['name']);

                $data['group'] = $group ? $group->name : '';
            }

            $deviceData[$data['imei']] = $data;
            $bookingArr[] = $data;
        }
        $response = [
            'data' => $bookingArr
        ];
        return $response;
    }

    public function liveView()
    {
        try {
            $bookingArr = [];
            $date = date('Y-m-d');
            $userId = Auth::id();
            $roleId = Auth::user()->role_id;

            $groups = TractorGroup::query();
            if ($roleId == User::ROLE_SUB_ADMIN) {
                $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();
                $groups->whereIn('id', $assignedGroups);
            }
            $groups = $groups->latest('id')->get();

            $allDevices = Device::query();
            if ($roleId == User::ROLE_SUB_ADMIN) {
                $deviceIds = TractorGroup::whereIn('id', $assignedGroups)
                    ->pluck('device_ids')
                    ->flatten()
                    ->toArray();
                $deviceIds = array_unique($deviceIds);
                $allDevices->whereIn('id', $deviceIds);
            }
            $allDevices = $allDevices->latest('id')->get();

            return view('tractor.google-map', compact('bookingArr', 'groups', 'allDevices'));
        } catch (Exception $e) {
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        }
    }

    public function ajaxLiveView()
    {
        $response['status'] = 'OK';
        $bookingArr = $callAPiData = $apiData = [];

        $date = date('Y-m-d');

        $devices = Device::query();
        if (Auth::user()->role_id == User::ROLE_SUB_ADMIN) {
            $assignIds = AssignedDevice::where('user_id', Auth::id())->pluck('device_id')->toArray();
            $devices = $devices->whereIn('id', $assignIds);
        }
        $devices = $devices->pluck('imei_no')->toArray();
        $imeis = is_array($devices) ? $devices : explode(',', $devices);

        $batchSize = 100;
        $imeisChunks = array_chunk($imeis, $batchSize);

        foreach ($imeisChunks as $chunk) {
            $callApi = (new Jimi())->getDeviceLocation($chunk);
            $callAPiData[] = $callApi;
        }
        $apiData = array_reduce($callAPiData, function ($value, $resultArray) {
            return array_merge_recursive($value, is_array($resultArray) ? $resultArray : []);
        }, []);

        foreach ($devices as $key => $device) {
            $imei =  !empty($apiData['result'][$key]) ? $apiData['result'][$key]['imei'] : null;
            if ($imei) {
                $devcieImei = Device::where('imei_no', $imei)->first();
                $api_data =  !empty($apiData['result'][$key]) ? $apiData['result'][$key] : [];
                $bookingData = TractorBooking::with('tractor', 'device', 'createdBy')->where(['device_id' => $devcieImei->id, 'state_id' => TractorBooking::STATE_ACCEPTED, 'date' => $date])->orderBy('date', 'ASC')->first();
                if ($bookingData) {
                    $api_data['bookingData'] =  $bookingData;
                    $api_data['tractor'] =  $bookingData->tractor;
                    $api_data['created_by'] =  $bookingData->createdBy;
                    $groupsFarmerIds = TractorGroup::pluck('farmer_ids', 'id')->toArray();
                    $data = array_filter($groupsFarmerIds, function ($farmerIds) use ($bookingData) {
                        $farmersArray = json_decode($farmerIds, true);
                        return (in_array($bookingData->createdBy->id, $farmersArray));
                    });
                    $data = array_map(function ($key) {
                        return TractorGroup::where('id', $key)->select('name')->first();
                    }, array_keys($data));
                    $api_data['group'] = isset($data[0]) && $data[0]['name'] ? $data[0]['name'] : '';
                }

                $api_data['device'] =  $devcieImei;
                array_push($bookingArr, $api_data);
            }
        }
        $response['data'] = $bookingArr;

        return $response;
    }

    public function currentDeviceData(Request $request)
    {
        $response['status'] = 'OK';

        $date = date('Y-m-d');
        $dateTime = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($dateTime));

        $devcieByImei = Device::where('imei_no', $request->imei)->first();
        if (!$devcieByImei) {
            // $bookedDevices = TractorBooking::with('tractor', 'device', 'createdBy')->where(['device_id' => $devcieByImei->id, 'state_id' => TractorBooking::STATE_ACCEPTED])->latest('id')->first();
            $response['status'] = 'NOK';
            return $response;
        }

        $imeis =  explode(',', $devcieByImei->imei_no);
        $apiData = (new Jimi())->getDeviceLocation($imeis);
        if (count($apiData['result']) == 0) {
            $response['status'] = 'NOK';
            return $response;
        }
        $apiData = $apiData['result'][0];

        $bookedDevices = TractorBooking::where(['device_id' => $devcieByImei->id, 'state_id' => TractorBooking::STATE_ACCEPTED, 'date' => $date])->first();
        if ($bookedDevices) {
            $apiData['bookingData'] =  $bookedDevices;
            $apiData['tractor'] =  $bookedDevices->tractor;
            $apiData['created_by'] =  $bookedDevices->createdBy;
        }
        $apiData['device'] =  $devcieByImei;

        $diffInSeconds = strtotime($gmt_date) - strtotime($apiData['hbTime']);
        $days = floor($diffInSeconds / 86400);
        $hours = floor(($diffInSeconds % 86400) / 3600);
        $minutes = floor(($diffInSeconds % 3600) / 60);
        $apiData['diff'] = 0;
        if ($days > 1) {
            $apiData['diff'] = "{$days} day+";
        } elseif ($hours > 1) {
            $apiData['diff'] = "{$hours} hr+";
        } elseif ($minutes > 1) {
            $apiData['diff'] = "{$minutes} min";
        }

        $geoFence = DeviceGeoFence::where(['imei' => $request->imei, 'state_id' => DeviceGeoFence::STATE_ACTIVE])->latest('id')->first();
        $response['data'] = $apiData;
        $response['fence'] = $geoFence;

        return $response;
    }

    public function historyData(Request $request)
    {
        $latLngArr = $gpsTime = $gpsSpeed = $direction = [];
        $playbackControl = null;
        $booking = TractorBooking::findorFail($request->id);
        $begin_time = $booking->date . ' 00:00:00';
        $end_time = $booking->date . ' 23:59:59';
        $apiData = (new Jimi())->getDeviceTrackData($request->imei, $begin_time, $end_time);
        if ($apiData['result']) {
            foreach ($apiData['result'] as $data) {
                $latLngArr[] = ['lat' => $data['lat'], 'lng' => $data['lng']];
                $gpsTime[] = $data['gpsTime'];
                $gpsSpeed[] = $data['gpsSpeed'];
                $direction[] = $data['direction'];
            }
            $playbackControl = '<div class="overlay-map-section position-absolute bg-white p-3 w-100" id="playbackControlId' . $booking->id . '"><p class="text-end" >Speed: <span id="gpsSpeedId">' . $gpsSpeed[0] . '</span> km/h</p>
                <div class="d-flex align-items-center">
                    <div class="play-pause-btn me-2">
                    <button id="playButton' . $booking->id . '" data-id="' . $booking->id . '" data-imei="' . $booking?->device?->imei_no . '" data-action="play" onClick="playPauseDevice(this)"><i class="fa-solid fa-play"></i></button>
                    <button id="pauseButton' . $booking->id . '" class="d-none" data-id="' . $booking->id . '" data-imei="' . $booking?->device?->imei_no . '" data-action="pause" onClick="playPauseDevice(this)"><i class="fa-solid fa-pause"></i></button>
                    </div>
                    <div id="progress-bar-container">
                        <div id="progress-bar"></div>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <button id="replayButton' . $booking->id . '" class="d-flex align-items-center border-0 bg-transparent replay-btn" data-id="' . $booking->id . '" data-imei="' . $booking?->device?->imei_no . '" data-action="replay" onClick="playPauseDevice(this)"><i class="fa-solid fa-arrow-rotate-right me-1"></i> <span>Replay</span></button>
                    <p class="mb-0" id="gpsTimeId">' . $gpsTime[0] . '</p>
                </div>
            </div>';
        }
        $response['latlng'] = $latLngArr;
        $response['gpsTime'] = $gpsTime;
        $response['gpsSpeed'] = $gpsSpeed;
        $response['playbackControl'] = $playbackControl;
        $response['direction'] = $direction;

        return $response;
    }

    public function bookingData(Request $request)
    {
        $device = Device::findorFail($request->id);
        $bookings = TractorBooking::where(['device_id' => $device->id, 'state_id' => TractorBooking::STATE_ACCEPTED])->whereDate('date', '<=', date('Y-m-d'))->latest('date')->get();

        $html = [];

        foreach ($bookings as $key => $booking) {
            $user =  $booking?->createdBy?->name ?? $booking?->createdBy?->email;

            $html[] =
                '<a href="javascript:void(0);" class="text-secondary current-device history_submit px-3 py-2 d-block rounded mb-3" data-id="' . $booking->id . '" data-imei="' . $booking?->device?->imei_no . '" onClick="deviceTrackHistory(this)">' .
                '<div class="d-flex gap-2 my-1">' .
                '<div class="device-state-img bg-success flex-1">
                        <i class="fa-solid fa-tractor "></i>
                    </div>' .
                '<div class=" flex-grow-1"> ' .
                '<div class="d-flex justify-content-between gap-2 w-100"> ' .
                '<div> ' .
                '<p class="mb-0 user_name">' . $user . '</p>' .
                '<h6 class="device_imei_no mb-1">' . $booking?->device?->imei_no  . '</h6>' .
                '</div>' .
                '<div>' .
                '<p class="mb-0 booking_date">' . $booking?->date . '</p>' .
                '</div>' .
                '</div>' .
                '<p class="mb-0 tractor_name">' . $booking?->tractor?->id_no . ' (' . $booking?->tractor?->model . ')' . '</p>' .
                '<p class="mb-0 booking_purpose">' . $booking?->purpose . '</p>' .
                '</div></div></a>';
            // '<button id="playButton' . $booking->id . '" class="btn btn-primary playBtnClass d-none mb-3" data-id="' . $booking->id . '" data-imei="' . $booking?->device?->imei_no . '" data-action="play" onClick="playPauseDevice(this)">Start</button>' .
            // '<button id="pauseButton' . $booking->id . '" class="btn btn-primary pauseBtnClass d-none mb-3" data-id="' . $booking->id . '" data-imei="' . $booking?->device?->imei_no . '" data-action="pause" onClick="playPauseDevice(this)">Stop</button>'.
            // '<button id="replayButton' . $booking->id . '" class="btn btn-primary replayBtnClass d-none mb-3" data-id="' . $booking->id . '" data-imei="' . $booking?->device?->imei_no . '" data-action="replay" onClick="playPauseDevice(this)">Replay</button>';
        }
        $response['htmlArr'] = $html;
        return $response;
    }

    /**
     * @return \Illuminate\Support\Collection
     */

    public function export(Request $request)
    {
        if (!Tractor::count()) {
            return redirect()->route('tractors.index')->with('error', 'No data found.');
        }
        $tractorIds = $request->tractor_ids;
        $fileName = 'reports_' . date('Ymdhis') . '.csv';
        Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_TRACTOR])->latest('id')->delete();
        $export = Export::create([
            'file_name' => $fileName,
            'type_id' => Export::TYPE_TRACTOR
        ]);
        $assignIds = AssignedTractor::where('user_id', Auth::id())->pluck('tractor_id')->toArray();
        $tractors = Tractor::query();
        if ($tractorIds) {
            $tractors = $tractors->whereIn('id', $tractorIds);
        }
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
            $tractorIds = multiDimToSingleDim($tractorIds);
            $tractors = $tractors->whereIn('id', $tractorIds);
        }
        $tractors = $tractors->get();

        ExportReport::dispatch($tractors, $fileName);
        return redirect()->back()->with('success', 'Export added to queue. Please wait!!');
    }

    public function download(Request $request)
    {
        try {
            if ($request->filename) {
                $originalFileName = $request->filename;
            } else {
                $export = Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_TRACTOR])->latest('id')->first();
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
            Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_TRACTOR])->delete();
            exit;
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function checkFile()
    {
        $response['status'] = 'NOK';
        $export = Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_TRACTOR])->latest('id')->first();
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
        $assignedIds = AssignedTractor::where('user_id', '!=', $userId)->pluck('tractor_id')->toArray();
        $tractors = Tractor::whereNotIn('id', $assignedIds)->latest('id');

        $search =  null;
        if ($request->search) {
            $search = $request->search;
            $tractors = $tractors->where(function (Builder $query) use ($request) {
                return $query->where('id_no', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('model', 'LIKE', '%' . $request->search . '%')->orWhere('brand', 'LIKE', '%' . $request->search . '%');
            })->paginate();
        } else {
            $tractors =  $tractors->paginate();
        }

        $assignedTractors = AssignedTractor::where('user_id', $userId)->pluck('tractor_id')->toArray();

        return view('tractor.assignIndex', compact('tractors', 'userId', 'search', 'assignedTractors'))
            ->with('i', (request()->input('page', 1) - 1) * $tractors->perPage());
    }

    public function assignTractor(Request $request)
    {
        if ($request->state == '1') {
            $assignedTractor = AssignedTractor::create([
                'user_id' => $request->user_id,
                'tractor_id' => $request->id,
            ]);
        } else {
            $assignedTractor = AssignedTractor::where(['tractor_id' => $request->id, 'user_id' => $request->user_id])->delete();
        }
        return redirect()->back();
    }

    public function getTractData(Request $request)
    {
        try {

            if (empty($request->device_imei)) {
                $response['error'] = 'Device cannot be empty.';
                return $response;
            }
            $latLngArr = $gpsTime = $gpsSpeed = $direction = $deviceData = [];
            $playbackControl = $output = null;

            $imei = Device::where('id', $request->device_imei)->value('imei_no');

            $cacheKey = 'deviceData_' . $imei . '_' . $request->period . '_' . ($request->date_range ?? '');


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
                            if ($output['code'] === 0 && isset($output['result']) && empty($output['result'])) {
                                session()->flash('success', $output['message'] . '[' . $output['code'] . '] - No data found');
                            } elseif ($output['code'] === 0 && isset($output['result'])) {
                                $deviceData[] = $output['result'];
                            } else {
                                $response['error'] = $output['code'] . ' - ' . $output['message'];
                                return $response;
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
                        if ($output['code'] === 0 && isset($output['result']) && empty($output['result'])) {
                            session()->flash('success', $output['message'] . '[' . $output['code'] . '] - No data found');
                        } elseif ($output['code'] === 0 && isset($output['result']) && !empty($output['result'])) {
                            $deviceData = $output['result'];
                        } else {
                            $response['error'] = $output['code'] . ' - ' . $output['message'];
                            return $response;
                        }
                    }
                }
                Cache::put($cacheKey, $deviceData, now()->addMinutes(5));
            }

            if ($deviceData) {
                foreach ($deviceData as $data) {
                    $latLngArr[] = ['lat' => $data['lat'], 'lng' => $data['lng']];
                    $gpsTime[] = $data['gpsTime'];
                    $gpsSpeed[] = $data['gpsSpeed'];
                    $direction[] = $data['direction'];
                }
                $playbackControl = '<div class="overlay-map-section position-absolute bg-white p-3 w-100" id="playbackControlId"><p class="text-end" >Speed: <span id="gpsSpeedId">' . $gpsSpeed[0] . '</span> km/h</p>
                                        <div class="d-flex align-items-center">
                                            <div class="play-pause-btn me-2">
                                            <button id="playButton" data-imei="' . $imei . '" data-action="play" onClick="playPauseDevice(this)"><i class="fa-solid fa-play"></i></button>
                                            <button id="pauseButton" class="d-none" data-imei="' . $imei . '" data-action="pause" onClick="playPauseDevice(this)"><i class="fa-solid fa-pause"></i></button>
                                            </div>
                                            <div id="progress-bar-container">
                                                <div id="progress-bar"></div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-2">
                                            <button id="replayButton" class="d-flex align-items-center border-0 bg-transparent replay-btn" data-imei="' . $request->device_imei_no . '" data-action="replay" onClick="playPauseDevice(this)"><i class="fa-solid fa-arrow-rotate-right me-1"></i> <span>Replay</span></button>
                                            <p class="mb-0" id="gpsTimeId">' . $gpsTime[0] . '</p>
                                        </div>
                                    </div>';
                $response['latlng'] = $latLngArr;
                $response['gpsTime'] = $gpsTime;
                $response['gpsSpeed'] = $gpsSpeed;
                $response['playbackControl'] = $playbackControl;
                $response['direction'] = $direction;
            } else {
                $response['error'] = 'No data found';
                return $response;
            }
            return $response;
        } catch (Exception $e) {
            $response['error'] = 'An error occured: ' . $e->getMessage();
            return $response;
        }
    }

    public function import(Request $request)
    {
        $rules = [
            'fileInput' => 'required|mimes:csv,txt'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            $file = $request->file('fileInput');
            $extension = $file->getClientOriginalExtension();
            $fileName = uniqid('file_', true) . '.' . $extension;
            $filePath = $file->storeAs('import', $fileName, 'public');

            Export::where(['created_by' => Auth::id(), 'type_id' => Export::TYPE_TRACTOR_IMPORT])->latest('id')->delete();
            $export = Export::create([
                'file_name' => $file->getClientOriginalName(),
                'type_id' => Export::TYPE_TRACTOR_IMPORT,
            ]);
            ImportTractors::dispatch($filePath, Auth::id(), $export->id);

            return redirect()->back()->with('success', 'Import request has been added to the queue. Please check back shortly.');
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function closeProgress(Request $request)
    {
        try {
            $response['status'] = 'NOK';
            $export = Export::where(['created_by' => Auth::id(), 'type_id' => $request->type])->latest('id')->delete();
            if ($export) {
                $response['status'] = 'OK';
            }
            return $response;
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function ImportStatus(Request $request)
    {
        try {
            $response['status'] = 'NOK';
            $exportInfo = Export::where(['created_by' => Auth::id(), 'type_id' => $request->type])->latest('id')->first();
            if ($exportInfo) {
                $response['status'] = 'OK';
                $response['progress'] = $exportInfo->progress ?? 0;
            }
            return $response;
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function getFormat()
    {
        try {
            $filename = 'tractor_import_format.csv';
            $filePath = public_path('/assets/format/' . $filename);
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

            exit;
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

public function newImport(Request $request)
{
    $request->validate([
        'import_file' => 'required|file|mimes:xlsx,csv'
    ]);

    if ($request->hasFile('import_file')) {
        $file = $request->file('import_file');
    }

    if ($request->has('excel_data')) {
        $data = json_decode($request->input('excel_data'), true);

        if (empty($data)) {
            return redirect()->back()->with('error', 'No data found in the file.');
        }
        array_shift($data);

        foreach ($data as $row) {
            try {
                if (empty($row[8])) {
                    continue;
                }

                $imei = trim($row[8]);
                $tractorExists = Tractor::where('imei', $imei)->exists();
                $deviceExists = Device::where('imei_no', $imei)->exists();

                $tractor = null;
                $device = null;

                if (!$tractorExists) {
                    $tractor = Tractor::create([
                        'imei' => $imei,
                        'no_plate' => trim($row[24]) == 'N/A' ? '' : $row[24],
                        'dr_no' => trim($row[23]) == 'N/A' ? '' : $row[23],
                        'id_no' => trim($row[10]) == 'N/A' ? '' : $row[10],
                        'engine_no' => trim($row[25]) == 'N/A' ? '' : $row[25],
                        'fuel_consumption' => trim($row[12]) == 'N/A' ? '' : $row[12],
                        'brand' => trim($row[16]) == 'N/A' ? '' : $row[16],
                        'model' => trim($row[17]) == 'N/A' ? '' : $row[17],
                        'manufacture_date' => trim($row[18]) == 'N/A' ? null : $row[18],
                        'installation_time' => trim($row[19]) == 'N/A' ? null : $row[19],
                        'installation_address' => trim($row[20]) == 'N/A' ? '' : $row[20],
                        'max_speed' => null,
                        'maintenance_kilometer' => 100,
                        'first_maintenance_hr' => trim($row[13]) == 'N/A' ? '' : $row[13],
                        'running_km' => trim($row[15]) == 'N/A' ? '' : $row[15],
                        'total_distance' => null,
                        'chasis_no' => null,
                        'insurance_effect_date' => null,
                        'insurance_expire_date' => null,
                        'first_alert' => 0,
                        'last_alert_hours' => 0,
                        'dr_date' => trim($row[21]) == 'N/A' ? '' : $row[21],
                        'actual_delivery_date' => trim($row[22]) == 'N/A' ? '' : $row[22],
                        'front_loader_sn' => trim($row[26]) == 'N/A' ? '' : $row[26],
                        'rotary_tiller_sn' => trim($row[27]) == 'N/A' ? '' : $row[27],
                        'rotating_disc_plow_sn' => trim($row[28]) == 'N/A' ? '' : $row[28],
                        'state_id' => 1,
                        'type_id' => 0,
                        'created_by' => 1,
                    ]);
                }

                if (!$deviceExists && !empty($row[29])) {
                    $device = Device::create([
                        'imei_no' => $imei,
                        'device_model' => $row[30] ?? '',
                        'device_name' => $row[31] ?? '',
                        'sales_time' => $row[35] ?? null,
                        'mc_type_use_scope' => 'automobile',
                        'sim' => $row[32] ?? '',
                        'sim_iccid' => $row[33] ?? '',
                        'sim_registration_code' => $row[34] ?? '',
                        'mobile_data_load' => $row[36] ?? '',
                        'activation_time' => $row[35] ?? null,
                        'state_id' => 1,
                        'created_by' => 1
                    ]);
                }

                if ((!$tractorExists && $tractor) || (!$deviceExists && $device)) {
                    if (!empty($row[0])) {
                        $group = TractorGroup::where('name', 'LIKE', '%' . $row[0] . '%')->first();

                        if ($group) {
                            $device_ids = $group->device_ids ?? [];
                            $tractor_ids = $group->tractor_ids ?? [];

                            if ($device && !in_array($device->id, $device_ids)) {
                                $device_ids[] = $device->id;
                            }

                            if ($tractor && !in_array($tractor->id, $tractor_ids)) {
                                $tractor_ids[] = $tractor->id;
                            }

                            $group->update([
                                'device_ids' => $device_ids,
                                'tractor_ids' => $tractor_ids,
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Import error for row: ' . json_encode($row) . ' - Error: ' . $e->getMessage());
                continue;
            }
        }

        return redirect()->back()->with('success', 'Data imported successfully.');
    }

    return redirect()->back()->with('error', 'No data received.');
}

    //   public function newImport(Request $request)
    //     {
    //         $request->validate([
    //             'import_file' => 'required|file|mimes:xlsx,csv'
    //         ]);

    //         if (!$request->has('excel_data')) {
    //             return redirect()->back()->with('error', 'No data received.');
    //         }

    //         $data = json_decode($request->input('excel_data'), true);

    //         if (empty($data)) {
    //             return redirect()->back()->with('error', 'Empty Excel file.');
    //         }

    //         array_shift($data);

    //         $devicesToUpdate = [];
    //         $devicesToCreate = [];
    //         $imeiNumbers = array_column($data, 29);

    //         $existingDevices = Device::whereIn('imei_no', $imeiNumbers)
    //             ->pluck('imei_no')
    //             ->toArray();

    //         foreach ($data as $row) {
    //             $imei = $row[29];
    //             $deviceData = [
    //                 'device_model' => $row[30],
    //                 'device_name' => $row[31],
    //                 'sales_time' => $row[35],
    //                 'sim' => $row[32],
    //                 'sim_iccid' => $row[33],
    //                 'sim_registration_code' => $row[34],
    //                 'mobile_data_load' => $row[36],
    //                 'activation_time' => $row[35],
    //                 'state_id' => 1,
    //                 'created_by' => 1
    //             ];

    //             if (in_array($imei, $existingDevices)) {
    //                 $devicesToUpdate[$imei] = $deviceData;
    //             } else {
    //                 $deviceData['imei_no'] = $imei;
    //                 $deviceData['mc_type_use_scope'] = 'automobile';
    //                 $devicesToCreate[] = $deviceData;
    //             }
    //         }

    //         DB::beginTransaction();
    //         try {
    //             if (!empty($devicesToUpdate)) {
    //                 foreach ($devicesToUpdate as $imei => $data) {
    //                     Device::where('imei_no', $imei)->update($data);
    //                 }
    //             }

    //             if (!empty($devicesToCreate)) {
    //                 Device::insert($devicesToCreate);
    //             }

    //             DB::commit();
    //             return redirect()->back()->with('success',
    //                 'Data imported successfully. ' .
    //                 'Updated: ' . count($devicesToUpdate) . ', ' .
    //                 'Created: ' . count($devicesToCreate));
    //         } catch (\Exception $e) {
    //             DB::rollBack();
    //             return redirect()->back()->with('error',
    //                 'Import failed: ' . $e->getMessage());
    //         }
    //     }
}
