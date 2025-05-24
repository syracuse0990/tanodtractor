<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportFarmers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fileName;
    protected $farmers;
    /**
     * Create a new job instance.
     */
    public function __construct($fileName, $farmers)
    {
        $this->fileName = $fileName;
        $this->farmers = $farmers;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $headers = ["Name", "Email", "Phone", "Role", "Created At"];
        $data = [];

        foreach ($this->farmers as $farmer) {
            $phone = $farmer?->phone_country . ' ' . $farmer?->phone;
            $row = [
                $farmer->name ?? 'N/A',
                $farmer->email ?? 'N/A',
                $phone ?? 'N/A',
                $farmer->getRole() ?? 'N/A',
                date('Y-m-d', strtotime($farmer->created_at)) ?? 'N/A',
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
