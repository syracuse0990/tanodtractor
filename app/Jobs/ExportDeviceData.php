<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\Jimi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportDeviceData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $devices;
    protected $fileName;
    /**
     * Create a new job instance.
     */
    public function __construct($devices, $fileName)
    {
        $this->devices = $devices;
        $this->fileName = $fileName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $headers = ["Device Name", "IMEI No", "Device Model", "SIM", "farmer", "Number Plate", "Phone", "Booking Date", "Today's Kilometer"];
        $data = [];
        $kilometer = 0;

        foreach ($this->devices as $device) {
            $bookings = $device->bookings;
            $deviceInfo = [
                $device->device_name,
                $device->imei_no,
                $device->device_modal,
                $device->sim,
            ];

            if ($bookings->isEmpty()) {
                $data[] = array_merge(
                    $deviceInfo,
                    [
                        'N/A',
                        'N/A',
                        'N/A',
                        'N/A',
                        0,
                    ]
                );
            } else {
                foreach ($bookings as $booking) {
                    $begin_time = $booking->date . ' 00:00:00';
                    $end_time = $booking->date . ' 23:59:59';
                    $imeis = $device->imei_no ? explode(',', $device->imei_no) : [];

                    if ($imeis) {
                        $callApi = (new Jimi())->getDeviceMilage($imeis, $begin_time, $end_time);
                        $meter = $callApi['data'] ? $callApi['data'][0]['totalMileage'] : null;
                        $kilometer = $meter ? $meter / 1000 : 0;
                    }

                    $row = array_merge(
                        $deviceInfo,
                        [
                            $booking->createdBy ? $booking->createdBy->name ?? $booking->createdBy->email : '',
                            $booking->tractor ? $booking->tractor->no_plate : '',
                            $booking->createdBy ? $booking->createdBy->phone : '',
                            $booking->date,
                            $kilometer,
                        ]
                    );

                    $data[] = $row;
                }
            }
        }
        $storagePath = storage_path('app/public/csv');

        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $filePath = $storagePath . DIRECTORY_SEPARATOR . $this->fileName;

        $file = fopen($filePath, 'w');

        fputcsv($file, $headers);

        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);

        Storage::disk('public')->put('csv/' . $this->fileName, file_get_contents($filePath));
    }
}
