<?php

namespace App\Jobs;

use App\Models\FarmerFeedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportFeedback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fileName;
    protected $farmerFeedbacks;
    /**
     * Create a new job instance.
     */
    public function __construct($fileName, $farmerFeedbacks)
    {
        $this->fileName = $fileName;
        $this->farmerFeedbacks = $farmerFeedbacks;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $headers = ["Title", "Email", "Issue Type", "Description", "Conclusion", "State", "Farmer", "Tractor Name", "Number Plate", "Id Number", "Tractor Brand", "Tractor Model", "Maintenance Kilometer", "Running Kilometer", "Total Kilometer"];
        $data = [];

        foreach ($this->farmerFeedbacks as $feedback) {
            $farmer = $feedback->createdBy?->name ?? $feedback->createdBy?->email;
            $row = [
                $feedback->name ?? 'N/A',
                $feedback->email ?? 'N/A',
                $feedback->issueType?->title ?? 'N/A',
                $feedback->description ?? 'N/A',
                $feedback->conclusion ?? 'N/A',
                $feedback->getState() ?? 'N/A',
                $farmer,
                $feedback->tractor?->id_no . ' (' . $feedback->tractor->model . ')',
                $feedback->tractor->no_plate,
                $feedback->tractor->id_no,
                $feedback->tractor->brand,
                $feedback->tractor->model,
                $feedback->tractor->maintenance_kilometer ?? '0',
                $feedback->tractor->running_km ?? '0',
                $feedback->tractor->total_distance ?? '0',
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
