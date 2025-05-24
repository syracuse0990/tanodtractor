<?php

namespace App\Jobs;

use App\Models\Jimi;
use App\Models\Tractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tractors;
    protected $fileName;
    /**
     * Create a new job instance.
     */
    public function __construct($tractors, $fileName)
    {
        $this->tractors = $tractors;
        $this->fileName = $fileName;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
     
        $headers = ["Tractor Name", "Number Plate", "Id Number", "Tractor Brand", "Tractor Model", "Maintenance Kilometer", "Running Kilometer", "Total Kilometer", "IMEI No", "Device Name", "Device Model", "Booking Date", "Today's Kilometer", "Booking Purpose", "Created By"];
        $data = [];
        $kilometer = 0;

        foreach ($this->tractors as $tractor) {
            $bookings = $tractor->bookings;

            $row = [
                $tractor->id_no . ' (' . $tractor->model . ')',
                $tractor->no_plate,
                $tractor->id_no,
                $tractor->brand,
                $tractor->model,
                $tractor->maintenance_kilometer ?? '0',
                $tractor->running_km ?? '0',
                $tractor->total_distance ?? '0',
            ];

            $data[] = $row;

            if (count($bookings)) {
                foreach ($bookings as $booking) {
                    $begin_time = $booking->date . ' 00:00:00';
                    $end_time = $booking->date . ' 23:59:59';
                    $imeis = $booking->device?->imei_no ? explode(',', $booking->device?->imei_no) : [];
                    if ($imeis) {
                        $callApi = (new Jimi())->getDeviceMilage($imeis, $begin_time, $end_time);
                        $meter = $callApi['data'] ? $callApi['data'][0]['totalMileage'] : null;
                        $kilometer = $meter ? $meter / 1000 : 0;
                    }

                    $bookingRow = array_fill(0, count($headers), '');

                    // Add booking details
                    $bookingRow[count($headers) - 7] = $booking->device?->imei_no;
                    $bookingRow[count($headers) - 6] = $booking->device?->device_name;
                    $bookingRow[count($headers) - 5] = $booking->device?->device_modal;
                    $bookingRow[count($headers) - 4] = $booking->date;
                    $bookingRow[count($headers) - 3] = $kilometer;
                    $bookingRow[count($headers) - 2] = $booking->purpose;
                    $bookingRow[count($headers) - 1] = $booking->createdBy ? $booking->createdBy->name ?? $booking->createdBy->email : '';

                    $data[] = $bookingRow;
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
