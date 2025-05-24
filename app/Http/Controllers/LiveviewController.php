<?php

namespace App\Http\Controllers;

use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\DeviceGeoFence;
use App\Models\Jimi;
use App\Models\Tractor;
use App\Models\TractorGroup;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LiveviewController extends Controller
{
    public function index()
    {
        try {
            $date = date('Y-m-d');
            $userId = Auth::id();
            $roleId = Auth::user()->role_id;

            $groupsQuery = TractorGroup::query();

            if ($roleId == User::ROLE_SUB_ADMIN) {
                $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id');
                $groupsQuery->whereIn('id', $assignedGroups);
            }

            // Fetch groups and names in a single query
            $groups = $groupsQuery->latest('id')->get();
            $groupNameArray = $groupsQuery->pluck('name', 'id')->toArray();

            $allDevices = Device::whereNotNull('activation_time');
            if ($roleId == User::ROLE_SUB_ADMIN) {
                $deviceIds = TractorGroup::whereIn('id', $assignedGroups)
                    ->pluck('device_ids')
                    ->flatten()
                    ->toArray();
                $deviceIds = array_unique($deviceIds);
                $allDevices->whereIn('id', $deviceIds);
            }
            $allDevices = $allDevices->latest('id')->get();


            $state = Device::ALL_DEVICES;


            return view('live-view.index', compact('groups', 'allDevices', 'state', 'groupNameArray'));
        } catch (Exception $e) {
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        }
    }

    public function appendGroupDevices(Request $request)
    {
        try {
            $userId = Auth::id();
            $roleId = Auth::user()->role_id;

            // Get relevant group IDs
            $groupsQuery = TractorGroup::query();
            if ($roleId == User::ROLE_SUB_ADMIN) {
                $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();
                $groupsQuery->whereIn('id', $assignedGroups);
            }
            $groups = $groupsQuery->latest('id')->get();

            // Fetch all device IDs in one query
            $groupDeviceIds = $groups->pluck('device_ids')->map(function ($ids) {
                return json_decode($ids, true) ?? [];
            })->flatten()->unique()->toArray();

            $allDevices = Device::whereIn('id', $groupDeviceIds)->get()->keyBy('id');

            // Fetch all tractors in one query
            $tractors = Tractor::whereIn('device_id', array_unique($groupDeviceIds))
                ->get()
                ->keyBy(fn($tractor) => "{$tractor->device_id}-{$tractor->group_id}");

            // Stream the response
            return response()->stream(function () use ($groups, $allDevices, $tractors) {
                echo "data: " . json_encode(['start' => true]) . "\n\n"; // Signal start
                ob_flush();
                flush();

                foreach ($groups as $group) {
                    $deviceIds = $group->device_ids ? json_decode($group->device_ids, true) : [];
                    $deviceImeis = collect($deviceIds)->map(fn($id) => $allDevices[$id]->imei_no ?? null)->filter()->toArray();

                    // Split IMEIs into chunks for API request
                    $batchSize = 99;
                    $imeisChunks = array_chunk($deviceImeis, $batchSize);
                    $apiData = [];

                    foreach ($imeisChunks as $chunk) {
                        $apiResponse = (new Jimi())->getDeviceLocation($chunk)['result'] ?? [];
                        $apiData = array_merge($apiData, $apiResponse);
                    }

                    // Render and send HTML for this group immediately
                    if (!empty($apiData)) {
                        $view = view('live-view.append-group-device', compact('group', 'apiData', 'tractors'))->render();
                        $html = ['group_id' => $group->id, 'html' => $view];
                        echo "data: " . json_encode($html) . "\n\n"; // Send as Server-Sent Event (SSE)
                        ob_flush();
                        flush();
                    } else {
                        $view = '<div class="d-flex justify-content-between my-3">
                                <div class="d-flex gap-2">No Data Found</div>
                            </div>';
                        $html = ['group_id' => $group->id, 'html' => $view];
                        echo "data: " . json_encode($html) . "\n\n"; // Send as Server-Sent Event (SSE)
                        ob_flush();
                        flush();
                    }
                }

                echo "data: " . json_encode(['end' => true]) . "\n\n"; // Signal end
                ob_flush();
                flush();
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function markersData()
    {
        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $dateTime = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($dateTime));
        $userId = Auth::id();
        $roleId = Auth::user()->role_id;

        $query = Device::select('id', 'imei_no', 'device_modal', 'device_name', 'subscription_expiration', 'expiration_date', 'sim')->whereNotNull('activation_time');
        if ($roleId == User::ROLE_SUB_ADMIN) {
            $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();
            $deviceIds = TractorGroup::whereIn('id', $assignedGroups)
                ->pluck('device_ids')
                ->flatten()
                ->toArray();
            $deviceIds = array_unique($deviceIds);
            $query->whereIn('id', $deviceIds);
        }
        $devices = $query->get();
        $imeis = $query->pluck('imei_no')->toArray();

        $batchSize = 99;
        $imeisChunks = array_chunk($imeis, $batchSize);

        // Process each chunk and stream the result
        foreach ($imeisChunks as $chunk) {
            $apiData = (new Jimi())->getDeviceLocation($chunk)['result'] ?? [];
            $apiData = array_column($apiData, null, 'imei');

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

                $diffInSeconds = strtotime($gmt_date) - strtotime($device['apiData']['hbTime']);
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

                echo "data: " . json_encode(['device' => $device]) . "\n\n";
                ob_flush();
                flush();
            }
        }

        // Close the connection after streaming all data
        echo "data: " . json_encode(['end' => true]) . "\n\n";
        ob_flush();
        flush();
        exit;
    }

    public function currentDevice(Request $request)
    {
        $response['status'] = 'OK';

        $date = date('Y-m-d');
        $dateTime = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($dateTime));

        $devcieByImei = Device::where('imei_no', $request->imei)->first();
        if (!$devcieByImei) {
            $response['status'] = 'NOK';
            return $response;
        }

        $imeis =  explode(',', $devcieByImei->imei_no);
        $apiData = (new Jimi())->getDeviceLocation($imeis);
        if (count($apiData['result']) == 0) {
            $response['status'] = 'NOK';
            return $response;
        }

        $devcieByImei['apiData'] = $apiData['result'][0];
        $tractor = Tractor::where([
            'device_id' => $devcieByImei->id
        ])->first();

        $devcieByImei['apiData'] = $apiData['result'][0];
        $devcieByImei['tractor'] = $tractor;
        $devcieByImei['user'] = User::where([
            'id' => $tractor?->driver_id
        ])->first();
        $devcieByImei['group'] = TractorGroup::where([
            'id' => $tractor?->group_id
        ])->first();

        $diffInSeconds = strtotime($gmt_date) - strtotime($devcieByImei['apiData']['hbTime']);
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

        $devcieByImei['diff'] = $diff;
        $devcieByImei['minutes'] = $minutes;

        $geoFence = DeviceGeoFence::where(['imei' => $request->imei, 'state_id' => DeviceGeoFence::STATE_ACTIVE])->latest('id')->first();

        $response['fence'] = $geoFence;
        $response['device'] = $devcieByImei;

        return $response;
    }

    public function getTrackData(Request $request)
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
                            if ($output['code'] == 0 && isset($output['result']) && empty($output['result'])) {
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

    public function search(Request $request)
    {
        $search = null;
        $response['status'] = 'OK';
        if ($request->search) {
            $search = $request->search;
            $device = Device::whereNotNull('activation_time');
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
                $deviceIds = multiDimToSingleDim($deviceIds);
                $device = $device->whereIn('id', $deviceIds);
            }
            $device = $device->where('imei_no',  'LIKE', '%' . $search . '%')->get();
            $response['device'] = $device;
        }
        return $response;
    }

    public function getDeviceWithState(Request $request)
    {
        $state = $request->state;

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $dateTime = now();
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($dateTime));
        $userId = Auth::id();
        $roleId = Auth::user()->role_id;

        if ($state == Device::INACTIVE_DEVICES) {
            $devices = Device::whereNull('activation_time')->get();
            foreach ($devices as $device) {
                $tractor = Tractor::where('device_id', $device->id)->first();
                $device->tractor = $tractor; // Assign tractor relation dynamically
                $view = view('live-view.inactive-device-list', compact('device'))->render();
                $html = ['html' => $view];
                echo "data: " . json_encode($html) . "\n\n"; // Send as Server-Sent Event (SSE)
                ob_flush();
                flush();
            }
        } else {

            $query = Device::select('id', 'imei_no', 'device_modal', 'device_name', 'subscription_expiration', 'expiration_date', 'sim')->whereNotNull('activation_time');

            if ($roleId == User::ROLE_SUB_ADMIN) {
                $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();
                $deviceIds = TractorGroup::whereIn('id', $assignedGroups)
                    ->pluck('device_ids')
                    ->flatten()
                    ->toArray();
                $deviceIds = array_unique($deviceIds);
                $query->whereIn('id', $deviceIds);
            }

            $devices = $query->get();
            $imeis = $devices->pluck('imei_no')->toArray();

            $batchSize = 99;
            $imeisChunks = array_chunk($imeis, $batchSize);

            foreach ($imeisChunks as $chunk) {
                $apiData = (new Jimi())->getDeviceLocation($chunk)['result'] ?? [];
                $apiData = array_column($apiData, null, 'imei');

                foreach ($devices as $device) {
                    if (!isset($apiData[$device->imei_no])) {
                        continue;
                    }

                    $tractor = Tractor::where('device_id', $device->id)->first();
                    $device['apiData'] = $apiData[$device->imei_no];
                    $device['tractor'] = $tractor;

                    $diffInSeconds = strtotime($gmt_date) - strtotime($device['apiData']['hbTime']);
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

                    if ($state == Device::ONLINE_DEVICES && $minutes <= 8) {
                        $view = view('live-view.device-list', compact('device', 'state'))->render();
                        $html = ['html' => $view];
                        echo "data: " . json_encode($html) . "\n\n"; // Send as Server-Sent Event (SSE)
                        ob_flush();
                        flush();
                    } elseif ($state == Device::OFFLINE_DEVICES && $minutes > 8) {
                        $view = view('live-view.device-list', compact('device', 'state'))->render();
                        $html = ['html' => $view];
                        echo "data: " . json_encode($html) . "\n\n"; // Send as Server-Sent Event (SSE)
                        ob_flush();
                        flush();
                    }
                }
            }
        }

        // End of data
        echo "data: " . json_encode(['end' => true]) . "\n\n";
        ob_flush();
        flush();
        exit;
    }

    public function getDevicesCount()
    {
        $onlineCount = 0;
        $offlineCount = 0;
        $inactiveCount = 0;
        $movingCount = 0;
        $idleCount = 0;
        $dateTime = now();
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($dateTime));
        $userId = Auth::id();
        $roleId = Auth::user()->role_id;

        $query = Device::select('id', 'imei_no', 'device_modal', 'device_name', 'subscription_expiration', 'expiration_date', 'sim')->whereNotNull('activation_time');

        if ($roleId == User::ROLE_SUB_ADMIN) {
            $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();
            $deviceIds = TractorGroup::whereIn('id', $assignedGroups)
                ->pluck('device_ids')
                ->flatten()
                ->toArray();
            $deviceIds = array_unique($deviceIds);
            $query->whereIn('id', $deviceIds);
        }

        $devices = $query->get();
        $imeis = $devices->pluck('imei_no')->toArray();

        $batchSize = 99;
        $imeisChunks = array_chunk($imeis, $batchSize);

        foreach ($imeisChunks as $chunk) {
            $apiData = (new Jimi())->getDeviceLocation($chunk)['result'] ?? [];
            $apiData = array_column($apiData, null, 'imei');

            foreach ($devices as $device) {
                if (!isset($apiData[$device->imei_no])) {
                    continue;
                }

                $diffInSeconds = strtotime($gmt_date) - strtotime($apiData[$device->imei_no]['hbTime']);
                $minutes = floor($diffInSeconds / 60);
                if ($minutes <= 8) {
                    $onlineCount++;
                } else {
                    $offlineCount++;
                }

                if ($apiData[$device->imei_no]['status'] == 1 && $apiData[$device->imei_no]['accStatus'] == 1 && $apiData[$device->imei_no]['speed'] != 0) {
                    $movingCount++;
                } elseif ($apiData[$device->imei_no]['status'] == 1 && ($apiData[$device->imei_no]['speed'] == 0 || $apiData[$device->imei_no]['speed'] == null)) {
                    $idleCount++;
                }
            }
        }
        $inactiveCount = Device::whereNull('activation_time')->count();

        $data = [
            'onlineCount' => $onlineCount,
            'offlineCount' => $offlineCount,
            'inactiveCount' => $inactiveCount,
            'movingCount' => $movingCount,
            'idleCount' => $idleCount
        ];

        return response()->json(['data' => $data]);
    }

    public function getFilteredDevices(Request $request)
    {
        $type = $request->type;

        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $dateTime = now();
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($dateTime));
        $userId = Auth::id();
        $roleId = Auth::user()->role_id;

        $query = Device::select('id', 'imei_no', 'device_modal', 'device_name', 'subscription_expiration', 'expiration_date', 'sim')->whereNotNull('activation_time');

        if ($roleId == User::ROLE_SUB_ADMIN) {
            $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();
            $deviceIds = TractorGroup::whereIn('id', $assignedGroups)
                ->pluck('device_ids')
                ->flatten()
                ->toArray();
            $deviceIds = array_unique($deviceIds);
            $query->whereIn('id', $deviceIds);
        }

        $devices = $query->get();
        $imeis = $devices->pluck('imei_no')->toArray();

        $batchSize = 99;
        $imeisChunks = array_chunk($imeis, $batchSize);

        foreach ($imeisChunks as $chunk) {
            $apiData = (new Jimi())->getDeviceLocation($chunk)['result'] ?? [];
            $apiData = array_column($apiData, null, 'imei');

            foreach ($devices as $device) {
                if (!isset($apiData[$device->imei_no])) {
                    continue;
                }

                $tractor = Tractor::where('device_id', $device->id)->first();
                $device['apiData'] = $apiData[$device->imei_no];
                $device['tractor'] = $tractor;

                $diffInSeconds = strtotime($gmt_date) - strtotime($device['apiData']['hbTime']);
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

                if ($type == Device::MOVING_DEVICES && $apiData[$device->imei_no]['status'] == 1 && $apiData[$device->imei_no]['accStatus'] == 1 && $apiData[$device->imei_no]['speed'] != 0) {
                    $view = view('live-view.device-list', compact('device'))->render();
                    $html = ['html' => $view];
                    echo "data: " . json_encode($html) . "\n\n"; // Send as Server-Sent Event (SSE)
                    ob_flush();
                    flush();
                } elseif ($type == Device::IDLE_DEVICES && $apiData[$device->imei_no]['status'] == 1 && ($apiData[$device->imei_no]['speed'] == 0 || $apiData[$device->imei_no]['speed'] == null)) {
                    $view = view('live-view.device-list', compact('device'))->render();
                    $html = ['html' => $view];
                    echo "data: " . json_encode($html) . "\n\n"; // Send as Server-Sent Event (SSE)
                    ob_flush();
                    flush();
                }
            }
        }

        // End of data
        echo "data: " . json_encode(['end' => true]) . "\n\n";
        ob_flush();
        flush();
        exit;
    }

    public function updateGroup(Request $request)
    {
        $newGroupId = $request->new_group_id;

        $device = Device::where('imei_no', $request->imei)->first();
        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device not found'], 404);
        }

        $tractor = Tractor::where('device_id', $device->id)->first();
        if (!$tractor) {
            return response()->json(['success' => false, 'message' => 'Tractor not found'], 404);
        }

        $oldGroup = TractorGroup::where('id', $tractor->group_id)->first();
        $newGroup = TractorGroup::where('id', $newGroupId)->first();

        if (!$oldGroup || !$newGroup) {
            return response()->json(['success' => false, 'message' => 'Group not found'], 404);
        }

        $tractorId = $tractor->id;
        $deviceId = $device->id;
        $driverId = $tractor?->driver_id;

        // Decode old group data
        $oldDeviceIds = json_decode($oldGroup->device_ids, true) ?? [];
        $oldTractorIds = json_decode($oldGroup->tractor_ids, true) ?? [];
        $oldDriverIds = json_decode($oldGroup->farmer_ids, true) ?? [];

        // Remove IDs from old group
        $oldDeviceIds = array_values(array_diff($oldDeviceIds, [$deviceId]));
        $oldTractorIds = array_values(array_diff($oldTractorIds, [$tractorId]));
        $oldDriverIds = array_values(array_diff($oldDriverIds, [$driverId]));

        // Update old group
        $oldGroup->update([
            'device_ids' => json_encode($oldDeviceIds),
            'tractor_ids' => json_encode($oldTractorIds),
            'farmer_ids' => json_encode($oldDriverIds),
        ]);

        // Decode new group data
        $newDeviceIds = json_decode($newGroup->device_ids, true) ?? [];
        $newTractorIds = json_decode($newGroup->tractor_ids, true) ?? [];
        $newDriverIds = json_decode($newGroup->farmer_ids, true) ?? [];

        // Add IDs to new group
        if (!in_array($deviceId, $newDeviceIds)) {
            $newDeviceIds[] = $deviceId;
        }
        if (!in_array($tractorId, $newTractorIds)) {
            $newTractorIds[] = $tractorId;
        }
        if (!in_array($driverId, $newDriverIds)) {
            $newDriverIds[] = $driverId;
        }

        // Update new group
        $newGroup->update([
            'device_ids' => json_encode($newDeviceIds),
            'tractor_ids' => json_encode($newTractorIds),
            'farmer_ids' => json_encode($newDriverIds),
        ]);

        // Update tractor's group_id
        $tractor->update(['group_id' => $newGroupId]);

        return response()->json(['success' => true, 'message' => 'Group updated successfully']);
    }
}
