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

    public function maintenaceReports()
    {
        $maintenanceCount = Maintenance::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $maintenanceCount = $maintenanceCount->where('created_by', Auth::id())->count();
        } else {
            $maintenanceCount = $maintenanceCount->count();
        }
        $maintenances = Maintenance::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $maintenances = $maintenances->where('created_by', Auth::id())->get();
        } else {
            $maintenances = $maintenances->get();
        }
        $documentation = $filled = $inprogress = $completed = $cancelled = 0;
        foreach ($maintenances as $key => $maintenance) {
            if ($maintenance->state_id == Maintenance::STATE_DOCUMENTATION) {
                $documentation++;
            } elseif ($maintenance->state_id == Maintenance::STATE_FILLED) {
                $filled++;
            } elseif ($maintenance->state_id == Maintenance::STATE_INPROGRESS) {
                $inprogress++;
            } elseif ($maintenance->state_id == Maintenance::STATE_COMPLETED) {
                $completed++;
            } elseif ($maintenance->state_id == Maintenance::STATE_CANCELLED) {
                $cancelled++;
            }
        }
        $data = [
            'total' => $maintenanceCount,
            'documentation' => $documentation,
            'filled' => $filled,
            'inprogress' => $inprogress,
            'completed' => $completed,
            'cancelled' => $cancelled,
        ];
        return view('report.maintenace-report', compact('data'));
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

    public function deviceReports()
    {
        $oneMonthFromNow = Carbon::now()->addMonth();

        $totalDevices = Device::count();
        $activeDevices = Device::whereNotNull('activation_time')->where('expiration_date', '>', now())->count();
        $inactiveDevices = Device::whereNull('activation_time')->count();
        $expiredDevices = Device::where('expiration_date', '<', now())->count();
        $expiringSoonDevices = Device::whereBetween('expiration_date', [now(), $oneMonthFromNow])->count();
        return view('report.device-reports', compact('totalDevices', 'activeDevices', 'inactiveDevices', 'expiredDevices', 'expiringSoonDevices'));
    }
}
