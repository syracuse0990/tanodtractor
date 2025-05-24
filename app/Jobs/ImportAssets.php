<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\FarmAsset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class ImportAssets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    protected $filePath;
    protected $currentUser;
    protected $exportId;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $currentUser, $exportId)
    {
        $this->filePath = $filePath;
        $this->currentUser = $currentUser;
        $this->exportId = $exportId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $output = new ConsoleOutput();
        $filePath = storage_path('app/public/' . $this->filePath);
        $handle = fopen($filePath, 'r');
        $totalLines = count(file($filePath)) - 1;
        $progressBar = new ProgressBar($output, $totalLines);
        $header = fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            $number_plate = (isset($data[0]) && !empty($data[0])) ? trim($data[0]) : null;
            if (!$number_plate) {
                continue;
            }
            $assetExists = FarmAsset::Where('number_plate', $number_plate)->latest('id')->first();
            if ($assetExists) {
                continue;
            }
            $old = (isset($data[3]) && !empty($data[3])) ? trim($data[3]) : null;
            $new = (isset($data[4]) && !empty($data[4])) ? trim($data[4]) : null;
            $condition = 0;
            if ($old == 1) {
                $condition = 1;
            }
            if ($new == 1) {
                $condition = 2;
            }

            $type = array_search(strtolower(trim($data[2])), array_map('strtolower', FarmAsset::typeOptions()));
            $assetData = [
                'number_plate' => $number_plate,
                'mileage' => (isset($data[1]) && !empty($data[1])) ? trim($data[1]) : null,
                'condition' => $condition,
                'type_id' => isset($type) && $type !== false ? $type : null,
                'created_by' => $this->currentUser
            ];
            FarmAsset::create($assetData);
            $progressBar->advance();
            $progressPercentage = floor(($progressBar->getProgress() / $progressBar->getMaxSteps()) * 100);
            if ($progressPercentage > 0) {
                Export::where('id', $this->exportId)->update(['progress' => $progressPercentage]);
            }
        }
        $progressBar->finish();
        Export::where('id', $this->exportId)->update(['progress' => 100]);
        fclose($handle);
        Storage::disk('public')->delete($filePath);
    }
}
