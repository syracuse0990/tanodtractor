<?php

namespace App\Jobs;

use App\Models\Jimi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportOverview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bookings;
    protected $fileName;
    /**
     * Create a new job instance.
     */
    public function __construct($bookings, $fileName)
    {
        $this->bookings = $bookings;
        $this->fileName = $fileName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $headers = [
            "Device Name",
            "IMEI",
            "Model",
            "Driver Name",
            "Number Plate",
            "Sim",
            "Phone",
            "Booking Date",
            "Today's Kilometer"
        ];
        $data = [];

        foreach ($this->bookings as $booking) {
            $kilometer = 0;
            $farmer = $booking?->createdBy ? $booking?->createdBy->name ?? $booking?->createdBy->email : 'N/A';
            $begin_time = $booking?->date . ' 00:00:00';
            $end_time = $booking?->date . ' 23:59:59';
            $imeis = $booking?->device?->imei_no ? explode(',', $booking?->device?->imei_no) : [];
            if ($imeis) {
                $callApi = (new Jimi())->getDeviceMilage($imeis, $begin_time, $end_time);
                $meter = $callApi['data'] ? $callApi['data'][0]['totalMileage'] : null;
                $kilometer = $meter ? $meter / 1000 : 0;
            }
            $row = [
                $booking?->device?->device_name ?? 'N/A',
                $booking?->device?->imei_no ?? 'N/A',
                $booking?->device?->device_modal ?? 'N/A',
                $farmer,
                $booking?->tractor?->no_plate ?? 'N/A',
                $booking?->device?->sim ?? 'N/A',
                $booking?->createdBy?->phone ?? 'N/A',
                $booking?->date ?? 'N/A',
                $kilometer
            ];
            $data[] = $row;
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
