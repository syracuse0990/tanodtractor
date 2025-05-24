<?php

namespace App\Console\Commands;

use App\Mail\DailyReportMail;
use App\Mail\MonthlyReportMail;
use App\Mail\WeeklyReportMail;
use App\Models\AutoReport;
use App\Models\Device;
use App\Models\Jimi;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GenerateAutoReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-auto-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to generate and send report on mail automatically.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $autoReports = AutoReport::get();
        echo 'File Generation start>>>>>>';
        foreach ($autoReports as $report) {
            $frequency = $report->frequency;
            if ($frequency == AutoReport::FREQUENCY_MONTHLY) {
                $this->monthlyReport($report);
            } elseif ($frequency == AutoReport::FREQUENCY_WEEKLY) {
                $this->weeklyReport($report);
            } elseif ($frequency == AutoReport::FREQUENCY_DAILY) {
                $this->dailyReport($report);
            } else {
                continue;
            }
        }
        echo 'File Generation End>>>>>>';
    }

    public function monthlyReport($report)
    {
        $day = $report->execution_day;
        $time = date('H:i', strtotime($report->execution_time));
        $currentDay = Carbon::now()->timezone('Asia/Kuala_Lumpur');
        $currentDate = $currentDay->day;
        $currentTime = $currentDay->format('H:i');

        if ($day == $currentDate && $time == $currentTime) {

            $deviceIds = explode(',', $report->device_ids);
            $imeis = Device::whereIn('id', $deviceIds)->pluck('imei_no')->toArray();

            $currentYearMonth = $currentDay->format('Y-m');
            $begin_date = Carbon::createFromFormat('Y-m-d', $currentYearMonth . '-' . $report->from_day)->format('Y-m-d');
            $end_date = Carbon::createFromFormat('Y-m-d', $currentYearMonth . '-' . $report->to_day)->format('Y-m-d');
            $weeks = getWeeksOfMonth($begin_date, $end_date);

            $files = [];
            $deviceIds = [];
            foreach ($imeis as $imei) {
                $deviceData = [];
                foreach ($weeks as $key => $week) {
                    if ($key === 0) {
                        $begin_time = $week['start'] . ' ' . $report->from_time;
                    } else {
                        $begin_time = $week['start'] . ' 00:00:00';
                    }

                    if ($key === count($weeks) - 1) {
                        $end_time = $week['end'] . ' ' . $report->to_time;
                    } else {
                        $end_time = $week['end'] . ' 23:59:59';
                    }
                    $output = (new Jimi())->getDeviceTrackData($imei, $begin_time, $end_time);

                    if ($output['code'] === 0 && isset($output['result'])) {
                        $deviceData[] = $output['result'];
                    }
                }
                $deviceData = singleArray($deviceData);
                if (!empty($deviceData)) {
                    $device = Device::where('imei_no', $imei)->first();
                    $spreadsheet = new Spreadsheet();

                    // Set the active sheet
                    $sheet = $spreadsheet->getActiveSheet();

                    // Merge cells for the title in row 1
                    $sheet->mergeCells('A1:L1');
                    $sheet->setCellValue('A1', 'Track Details:' . $device->device_name . ' ' . $begin_date . ' ' . $report->from_time . ' - ' . $end_date . ' ' . $report->to_time); // Add title text to the merged cells

                    // Set the title style: center alignment
                    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getRowDimension(1)->setRowHeight(30);

                    // Set the width of the header columns
                    $sheet->getColumnDimension('A')->setWidth(20);
                    $sheet->getColumnDimension('B')->setWidth(20);
                    $sheet->getColumnDimension('C')->setWidth(20);
                    $sheet->getColumnDimension('D')->setWidth(20);
                    $sheet->getColumnDimension('E')->setWidth(20);
                    $sheet->getColumnDimension('F')->setWidth(20);
                    $sheet->getColumnDimension('G')->setWidth(20);
                    $sheet->getColumnDimension('H')->setWidth(20);
                    $sheet->getColumnDimension('I')->setWidth(20);
                    $sheet->getColumnDimension('J')->setWidth(20);
                    $sheet->getColumnDimension('K')->setWidth(20);
                    $sheet->getColumnDimension('L')->setWidth(20);

                    // Add headers with blue background
                    $sheet->setCellValue('A2', 'No');
                    $sheet->setCellValue('B2', 'Device Name');
                    $sheet->setCellValue('C2', 'IMEI');
                    $sheet->setCellValue('D2', 'Model');
                    $sheet->setCellValue('E2', 'SIM');
                    $sheet->setCellValue('F2', 'Ignition');
                    $sheet->setCellValue('G2', 'Position Time');
                    $sheet->setCellValue('H2', 'Speed');
                    $sheet->setCellValue('I2', 'Azimuth');
                    $sheet->setCellValue('J2', 'Position Type');
                    $sheet->setCellValue('K2', 'No of satellites');
                    $sheet->setCellValue('L2', 'Latitude');
                    $sheet->setCellValue('M2', 'Longitude');

                    // Apply blue background color to headers
                    $sheet->getStyle('A2:L2')->getFill()->setFillType(Fill::FILL_SOLID);
                    $sheet->getStyle('A2:L2')->getFill()->getStartColor()->setRGB('0066CC');
                    $sheet->getStyle('A2:L2')->getFont()->getColor()->setRGB(Color::COLOR_WHITE);

                    $sheet->getStyle('A2:L2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('A2:L2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                    // Set the row height for the header row to a smaller value
                    $sheet->getRowDimension(2)->setRowHeight(20);
                    $sheet->getStyle('A2:L2')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                    $i = 3;
                    foreach ($deviceData as $key => $data) {
                        $positionType = 'N/A';
                        if ($data['posType'] == 1) {
                            $positionType = 'GPS';
                        } elseif ($data['posType'] == 2) {
                            $positionType = 'LBS';
                        } elseif ($data['posType'] == 3) {
                            $positionType = 'WIFI';
                        }
                        $sheet->setCellValue('A' . $i, ($key + 1));
                        $sheet->setCellValue('B' . $i, $device->device_name);
                        $sheet->setCellValue('C' . $i, $imei);
                        $sheet->setCellValue('D' . $i, $device->device_modal);
                        $sheet->setCellValue('E' . $i, $device->sim);
                        $sheet->setCellValue('F' . $i, $data['ignition']);
                        $sheet->setCellValue('G' . $i, gmdate('Y-m-d H:i:s', strtotime($data['gpsTime'])));
                        $sheet->setCellValue('H' . $i, $data['gpsSpeed']);
                        $sheet->setCellValue('I' . $i, $data['direction']);
                        $sheet->setCellValue('J' . $i, $positionType);
                        $sheet->setCellValue('K' . $i, $data['satellite']);
                        $sheet->setCellValue('L' . $i, $data['lat']);
                        $sheet->setCellValue('M' . $i, $data['lng']);

                        $i++;
                    }

                    $writer = new Xlsx($spreadsheet);
                    $filePath = storage_path('app/public/excel_files/' . $imei . '-' . time() . '.xlsx');
                    $writer->save($filePath);
                    $files[] = $filePath;
                    $deviceIds[] = $imei;
                }
            }

            if (!empty($files)) {
                echo count($files);
                Mail::to($report->email_addresses)->send(new MonthlyReportMail($files, $deviceIds));
            }
        }
    }

    public function weeklyReport($report)
    {
        $day = $report->execution_day;
        $time = date('H:i', strtotime($report->execution_time));
        $currentDay = Carbon::now()->timezone('Asia/Kuala_Lumpur');
        $weekday = $currentDay->dayOfWeek;
        $currentTime = $currentDay->format('H:i');

        if ($day == $weekday && $time == $currentTime) {
            $deviceIds = explode(',', $report->device_ids);
            $imeis = Device::whereIn('id', $deviceIds)->pluck('imei_no')->toArray();

            $currentDate = new DateTime('now', new DateTimeZone('GMT'));
            $dayOfWeek = $currentDate->format('N');
            $interval = $report->from_day - $dayOfWeek;
            $currentDate->modify("$interval days");
            $begin_time = $currentDate->format('Y-m-d') . ' ' . $report->from_time;

            $interval = $report->to_day - $dayOfWeek;
            $currentDate->modify("$interval days");
            $end_time = $currentDate->format('Y-m-d') . ' ' . $report->to_time;

            $files = [];
            $deviceIds = [];
            foreach ($imeis as $imei) {
                $output = (new Jimi())->getDeviceTrackData($imei, $begin_time, $end_time);
                if ($output['code'] === 0 && isset($output['result'])) {
                    $deviceData = $output['result'];
                    if (!empty($deviceData)) {
                        $device = Device::where('imei_no', $imei)->first();
                        $spreadsheet = new Spreadsheet();

                        // Set the active sheet
                        $sheet = $spreadsheet->getActiveSheet();

                        // Merge cells for the title in row 1
                        $sheet->mergeCells('A1:L1');
                        $sheet->setCellValue('A1', 'Track Details:' . $device->device_name . ' ' . $begin_time . ' - ' . $end_time); // Add title text to the merged cells

                        // Set the title style: center alignment
                        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        $sheet->getRowDimension(1)->setRowHeight(30);

                        // Set the width of the header columns
                        $sheet->getColumnDimension('A')->setWidth(20);
                        $sheet->getColumnDimension('B')->setWidth(20);
                        $sheet->getColumnDimension('C')->setWidth(20);
                        $sheet->getColumnDimension('D')->setWidth(20);
                        $sheet->getColumnDimension('E')->setWidth(20);
                        $sheet->getColumnDimension('F')->setWidth(20);
                        $sheet->getColumnDimension('G')->setWidth(20);
                        $sheet->getColumnDimension('H')->setWidth(20);
                        $sheet->getColumnDimension('I')->setWidth(20);
                        $sheet->getColumnDimension('J')->setWidth(20);
                        $sheet->getColumnDimension('K')->setWidth(20);
                        $sheet->getColumnDimension('L')->setWidth(20);

                        // Add headers with blue background
                        $sheet->setCellValue('A2', 'No');
                        $sheet->setCellValue('B2', 'Device Name');
                        $sheet->setCellValue('C2', 'IMEI');
                        $sheet->setCellValue('D2', 'Model');
                        $sheet->setCellValue('E2', 'SIM');
                        $sheet->setCellValue('F2', 'Ignition');
                        $sheet->setCellValue('G2', 'Position Time');
                        $sheet->setCellValue('H2', 'Speed');
                        $sheet->setCellValue('I2', 'Azimuth');
                        $sheet->setCellValue('J2', 'Position Type');
                        $sheet->setCellValue('K2', 'No of satellites');
                        $sheet->setCellValue('L2', 'Latitude');
                        $sheet->setCellValue('M2', 'Longitude');

                        // Apply blue background color to headers
                        $sheet->getStyle('A2:L2')->getFill()->setFillType(Fill::FILL_SOLID);
                        $sheet->getStyle('A2:L2')->getFill()->getStartColor()->setRGB('0066CC');
                        $sheet->getStyle('A2:L2')->getFont()->getColor()->setRGB(Color::COLOR_WHITE);

                        $sheet->getStyle('A2:L2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('A2:L2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                        // Set the row height for the header row to a smaller value
                        $sheet->getRowDimension(2)->setRowHeight(20);
                        $sheet->getStyle('A2:L2')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                        $i = 3;
                        foreach ($deviceData as $key => $data) {
                            $positionType = 'N/A';
                            if ($data['posType'] == 1) {
                                $positionType = 'GPS';
                            } elseif ($data['posType'] == 2) {
                                $positionType = 'LBS';
                            } elseif ($data['posType'] == 3) {
                                $positionType = 'WIFI';
                            }
                            $sheet->setCellValue('A' . $i, ($key + 1));
                            $sheet->setCellValue('B' . $i, $device->device_name);
                            $sheet->setCellValue('C' . $i, $imei);
                            $sheet->setCellValue('D' . $i, $device->device_modal);
                            $sheet->setCellValue('E' . $i, $device->sim);
                            $sheet->setCellValue('F' . $i, $data['ignition']);
                            $sheet->setCellValue('G' . $i, gmdate('Y-m-d H:i:s', strtotime($data['gpsTime'])));
                            $sheet->setCellValue('H' . $i, $data['gpsSpeed']);
                            $sheet->setCellValue('I' . $i, $data['direction']);
                            $sheet->setCellValue('J' . $i, $positionType);
                            $sheet->setCellValue('K' . $i, $data['satellite']);
                            $sheet->setCellValue('L' . $i, $data['lat']);
                            $sheet->setCellValue('M' . $i, $data['lng']);

                            $i++;
                        }

                        $writer = new Xlsx($spreadsheet);
                        $filePath = storage_path('app/public/excel_files/' . $imei . '-' . time() . '.xlsx');
                        $writer->save($filePath);
                        $files[] = $filePath;
                        $deviceIds[] = $imei;
                    }
                }
            }

            if (!empty($files)) {
                echo count($files);
                Mail::to($report->email_addresses)->send(new WeeklyReportMail($files, $deviceIds));
            }
        }
    }

    public function dailyReport($report)
    {
        $time = date('H:i', strtotime($report->execution_time));
        $currentDay = Carbon::now()->timezone('Asia/Kuala_Lumpur');
        $currentTime = $currentDay->format('H:i');

        if ($time == $currentTime) {
            $deviceIds = explode(',', $report->device_ids);
            $imeis = Device::whereIn('id', $deviceIds)->pluck('imei_no')->toArray();

            $currentDate = new DateTime('now', new DateTimeZone('GMT'));
            $currentDate->modify('-1 day');
            $begin_time = $currentDate->format('Y-m-d') . ' ' . $report->from_time;
            $end_time = $currentDate->format('Y-m-d') . ' ' . $report->to_time;

            $files = [];
            $deviceIds = [];
            foreach ($imeis as $imei) {
                $output = (new Jimi())->getDeviceTrackData($imei, $begin_time, $end_time);
                if ($output['code'] === 0 && isset($output['result'])) {
                    $deviceData = $output['result'];
                    if (!empty($deviceData)) {
                        $device = Device::where('imei_no', $imei)->first();
                        $spreadsheet = new Spreadsheet();

                        // Set the active sheet
                        $sheet = $spreadsheet->getActiveSheet();

                        // Merge cells for the title in row 1
                        $sheet->mergeCells('A1:L1');
                        $sheet->setCellValue('A1', 'Track Details:' . $device->device_name . ' ' . $begin_time . ' - ' . $end_time); // Add title text to the merged cells

                        // Set the title style: center alignment
                        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        $sheet->getRowDimension(1)->setRowHeight(30);

                        // Set the width of the header columns
                        $sheet->getColumnDimension('A')->setWidth(20);
                        $sheet->getColumnDimension('B')->setWidth(20);
                        $sheet->getColumnDimension('C')->setWidth(20);
                        $sheet->getColumnDimension('D')->setWidth(20);
                        $sheet->getColumnDimension('E')->setWidth(20);
                        $sheet->getColumnDimension('F')->setWidth(20);
                        $sheet->getColumnDimension('G')->setWidth(20);
                        $sheet->getColumnDimension('H')->setWidth(20);
                        $sheet->getColumnDimension('I')->setWidth(20);
                        $sheet->getColumnDimension('J')->setWidth(20);
                        $sheet->getColumnDimension('K')->setWidth(20);
                        $sheet->getColumnDimension('L')->setWidth(20);

                        // Add headers with blue background
                        $sheet->setCellValue('A2', 'No');
                        $sheet->setCellValue('B2', 'Device Name');
                        $sheet->setCellValue('C2', 'IMEI');
                        $sheet->setCellValue('D2', 'Model');
                        $sheet->setCellValue('E2', 'SIM');
                        $sheet->setCellValue('F2', 'Ignition');
                        $sheet->setCellValue('G2', 'Position Time');
                        $sheet->setCellValue('H2', 'Speed');
                        $sheet->setCellValue('I2', 'Azimuth');
                        $sheet->setCellValue('J2', 'Position Type');
                        $sheet->setCellValue('K2', 'No of satellites');
                        $sheet->setCellValue('L2', 'Latitude');
                        $sheet->setCellValue('M2', 'Longitude');

                        // Apply blue background color to headers
                        $sheet->getStyle('A2:L2')->getFill()->setFillType(Fill::FILL_SOLID);
                        $sheet->getStyle('A2:L2')->getFill()->getStartColor()->setRGB('0066CC');
                        $sheet->getStyle('A2:L2')->getFont()->getColor()->setRGB(Color::COLOR_WHITE);

                        $sheet->getStyle('A2:L2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle('A2:L2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                        // Set the row height for the header row to a smaller value
                        $sheet->getRowDimension(2)->setRowHeight(20);
                        $sheet->getStyle('A2:L2')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                        $i = 3;
                        foreach ($deviceData as $key => $data) {
                            $positionType = 'N/A';
                            if ($data['posType'] == 1) {
                                $positionType = 'GPS';
                            } elseif ($data['posType'] == 2) {
                                $positionType = 'LBS';
                            } elseif ($data['posType'] == 3) {
                                $positionType = 'WIFI';
                            }
                            $sheet->setCellValue('A' . $i, ($key + 1));
                            $sheet->setCellValue('B' . $i, $device->device_name);
                            $sheet->setCellValue('C' . $i, $imei);
                            $sheet->setCellValue('D' . $i, $device->device_modal);
                            $sheet->setCellValue('E' . $i, $device->sim);
                            $sheet->setCellValue('F' . $i, $data['ignition']);
                            $sheet->setCellValue('G' . $i, gmdate('Y-m-d H:i:s', strtotime($data['gpsTime'])));
                            $sheet->setCellValue('H' . $i, $data['gpsSpeed']);
                            $sheet->setCellValue('I' . $i, $data['direction']);
                            $sheet->setCellValue('J' . $i, $positionType);
                            $sheet->setCellValue('K' . $i, $data['satellite']);
                            $sheet->setCellValue('L' . $i, $data['lat']);
                            $sheet->setCellValue('M' . $i, $data['lng']);

                            $i++;
                        }

                        $writer = new Xlsx($spreadsheet);
                        $filePath = storage_path('app/public/excel_files/' . $imei . '-' . time() . '.xlsx');
                        $writer->save($filePath);
                        $files[] = $filePath;
                        $deviceIds[] = $imei;
                    }
                }
            }

            if (!empty($files)) {
                echo count($files);
                Mail::to($report->email_addresses)->send(new DailyReportMail($files, $deviceIds));
            }
        }
    }
}
