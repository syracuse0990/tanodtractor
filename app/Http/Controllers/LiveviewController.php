<?php

namespace App\Http\Controllers;

use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\DeviceGeoFence;
use App\Models\FarmerFeedback;
use App\Models\Jimi;
use App\Models\Maintenance;
use App\Models\Tractor;
use App\Models\TractorBooking;
use App\Models\TractorGroup;
use App\Models\TractorShare;
use App\Models\User;
use App\Services\MaintenanceReportService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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

            // Fetch all device IDs in one query.
            // Prefer actual tractor assignments because tractor_groups.device_ids can be stale.
            $groupIds = $groups->pluck('id')->toArray();
            $deviceIdsFromTractors = Tractor::whereIn('group_id', $groupIds)
                ->whereNotNull('device_id')
                ->pluck('device_id')
                ->toArray();

            $deviceIdsFromGroups = $groups->pluck('device_ids')->map(function ($ids) {
                return $ids ?? [];
            })->flatten()->toArray();

            $groupDeviceIds = array_values(array_unique(array_merge($deviceIdsFromGroups, $deviceIdsFromTractors)));

            $allDevices = Device::whereIn('id', $groupDeviceIds)->get()->keyBy('id');

            // Precompute tractor-assigned device IDs per group to avoid N+1 queries in loop.
            $tractorDevicesByGroup = Tractor::whereIn('group_id', $groupIds)
                ->whereNotNull('device_id')
                ->get(['group_id', 'device_id'])
                ->groupBy('group_id')
                ->map(function ($rows) {
                    return $rows->pluck('device_id')->unique()->values()->toArray();
                })
                ->toArray();

            // Pull live locations once (chunked) and reuse for all groups to reduce API load.
            $allImeis = $allDevices->pluck('imei_no')->filter()->unique()->values()->toArray();
            $apiDataByImei = [];
            $apiErrorMessage = null;
            foreach (array_chunk($allImeis, 99) as $chunk) {
                $apiResponse = (new Jimi())->getDeviceLocation($chunk);
                if (!is_array($apiResponse)) {
                    $apiErrorMessage = 'Unable to fetch live data. Please try again.';
                    continue;
                }

                $code = (int) ($apiResponse['code'] ?? 0);
                if ($code !== 0 && $apiErrorMessage === null) {
                    $apiErrorMessage = $apiResponse['message'] ?? 'Unable to fetch live data.';
                }

                $result = $apiResponse['result'] ?? [];
                foreach ($result as $item) {
                    $imei = $item['imei'] ?? null;
                    if (!empty($imei)) {
                        $apiDataByImei[$imei] = $item;
                    }
                }
            }

            // Stream the response
            return response()->stream(function () use ($groups, $allDevices, $tractorDevicesByGroup, $apiDataByImei, $apiErrorMessage) {
                echo "data: " . json_encode(['start' => true]) . "\n\n"; // Signal start
                ob_flush();
                flush();

                foreach ($groups as $group) {
                    $deviceIdsFromGroup = $group->device_ids ? $group->device_ids : [];
                    $deviceIdsFromTractor = $tractorDevicesByGroup[$group->id] ?? [];
                    $deviceIds = !empty($deviceIdsFromTractor)
                        ? $deviceIdsFromTractor
                        : $deviceIdsFromGroup;
                    $deviceIds = array_values(array_unique($deviceIds));

                    $deviceImeis = collect($deviceIds)->map(fn($id) => $allDevices[$id]->imei_no ?? null)->filter()->toArray();
                    $apiData = collect($deviceImeis)
                        ->map(fn($imei) => $apiDataByImei[$imei] ?? null)
                        ->filter()
                        ->values()
                        ->toArray();

                    // Render and send HTML for this group immediately
                    if (!empty($apiData)) {
                        $view = view('live-view.append-group-device', compact('group', 'apiData'))->render();
                        $html = ['group_id' => $group->id, 'html' => $view];
                        echo "data: " . json_encode($html) . "\n\n"; // Send as Server-Sent Event (SSE)
                        ob_flush();
                        flush();
                    } elseif (!empty($apiErrorMessage)) {
                        $view = '<div class="d-flex justify-content-between my-3">
                                <div class="d-flex gap-2 text-warning">' . e($apiErrorMessage) . '</div>
                            </div>';
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

    /**
     * Dashboard version of appendGroupDevices using MaintenanceReportService cache.
     * Same output format but reads from the 10-min cached device location map.
     */
    public function dashboardAppendGroupDevices(Request $request)
    {
        try {
            $userId = Auth::id();
            $roleId = Auth::user()->role_id;

            $groupsQuery = TractorGroup::query();
            if ($roleId == User::ROLE_SUB_ADMIN) {
                $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();
                $groupsQuery->whereIn('id', $assignedGroups);
            }
            $groups = $groupsQuery->latest('id')->get();

            $groupIds = $groups->pluck('id')->toArray();
            $deviceIdsFromTractors = Tractor::whereIn('group_id', $groupIds)
                ->whereNotNull('device_id')
                ->pluck('device_id')
                ->toArray();

            $deviceIdsFromGroups = $groups->pluck('device_ids')->map(function ($ids) {
                return $ids ?? [];
            })->flatten()->toArray();

            $groupDeviceIds = array_values(array_unique(array_merge($deviceIdsFromGroups, $deviceIdsFromTractors)));
            $allDevices = Device::whereIn('id', $groupDeviceIds)->get()->keyBy('id');

            $tractorDevicesByGroup = Tractor::whereIn('group_id', $groupIds)
                ->whereNotNull('device_id')
                ->get(['group_id', 'device_id'])
                ->groupBy('group_id')
                ->map(function ($rows) {
                    return $rows->pluck('device_id')->unique()->values()->toArray();
                })
                ->toArray();

            // Use cache instead of fresh API calls
            $apiDataByImei = (new MaintenanceReportService())->getDeviceLocationMap();

            return response()->stream(function () use ($groups, $allDevices, $tractorDevicesByGroup, $apiDataByImei) {
                echo "data: " . json_encode(['start' => true]) . "\n\n";
                ob_flush();
                flush();

                foreach ($groups as $group) {
                    $deviceIdsFromGroup = $group->device_ids ? $group->device_ids : [];
                    $deviceIdsFromTractor = $tractorDevicesByGroup[$group->id] ?? [];
                    $deviceIds = !empty($deviceIdsFromTractor)
                        ? $deviceIdsFromTractor
                        : $deviceIdsFromGroup;
                    $deviceIds = array_values(array_unique($deviceIds));

                    $deviceImeis = collect($deviceIds)->map(fn($id) => $allDevices[$id]->imei_no ?? null)->filter()->toArray();
                    $apiData = collect($deviceImeis)
                        ->map(fn($imei) => $apiDataByImei[$imei] ?? null)
                        ->filter()
                        ->values()
                        ->toArray();

                    if (!empty($apiData)) {
                        $view = view('live-view.append-group-device', compact('group', 'apiData'))->render();
                    } else {
                        $view = '<div class="d-flex justify-content-between my-3">
                                <div class="d-flex gap-2">No Data Found</div>
                            </div>';
                    }

                    echo "data: " . json_encode(['group_id' => $group->id, 'html' => $view]) . "\n\n";
                    ob_flush();
                    flush();
                }

                echo "data: " . json_encode(['end' => true]) . "\n\n";
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

    /**
     * SSE endpoint for dashboard map markers using MaintenanceReportService cache.
     * Same output format as markersData() but reads from the 10-min cached
     * device location map instead of making fresh Jimi API calls.
     */
    public function dashboardMarkersData()
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $gmt_date = gmdate('Y-m-d H:i:s');
        $userId = Auth::id();
        $roleId = Auth::user()->role_id;

        // Get cached device location data from MaintenanceReportService
        $apiDataByImei = (new MaintenanceReportService())->getDeviceLocationMap();

        // Build the same Device query as markersData() for DB enrichment
        $query = Device::select('id', 'imei_no', 'device_modal', 'device_name', 'subscription_expiration', 'expiration_date', 'sim')
            ->whereNotNull('activation_time');

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

        foreach ($devices as $device) {
            if (!isset($apiDataByImei[$device->imei_no])) {
                continue;
            }

            $tractor = Tractor::where('device_id', $device->id)->first();

            $device['apiData'] = $apiDataByImei[$device->imei_no];
            $device['tractor'] = $tractor;
            $device['user'] = User::where('id', $tractor?->driver_id)->first();
            $device['group'] = TractorGroup::where('id', $tractor?->group_id)->first();

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

        echo "data: " . json_encode(['end' => true]) . "\n\n";
        ob_flush();
        flush();
        exit;
    }

    /**
     * Dashboard version of getDevicesCount using MaintenanceReportService cache.
     */
    public function dashboardGetDevicesCount()
    {
        $gmt_date = gmdate('Y-m-d H:i:s');
        $userId = Auth::id();
        $roleId = Auth::user()->role_id;

        $apiDataByImei = (new MaintenanceReportService())->getDeviceLocationMap();

        $query = Device::select('id', 'imei_no')
            ->whereNotNull('activation_time');

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

        $onlineCount = 0;
        $offlineCount = 0;
        $movingCount = 0;
        $idleCount = 0;

        foreach ($devices as $device) {
            if (!isset($apiDataByImei[$device->imei_no])) {
                continue;
            }

            $apiDevice = $apiDataByImei[$device->imei_no];
            $diffInSeconds = strtotime($gmt_date) - strtotime($apiDevice['hbTime'] ?? $gmt_date);
            $minutes = floor($diffInSeconds / 60);

            if ($minutes <= 8) {
                $onlineCount++;
            } else {
                $offlineCount++;
            }

            if (($apiDevice['status'] ?? 0) == 1 && ($apiDevice['accStatus'] ?? 0) == 1 && !empty($apiDevice['speed'])) {
                $movingCount++;
            } elseif (($apiDevice['status'] ?? 0) == 1 && (empty($apiDevice['speed']) || $apiDevice['speed'] == 0)) {
                $idleCount++;
            }
        }

        $inactiveCount = Device::whereNull('activation_time')->count();

        return response()->json([
            'data' => [
                'onlineCount' => $onlineCount,
                'offlineCount' => $offlineCount,
                'inactiveCount' => $inactiveCount,
                'movingCount' => $movingCount,
                'idleCount' => $idleCount,
            ],
        ]);
    }

    public function dashboardStats(Request $request)
    {
        $userId = Auth::id();
        $roleId = Auth::user()->role_id;
        $serial = trim((string) $request->get('serial', ''));
        $groupId = $request->get('group_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $tractorQuery = Tractor::query();

        if ($roleId == User::ROLE_SUB_ADMIN) {
            $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();
            $tractorQuery->whereIn('group_id', $assignedGroups);
        }

        if (!empty($groupId)) {
            $tractorQuery->where('group_id', $groupId);
        }

        if (!empty($startDate)) {
            $tractorQuery->whereDate('created_at', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $tractorQuery->whereDate('created_at', '<=', $endDate);
        }

        $tractorIds = (clone $tractorQuery)->pluck('id')->toArray();

        $totalTractors = count($tractorIds);

        $bookedTractorsQuery = TractorBooking::where('state_id', TractorBooking::STATE_ACTIVE);
        if (!empty($tractorIds)) {
            $bookedTractorsQuery->whereIn('tractor_id', $tractorIds);
        } else {
            $bookedTractorsQuery->whereRaw('1 = 0');
        }
        if (!empty($startDate)) {
            $bookedTractorsQuery->whereDate('created_at', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $bookedTractorsQuery->whereDate('created_at', '<=', $endDate);
        }
        $bookedTractors = $bookedTractorsQuery->count();

        $pmsTractorsQuery = Maintenance::whereIn('state_id', [
            Maintenance::STATE_DOCUMENTATION,
            Maintenance::STATE_FILLED,
            Maintenance::STATE_INPROGRESS,
        ]);
        if (!empty($tractorIds)) {
            $pmsTractorsQuery->whereIn('tractor_ids', $tractorIds);
        } else {
            $pmsTractorsQuery->whereRaw('1 = 0');
        }
        if (!empty($startDate)) {
            $pmsTractorsQuery->whereDate('maintenance_date', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $pmsTractorsQuery->whereDate('maintenance_date', '<=', $endDate);
        }
        $pmsTractors = $pmsTractorsQuery->count();

        $groupsQuery = TractorGroup::query();
        if ($roleId == User::ROLE_SUB_ADMIN) {
            $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();
            $groupsQuery->whereIn('id', $assignedGroups);
        }
        if (!empty($groupId)) {
            $groupsQuery->where('id', $groupId);
        }
        if ($serial !== '') {
            if (!empty($tractorIds)) {
                $groupIdsFromTractors = Tractor::whereIn('id', $tractorIds)->whereNotNull('group_id')->pluck('group_id')->unique()->toArray();
                if (!empty($groupIdsFromTractors)) {
                    $groupsQuery->whereIn('id', $groupIdsFromTractors);
                } else {
                    $groupsQuery->whereRaw('1 = 0');
                }
            } else {
                $groupsQuery->whereRaw('1 = 0');
            }
        }
        if (!empty($startDate)) {
            $groupsQuery->whereDate('created_at', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $groupsQuery->whereDate('created_at', '<=', $endDate);
        }
        $groupsCount = $groupsQuery->count();

        $feedbackQuery = FarmerFeedback::query();
        if (!empty($tractorIds)) {
            $feedbackQuery->whereIn('tractor_id', $tractorIds);
        } else {
            $feedbackQuery->whereRaw('1 = 0');
        }
        if (!empty($startDate)) {
            $feedbackQuery->whereDate('created_at', '>=', $startDate);
        }
        if (!empty($endDate)) {
            $feedbackQuery->whereDate('created_at', '<=', $endDate);
        }
        $feedbackCount = $feedbackQuery->count();

        // Use MaintenanceReportService (cached device location API) for total/online/offline/PMS counts.
        $totalTractors = 0;
        $onlineCount = 0;
        $offlineCount = 0;
        $inactiveCount = 0;
        $pmsTractors = 0;

        $devicesForCounts = Device::query();
        if ($roleId == User::ROLE_SUB_ADMIN) {
            $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();
            $allowedDeviceIds = Tractor::whereIn('group_id', $assignedGroups)
                ->whereNotNull('device_id')
                ->pluck('device_id')
                ->toArray();
            $allowedDeviceIds = array_unique($allowedDeviceIds);
            $devicesForCounts->whereIn('id', $allowedDeviceIds);
        }

        if (!empty($groupId)) {
            $groupDeviceIds = Tractor::where('group_id', $groupId)
                ->whereNotNull('device_id')
                ->pluck('device_id')
                ->toArray();
            $devicesForCounts->whereIn('id', $groupDeviceIds);
        }

        if ($serial !== '') {
            $serialDeviceIdsFromTractors = Tractor::where(function ($q) use ($serial) {
                $q->where('id_no', 'LIKE', "%{$serial}%")
                    ->orWhere('no_plate', 'LIKE', "%{$serial}%")
                    ->orWhere('imei', 'LIKE', "%{$serial}%");
            })->whereNotNull('device_id')->pluck('device_id')->toArray();

            $devicesForCounts->where(function ($q) use ($serial, $serialDeviceIdsFromTractors) {
                $q->where('imei_no', 'LIKE', "%{$serial}%")
                    ->orWhere('device_name', 'LIKE', "%{$serial}%");
                if (!empty($serialDeviceIdsFromTractors)) {
                    $q->orWhereIn('id', $serialDeviceIdsFromTractors);
                }
            });
        }

        $filteredDeviceIds = (clone $devicesForCounts)->pluck('id')->toArray();

        if (!empty($filteredDeviceIds)) {
            // Inactive = devices that have never been activated
            $inactiveCount = Device::whereIn('id', $filteredDeviceIds)
                ->whereNull('activation_time')
                ->count();

            // Collect IMEIs of activated devices for the API-based status check
            $imeis = Device::whereIn('id', $filteredDeviceIds)
                ->whereNotNull('activation_time')
                ->pluck('imei_no')
                ->filter()
                ->values()
                ->toArray();

            if (!empty($imeis)) {
                $maintenanceService = new MaintenanceReportService();
                $statusCounts = $maintenanceService->getDeviceStatusCounts($imeis);
                $onlineCount  = $statusCounts['online'];
                $offlineCount = $statusCounts['offline'];
                $pmsTractors   = $maintenanceService->getPmsCount($imeis, true);
            }
        }

        $totalTractors = $onlineCount + $offlineCount + $inactiveCount;

        return response()->json([
            'data' => [
                'totalTractors' => $totalTractors,
                'onlineCount' => $onlineCount,
                'offlineCount' => $offlineCount,
                'inactiveCount' => $inactiveCount,
                'bookedTractors' => $bookedTractors,
                'pmsTractors' => $pmsTractors,
                'groupsCount' => $groupsCount,
                'feedbackCount' => $feedbackCount,
            ],
        ]);
    }

    /**
     * Dashboard cached version of currentDevice().
     * Uses MaintenanceReportService cache instead of fresh Jimi API call.
     */
    public function dashboardCurrentDevice(Request $request)
    {
        $response['status'] = 'OK';

        $dateTime = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($dateTime));

        $devcieByImei = Device::where('imei_no', $request->imei)->first();
        if (!$devcieByImei) {
            $response['status'] = 'NOK';
            return $response;
        }

        // Use cached device location from MaintenanceReportService
        $maintenanceService = new MaintenanceReportService();
        $deviceMap = $maintenanceService->getDeviceLocationMap();

        if (!isset($deviceMap[$devcieByImei->imei_no])) {
            $response['status'] = 'NOK';
            return $response;
        }

        $apiData = $deviceMap[$devcieByImei->imei_no];

        $devcieByImei['apiData'] = $apiData;
        $tractor = Tractor::where([
            'device_id' => $devcieByImei->id
        ])->first();

        $devcieByImei['tractor'] = $tractor;
        $devcieByImei['user'] = User::where([
            'id' => $tractor?->driver_id
        ])->first();
        $devcieByImei['group'] = TractorGroup::where([
            'id' => $tractor?->group_id
        ])->first();

        $diffInSeconds = strtotime($gmt_date) - strtotime($apiData['hbTime']);
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

    /**
     * Dashboard cached version of getDeviceWithState().
     * Uses MaintenanceReportService cache instead of fresh Jimi API calls.
     */
    public function dashboardGetDeviceWithState(Request $request)
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
                $device->tractor = $tractor;
                $view = view('live-view.inactive-device-list', compact('device'))->render();
                $html = ['html' => $view];
                echo "data: " . json_encode($html) . "\n\n";
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

            // Use cached device locations from MaintenanceReportService
            $maintenanceService = new MaintenanceReportService();
            $deviceMap = $maintenanceService->getDeviceLocationMap();

            foreach ($devices as $device) {
                if (!isset($deviceMap[$device->imei_no])) {
                    continue;
                }

                $tractor = Tractor::where('device_id', $device->id)->first();
                $device['apiData'] = $deviceMap[$device->imei_no];
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
                    echo "data: " . json_encode($html) . "\n\n";
                    ob_flush();
                    flush();
                } elseif ($state == Device::OFFLINE_DEVICES && $minutes > 8) {
                    $view = view('live-view.device-list', compact('device', 'state'))->render();
                    $html = ['html' => $view];
                    echo "data: " . json_encode($html) . "\n\n";
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

    /**
     * Dashboard cached version of getFilteredDevices().
     * Uses MaintenanceReportService cache instead of fresh Jimi API calls.
     */
    public function dashboardGetFilteredDevices(Request $request)
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

        // Use cached device locations from MaintenanceReportService
        $maintenanceService = new MaintenanceReportService();
        $deviceMap = $maintenanceService->getDeviceLocationMap();

        foreach ($devices as $device) {
            if (!isset($deviceMap[$device->imei_no])) {
                continue;
            }

            $tractor = Tractor::where('device_id', $device->id)->first();
            $device['apiData'] = $deviceMap[$device->imei_no];
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

            $imei = $device->imei_no;
            if ($type == Device::MOVING_DEVICES && $deviceMap[$imei]['status'] == 1 && $deviceMap[$imei]['accStatus'] == 1 && $deviceMap[$imei]['speed'] != 0) {
                $view = view('live-view.device-list', compact('device'))->render();
                $html = ['html' => $view];
                echo "data: " . json_encode($html) . "\n\n";
                ob_flush();
                flush();
            } elseif ($type == Device::IDLE_DEVICES && $deviceMap[$imei]['status'] == 1 && ($deviceMap[$imei]['speed'] == 0 || $deviceMap[$imei]['speed'] == null)) {
                $view = view('live-view.device-list', compact('device'))->render();
                $html = ['html' => $view];
                echo "data: " . json_encode($html) . "\n\n";
                ob_flush();
                flush();
            }
        }

        // End of data
        echo "data: " . json_encode(['end' => true]) . "\n\n";
        ob_flush();
        flush();
        exit;
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

    /**
     * Create a shareable link for a device (valid for 1 hour).
     */
    public function createShareLink(Request $request)
    {
        try {
            $imei = $request->input('imei');
            if (empty($imei)) {
                return response()->json(['error' => 'IMEI is required.'], 422);
            }

            $device = Device::where('imei_no', $imei)->first();
            if (!$device) {
                return response()->json(['error' => 'Device not found.'], 404);
            }

            $token = Str::random(48);

            $share = TractorShare::create([
                'token'       => $token,
                'imei'        => $imei,
                'device_name' => $device->device_name,
                'created_by'  => Auth::id(),
                'expires_at'  => now()->addHour(),
            ]);

            $url = url('/share/' . $token);

            return response()->json([
                'success' => true,
                'url'     => $url,
                'expires' => $share->expires_at->toIso8601String(),
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to create share link: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Public share page — no auth required.
     */
    public function publicShare($token)
    {
        $share = TractorShare::where('token', $token)->first();

        if (!$share) {
            abort(404, 'Share link not found.');
        }

        if ($share->isExpired()) {
            return view('share.expired');
        }

        return view('share.show', [
            'share' => $share,
            'googleMapKey' => env('GOOGLE_MAP_KEY'),
        ]);
    }

    /**
     * API endpoint for the public share page to get live location data.
     */
    public function publicShareData($token)
    {
        $share = TractorShare::where('token', $token)->first();

        if (!$share || $share->isExpired()) {
            return response()->json(['error' => 'Link expired or invalid.'], 403);
        }

        try {
            $service = new MaintenanceReportService();
            $locationMap = $service->getDeviceLocationMap();

            if (isset($locationMap[$share->imei])) {
                $api = $locationMap[$share->imei];
                $dateTime = date('Y-m-d H:i:s');
                $gmt_date = gmdate('Y-m-d H:i:s', strtotime($dateTime));
                $diffInSeconds = strtotime($gmt_date) - strtotime($api['hbTime'] ?? $gmt_date);
                $minutes = floor($diffInSeconds / 60);

                return response()->json([
                    'success'     => true,
                    'device_name' => $share->device_name,
                    'imei'        => $share->imei,
                    'lat'         => $api['lat'],
                    'lng'         => $api['lng'],
                    'speed'       => $api['speed'] ?? 0,
                    'status'      => $api['status'] ?? 0,
                    'accStatus'   => $api['accStatus'] ?? 0,
                    'hbTime'      => $api['hbTime'] ?? '',
                    'minutes'     => $minutes,
                    'posType'     => $api['posType'] ?? '',
                    'direction'   => $api['direction'] ?? 0,
                ]);
            }

            // Fallback: direct API call
            $apiData = (new Jimi())->getDeviceLocation([$share->imei]);
            if (!empty($apiData['result'][0])) {
                $api = $apiData['result'][0];
                return response()->json([
                    'success'     => true,
                    'device_name' => $share->device_name,
                    'imei'        => $share->imei,
                    'lat'         => $api['lat'],
                    'lng'         => $api['lng'],
                    'speed'       => $api['speed'] ?? 0,
                    'status'      => $api['status'] ?? 0,
                    'accStatus'   => $api['accStatus'] ?? 0,
                    'hbTime'      => $api['hbTime'] ?? '',
                    'minutes'     => 0,
                    'posType'     => $api['posType'] ?? '',
                    'direction'   => $api['direction'] ?? 0,
                ]);
            }

            return response()->json(['error' => 'Device location not available.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to get location: ' . $e->getMessage()], 500);
        }
    }
}
