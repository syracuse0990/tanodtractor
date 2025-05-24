<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportREportCSV implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    protected $data;
    protected $user;
    protected $filename;
    /**
     * Create a new job instance.
     */
    public function __construct($data, $user, $filename)
    {
        $this->data = $data;
        $this->user = $user;
        $this->filename = $filename;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $headers = ["Position Time", "Speed", "Azimuth", "Position type", "No. of satellites", "Latitude", "Longitude"];

        $csvData = [];

        foreach ($this->data['deviceData'] as  $value) {
            $positionType = 'N/A';
            if ($value['posType'] == 1) {
                $positionType = 'GPS';
            } elseif ($value['posType'] == 2) {
                $positionType = 'LBS';
            } elseif ($value['posType'] == 3) {
                $positionType = 'WIFI';
            }
            $csvData[] = [
                gmdate('Y-m-d H:i:s', strtotime($value['gpsTime'])),
                $value['gpsSpeed'],
                $value['direction'],
                $positionType,
                $value['satellite'],
                $value['lat'],
                $value['lng']
            ];
        }
        $storagePath = storage_path('app/public/csv');

        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $filePath = $storagePath . DIRECTORY_SEPARATOR . $this->filename;

        $file = fopen($filePath, 'w');

        fputcsv($file, ['Track Details']);
        fputcsv($file, [$this->data['device_name']]);
        fputcsv($file, [$this->data['begin_time'] . ' - ' . $this->data['end_time']]);

        fputcsv($file, $headers);
        foreach ($csvData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        Storage::disk('public')->put('csv/' . $this->filename, file_get_contents($filePath));
    }
}
