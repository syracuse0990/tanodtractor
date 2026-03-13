<?php

namespace App\Http\Controllers;

use App\Jobs\ExportREportCSV;
use App\Jobs\ExportReportPdf;
use App\Models\Device;
use App\Models\Export;
use App\Models\Jimi;
use App\Models\Maintenance;
use App\Models\Tractor;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Services\TrackSolidProService;
use App\Services\JimiService;
use App\Services\MaintenanceReportService;
use App\Exports\MaintenanceReportExport;
use App\Exports\TractorUsageExport;
use App\Models\TractorGroup;
use Maatwebsite\Excel\Facades\Excel;


class ReportController extends Controller
{

    public function index(Request $request)
    {
        // set_time_limit(0); // Unlimited execution time
        // ini_set('memory_limit', '-1');
        $device_name = $imei = $output = null;
        $deviceData = $paginatedDeviceData = [];
        $cacheKey = 'deviceData_' . $request->device . '_' . $request->period . '_' . ($request->date_range ?? '');
        if ($request->device) {
            $imei = Device::where('id', $request->device)->pluck('imei_no')->first();
            if (!$imei) {
                session()->flash('error', 'Please select a valid device.');
            } else {
                $device_name = Device::where('id', $request->device)->pluck('device_name')->first();
            }
        } else {
            if (!is_null($request->period)) {
                session()->flash('error', 'Please select a device');
            }
        }

        if (!is_null($request->period)) {

            if (Cache::has($cacheKey)) {
                $deviceData = Cache::get($cacheKey);
            } else {
                // Period Handling Logic
                if ($request->period == 1) {
                    $begin_time = gmdate('Y-m-d H:i:s');
                    $end_time = gmdate('Y-m-d H:i:s');
                } elseif ($request->period == 2) {
                    $thisWeek = Carbon::now()->startOfWeek();
                    $begin_time = $thisWeek->format('Y-m-d H:i:s');
                    $end_time = gmdate('Y-m-d H:i:s');
                } elseif ($request->period == 3) {
                    $thisMonth = Carbon::now()->startOfMonth();
                    $begin_time = $thisMonth->format('Y-m-d H:i:s');
                    $end_time = gmdate('Y-m-d H:i:s');
                    $weeks = getWeeksOfMonth($begin_time, $end_time);
                } elseif ($request->period == 4 && !is_null($request->date_range)) {
                    $data = explode(' - ', $request->date_range);
                    if (!empty($data)) {
                        $begin_time = gmdate('Y-m-d H:i:s', strtotime($data[0]));
                        $end_time = gmdate('Y-m-d', strtotime($data[1])) . ' 23:59:59';
                        $weeks = getWeeksOfMonth($begin_time, $end_time);
                    } else {
                        session()->flash('error', 'Please select date range');
                    }
                }

                if ($imei) {
                    if (isset($weeks)) {
                        foreach ($weeks as $week) {
                            $begin_time  = $week['start'] . ' 00:00:00';
                            $end_time  = $week['end'] . ' 23:59:59';
                            $output = (new Jimi())->getDeviceTrackData($imei, $begin_time, $end_time);
                            if ($output['code'] === 0 && isset($output['result']) && empty($output['result'])) {
                                session()->flash('success', $output['message'] . '[' . $output['code'] . '] - No data found');
                            } elseif ($output['code'] === 0 && isset($output['result'])) {
                                $deviceData[] = $output['result'];
                            } else {
                                session()->flash('error', 'An error occurred in week: ' . $output['code'] . ' - ' . $output['message']);
                            }
                        }
                    } else {
                        $output = (new Jimi())->getDeviceTrackData($imei, $begin_time, $end_time);
                        if ($output['code'] === 0 && isset($output['result']) && empty($output['result'])) {
                            session()->flash('success', $output['message'] . '[' . $output['code'] . '] - No data found');
                        } elseif ($output['code'] === 0 && isset($output['result'])) {
                            $deviceData[] = $output['result'];
                        } else {
                            session()->flash('error', $output['code'] . ' - ' . $output['message']);
                        }
                    }
                    $deviceData = singleArray($deviceData);
                    Cache::put($cacheKey, $deviceData, now()->addMinutes(5));
                } else {
                    session()->flash('error', 'Please select a device');
                }
            }
        }

        if ($request->pdf) {
            if (!count($deviceData)) {
                return redirect()->back()->with('error', 'No data found!!');
            }
            $fileName = 'Track-Details-' . time() . '.pdf';
            Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_REPORT_PDF])->latest('id')->delete();
            $export = Export::create([
                'file_name' => $fileName,
                'type_id' => Export::TYPE_REPORT_PDF
            ]);
            if (!is_null($request->period)) {

                // Period Handling Logic
                if ($request->period == 1) {
                    $begin_time = gmdate('Y-m-d H:i:s');
                    $end_time = gmdate('Y-m-d H:i:s');
                } elseif ($request->period == 2) {
                    $thisWeek = Carbon::now()->startOfWeek();
                    $begin_time = $thisWeek->format('Y-m-d H:i:s');
                    $end_time = gmdate('Y-m-d H:i:s');
                } elseif ($request->period == 3) {
                    $thisMonth = Carbon::now()->startOfMonth();
                    $begin_time = $thisMonth->format('Y-m-d H:i:s');
                    $end_time = gmdate('Y-m-d H:i:s');
                } elseif ($request->period == 4 && !is_null($request->date_range)) {
                    $data = explode(' - ', $request->date_range);
                    if (!empty($data)) {
                        $begin_time = gmdate('Y-m-d H:i:s', strtotime($data[0]));
                        $end_time = gmdate('Y-m-d', strtotime($data[1])) . ' 23:59:59';
                    } else {
                        session()->flash('error', 'Please select date range');
                    }
                }
            }
            $data = [
                'begin_time' => $begin_time,
                'end_time' => $end_time,
                'deviceData' => $deviceData,
                'device_name' => $device_name . ' [' . $imei . ']',
            ];
            ExportReportPdf::dispatch($data, $request->user(), $fileName);
            // dispatch(new ExportReportPdf($data, $request->user(), $fileName));
            session()->flash('success', 'Please wait PDF is generating!!');
            return redirect()->back()->with('success', 'Please wait PDF is generating!!');
        }

        if ($request->csv) {
            if (!count($deviceData)) {
                return redirect()->back()->with('error', 'No data found!!');
            }
            $fileName = 'Track-Details-' . time() . '.csv';
            Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_REPORT_CSV])->latest('id')->delete();
            $export = Export::create([
                'file_name' => $fileName,
                'type_id' => Export::TYPE_REPORT_CSV
            ]);
            if (!is_null($request->period)) {

                // Period Handling Logic
                if ($request->period == 1) {
                    $begin_time = gmdate('Y-m-d H:i:s');
                    $end_time = gmdate('Y-m-d H:i:s');
                } elseif ($request->period == 2) {
                    $thisWeek = Carbon::now()->startOfWeek();
                    $begin_time = $thisWeek->format('Y-m-d H:i:s');
                    $end_time = gmdate('Y-m-d H:i:s');
                } elseif ($request->period == 3) {
                    $thisMonth = Carbon::now()->startOfMonth();
                    $begin_time = $thisMonth->format('Y-m-d H:i:s');
                    $end_time = gmdate('Y-m-d H:i:s');
                } elseif ($request->period == 4 && !is_null($request->date_range)) {
                    $data = explode(' - ', $request->date_range);
                    if (!empty($data)) {
                        $begin_time = gmdate('Y-m-d H:i:s', strtotime($data[0]));
                        $end_time = gmdate('Y-m-d', strtotime($data[1])) . ' 23:59:59';
                    } else {
                        session()->flash('error', 'Please select date range');
                    }
                }
            }
            $data = [
                'begin_time' => $begin_time,
                'end_time' => $end_time,
                'deviceData' => $deviceData,
                'device_name' => $device_name . ' [' . $imei . ']',
            ];
            ExportREportCSV::dispatch($data, $request->user(), $fileName);
            session()->flash('success', 'Please wait CSV is generating!!');
            return redirect()->back()->with('success', 'Please wait CSV is generating!!');
        }

        $paginatedDeviceData = $this->paginate($deviceData);
        $pdfExport = Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_REPORT_PDF])->latest('id')->first();
        $csvExport = Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_REPORT_CSV])->latest('id')->first();
        return view('report.device-report', compact('paginatedDeviceData', 'pdfExport', 'csvExport'));
    }

    /**
     * Paginate an array of items.
     *
     * @return LengthAwarePaginator
     * The paginated items.
     */
    private function paginate(array $items, int $perPage = 20, ?int $page = null, $options = []): LengthAwarePaginator
    {
        $page = $page ?: (LengthAwarePaginator::resolveCurrentPage() ?: 1);
        $items = collect($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    // public function maintenaceReports()
    // {
    //     $maintenanceCount = Maintenance::query();
    //     if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
    //         $maintenanceCount = $maintenanceCount->where('created_by', Auth::id())->count();
    //     } else {
    //         $maintenanceCount = $maintenanceCount->count();
    //     }
    //     $maintenances = Maintenance::query();
    //     if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
    //         $maintenances = $maintenances->where('created_by', Auth::id())->get();
    //     } else {
    //         $maintenances = $maintenances->get();
    //     }
    //     $documentation = $filled = $inprogress = $completed = $cancelled = 0;
    //     foreach ($maintenances as $key => $maintenance) {
    //         if ($maintenance->state_id == Maintenance::STATE_DOCUMENTATION) {
    //             $documentation++;
    //         } elseif ($maintenance->state_id == Maintenance::STATE_FILLED) {
    //             $filled++;
    //         } elseif ($maintenance->state_id == Maintenance::STATE_INPROGRESS) {
    //             $inprogress++;
    //         } elseif ($maintenance->state_id == Maintenance::STATE_COMPLETED) {
    //             $completed++;
    //         } elseif ($maintenance->state_id == Maintenance::STATE_CANCELLED) {
    //             $cancelled++;
    //         }
    //     }
    //     $data = [
    //         'total' => $maintenanceCount,
    //         'documentation' => $documentation,
    //         'filled' => $filled,
    //         'inprogress' => $inprogress,
    //         'completed' => $completed,
    //         'cancelled' => $cancelled,
    //     ];
    //     return view('report.maintenace-report', compact('data'));
    // }
public function maintenanceReports(Request $request)
{
    $service = new MaintenanceReportService();
    $forceRefresh = $request->has('refresh');
    $allData = $service->getMaintenanceData($forceRefresh);

    $page = $request->get('page', 1);
    $perPage = 20;
    $total = count($allData);
    $items = array_slice($allData, ($page - 1) * $perPage, $perPage);

    $maintenanceData = new LengthAwarePaginator(
        $items,
        $total,
        $perPage,
        $page,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    return view('report.maintenace-report', ['maintenanceData' => $maintenanceData]);
}

    /**
     * Export maintenance report to Excel.
     */
    public function exportMaintenanceExcel()
    {
        $service = new MaintenanceReportService();
        $data = $service->getMaintenanceData();

        $filename = 'Maintenance_Report_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new MaintenanceReportExport($data), $filename);
    }

    public function checkFile(Request $request)
    {
        $response['status'] = 'NOK';
        $export = Export::where(['created_by' => Auth::user()->id, 'type_id' => $request->type_id])->latest('id')->first();
        if ($export) {
            $fileName = $export->file_name;
            if ($export->type_id == Export::TYPE_REPORT_PDF) {
                $filePath = storage_path('app/public/reports/' . $fileName);
            } else {
                $filePath = storage_path('app/public/csv/' . $fileName);
            }
            if (file_exists($filePath)) {
                $response['status'] = 'OK';
            }
        } else {
            $response['status'] = 'NF';
        }
        return $response;
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
            if ($request->type_id == Export::TYPE_REPORT_PDF) {
                $filePath = storage_path('app/public/reports/' . $originalFileName);
            } else {
                $filePath = storage_path('app/public/csv/' . $originalFileName);
            }

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
            $export->delete();

            exit;
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
    public function deviceReports(JimiService $jimiService)
    {
        $now = Carbon::now();
        $oneMonthFromNow = $now->copy()->addMonth();

        $totalDevices = Device::count();
        $activeDevices = Device::whereNotNull('activation_time')->where('expiration_date', '>', now())->count();
        $inactiveDevices = Device::whereNull('activation_time')->orWhere('activation_time', 'Inactive')->count();
        $expiredDevices = Device::where('expiration_date', '<', now())->count();
        $expiringSoonDevices = Device::whereBetween('expiration_date', [now(), $oneMonthFromNow])->count();

        $startTime = $now->copy()->startOfDay()->subDays(30)->format('Y-m-d H:i:s');
        $endTime = $now->format('Y-m-d H:i:s');

        $activatedDevices = Device::whereNotNull('activation_time')
            ->where('expiration_date', '>', $now)
            ->paginate(10, ['*'], 'active_page');


        $inActivatedDevices = Device::whereNull('activation_time')->orWhere('activation_time', 'Inactive')
            ->paginate(10, ['*'], 'inactive_page');

        $activeDeviceMetrics = $this->getDeviceMetrics($jimiService, $activatedDevices, $startTime, $endTime);
        $inActiveDeviceMetrics = $this->getDeviceMetrics($jimiService, $inActivatedDevices, $startTime, $endTime);

        return view('report.device-reports', compact(
            'activatedDevices',
            'inActivatedDevices',
            'activeDeviceMetrics',
            'inActiveDeviceMetrics',
            'totalDevices',
            'activeDevices',
            'inactiveDevices',
            'expiredDevices',
            'expiringSoonDevices'
        ));
    }

    /**
     * Get device metrics from Jimi API
     */
  private function getDeviceMetrics(JimiService $jimiService, $devices, $startTime, $endTime): array
    {
        $metrics = [];

        foreach ($devices as $device) {
            if ($device->imei_no) {
                try {
                    $response = $jimiService->getDeviceMileage(
                        [$device->imei_no],
                        $startTime,
                        $endTime
                    );

                    $metrics[$device->id] = $this->getDefaultMetrics();

                    if ($response['code'] === 0 && !empty($response['result'])) {
                        $totalDistance = 0;
                        $totalDuration = 0;
                        $speedSum = 0;
                        $tripCount = count($response['result']);

                        foreach ($response['result'] as $trip) {
                            $totalDistance += (float) $trip['distance'];
                            $totalDuration += (int) $trip['runTimeSecond'];
                            $speedSum += (float) $trip['avgSpeed'];
                        }

                        $averageSpeed = $tripCount > 0 ? $speedSum / $tripCount : 0;

                        $metrics[$device->id] = [
                            'total_distance' => round($totalDistance / 1000, 2),
                            'average_speed' => round($averageSpeed, 2),
                            'total_trips' => $tripCount,
                            'total_duration' => round($totalDuration / 3600, 2),
                        ];
                    }

                    if (!empty($response['data'][0]['totalMileage'])) {
                        $metrics[$device->id]['odometer'] = round($response['data'][0]['totalMileage'] / 1000, 2);
                    }

                } catch (\Exception $e) {
                    \Log::error("Failed to get metrics for device {$device->id}: " . $e->getMessage());
                    $metrics[$device->id] = $this->getDefaultMetrics();
                }
            } else {
                $metrics[$device->id] = $this->getDefaultMetrics();
            }
        }

        return $metrics;
    }

    private function getDefaultMetrics(): array
    {
        return [
            'total_distance' => 0,
            'average_speed' => 0,
            'total_trips' => 0,
            'total_duration' => 0,
            'odometer' => 0,
        ];
    }

    // public function deviceReports( JimiService $jimiService)
    // {

    //     $oneMonthFromNow = Carbon::now()->addMonth();

    //     $totalDevices = Device::count();
    //     $activeDevices = Device::whereNotNull('activation_time')->where('expiration_date', '>', now())->count();
    //     $inactiveDevices = Device::whereNull('activation_time')->count();
    //     $expiredDevices = Device::where('expiration_date', '<', now())->count();
    //     $expiringSoonDevices = Device::whereBetween('expiration_date', [now(), $oneMonthFromNow])->count();

    //     $activatedDevices = Device::whereNotNull('activation_time')->where('expiration_date', '>', now())->paginate(10);
    //     $inActivatedDevices = Device::whereNull('activation_time')->paginate(10);



    //     $now = Carbon::now();
    //     $oneMonthFromNow = $now->copy()->addMonth();
    //     $startTime = $now->copy()->startOfDay()->subDays(30)->format('Y-m-d H:i:s');
    //     $endTime = $now->format('Y-m-d H:i:s');

    //     $activatedDevices = Device::whereNotNull('activation_time')->where('expiration_date', '>', now())->paginate(10);

    //     return view('report.device-reports', compact('totalDevices', 'activeDevices', 'inactiveDevices', 'expiredDevices', 'expiringSoonDevices', 'activatedDevices', 'inActivatedDevices'));
    // }

    public function apiDocumentation(){
        return view('report.api-documentation');
    }

    public function tractorUsage(Request $request)
    {
        $request->validate([
            'group_id' => 'nullable|exists:tractor_groups,id',
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:online,offline,inactive',
            'pms' => 'nullable|in:due,ok,nodata',
            'sort' => 'nullable|in:no_plate,total_distance,running_km',
            'dir' => 'nullable|in:asc,desc',
        ]);

        $query = Tractor::with(['group:id,name', 'device']);

        if ($request->group_id) {
            $query->where('group_id', $request->group_id);
        }

        $allTractors = $query->get();

        $mapped = $allTractors->map(function ($t) {
            $device = $t->device;
            $hours = floatval($t->running_km ?? 0);

            // PMS schedule: first at 50 hrs, then every 100 hrs (150, 250, 350...)
            if ($hours == 0) {
                $pmsStatus = 'No Data';
            } else {
                $nextPms = $hours < 50 ? 50 : 50 + ceil(($hours - 50) / 100) * 100;
                $hrsLeft = round($nextPms - $hours, 1);
                $pmsStatus = $hrsLeft <= 0 ? 'Due' : $hrsLeft . ' hrs left';
            }

            // Last completed maintenance
            $lastMaintenance = Maintenance::where('tractor_ids', $t->id)
                ->where('state_id', Maintenance::STATE_COMPLETED)
                ->latest('maintenance_date')
                ->first();

            // Device status
            if (!$device) {
                $status = 'inactive';
            } elseif ($device->state_id == Device::STATE_ACTIVE && $device->expiration_date > now()) {
                $status = 'online';
            } else {
                $status = 'offline';
            }

            return [
                'id' => $t->id,
                'no_plate' => $t->no_plate,
                'brand' => $t->brand,
                'model' => $t->model,
                'imei' => $t->imei,
                'group_name' => $t->group->name ?? null,
                'total_distance' => floatval($t->total_distance ?? 0),
                'running_hours' => $hours,
                'status' => $status,
                'last_pms_date' => $lastMaintenance?->maintenance_date,
                'pms_status' => $pmsStatus,
            ];
        });

        // Apply filters
        $filtered = $mapped;

        if ($search = $request->search) {
            $search = strtolower($search);
            $filtered = $filtered->filter(fn($t) =>
                str_contains(strtolower($t['no_plate'] ?? ''), $search) ||
                str_contains(strtolower($t['brand'] ?? ''), $search) ||
                str_contains(strtolower($t['model'] ?? ''), $search) ||
                str_contains($t['imei'] ?? '', $search) ||
                str_contains(strtolower($t['group_name'] ?? ''), $search)
            );
        }

        if ($request->status) {
            $filtered = $filtered->where('status', $request->status);
        }

        if ($request->pms) {
            $filtered = match ($request->pms) {
                'due' => $filtered->where('pms_status', 'Due'),
                'nodata' => $filtered->where('pms_status', 'No Data'),
                'ok' => $filtered->filter(fn($t) => $t['pms_status'] !== 'Due' && $t['pms_status'] !== 'No Data'),
            };
        }

        // Sort
        $sortField = $request->sort ?? 'total_distance';
        $sortDir = $request->dir ?? 'desc';
        $filtered = $sortDir === 'asc' ? $filtered->sortBy($sortField) : $filtered->sortByDesc($sortField);

        // Summary from full set (before search/status/pms filters)
        $onlineCount = $mapped->where('status', 'online')->count();
        $offlineCount = $mapped->where('status', 'offline')->count();
        $withData = $mapped->filter(fn($t) => $t['total_distance'] > 0 || $t['running_hours'] > 0)->count();
        $totalTractors = $mapped->count();

        $summary = [
            'total_tractors' => $totalTractors,
            'total_distance' => $mapped->sum('total_distance'),
            'total_hours' => $mapped->sum('running_hours'),
            'avg_distance' => $totalTractors > 0 ? $mapped->avg('total_distance') : 0,
            'pms_due' => $mapped->where('pms_status', 'Due')->count(),
            'total_maintenances' => Maintenance::count(),
            'online' => $onlineCount,
            'offline' => $offlineCount,
            'with_data' => $withData,
            'data_percent' => $totalTractors > 0 ? round($withData / $totalTractors * 100) : 0,
        ];

        $groups = TractorGroup::get(['id', 'name']);

        // Paginate
        $page = $request->get('page', 1);
        $perPage = 25;
        $filteredValues = $filtered->values();
        $tractors = new LengthAwarePaginator(
            $filteredValues->forPage($page, $perPage),
            $filteredValues->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('report.tractor-usage', compact('tractors', 'groups', 'summary'));
    }

    public function exportTractorUsage(Request $request)
    {
        $request->validate([
            'group_id' => 'nullable|exists:tractor_groups,id',
        ]);

        $query = Tractor::with(['group:id,name', 'device']);

        if ($request->group_id) {
            $query->where('group_id', $request->group_id);
        }

        $tractors = $query->get()->map(function ($t) {
            $device = $t->device;
            $hours = floatval($t->running_km ?? 0);

            if ($hours == 0) {
                $pmsStatus = 'No Data';
            } else {
                $nextPms = $hours < 50 ? 50 : 50 + ceil(($hours - 50) / 100) * 100;
                $hrsLeft = round($nextPms - $hours, 1);
                $pmsStatus = $hrsLeft <= 0 ? 'Due' : $hrsLeft . ' hrs left';
            }

            $lastMaintenance = Maintenance::where('tractor_ids', $t->id)
                ->where('state_id', Maintenance::STATE_COMPLETED)
                ->latest('maintenance_date')
                ->first();

            if (!$device) {
                $status = 'inactive';
            } elseif ($device->state_id == Device::STATE_ACTIVE && $device->expiration_date > now()) {
                $status = 'online';
            } else {
                $status = 'offline';
            }

            return [
                'no_plate' => $t->no_plate,
                'brand' => $t->brand,
                'model' => $t->model,
                'imei' => $t->imei,
                'group_name' => $t->group->name ?? null,
                'total_distance' => floatval($t->total_distance ?? 0),
                'running_hours' => $hours,
                'status' => $status,
                'last_pms_date' => $lastMaintenance?->maintenance_date,
                'pms_status' => $pmsStatus,
            ];
        });

        $pmsDueCount = $tractors->where('pms_status', 'Due')->count();
        $summary = [
            'total_tractors' => $tractors->count(),
            'total_distance' => $tractors->sum('total_distance'),
            'total_hours' => $tractors->sum('running_hours'),
            'pms_due' => $pmsDueCount,
            'total_maintenances' => Maintenance::count(),
        ];

        $filename = 'tractor-usage-report-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new TractorUsageExport($tractors->toArray(), $summary),
            $filename
        );
    }

}
