<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\Tractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class ImportTractors implements ShouldQueue
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
            $imei = (isset($data[0]) && !empty($data[0])) ? trim($data[0]) : null;
            if (!$imei) {
                continue;
            }
            $tractorExists = Tractor::where('imei', $imei)->first();
            if ($tractorExists) {
                continue;
            }
            $tractorData = [
                'imei' => $imei,
                'no_plate' => (isset($data[1]) && !empty($data[1])) ? trim($data[1]) : null,
                'id_no' => (isset($data[2]) && !empty($data[2])) ? trim($data[2]) : null,
                'engine_no' => (isset($data[3]) && !empty($data[3])) ? trim($data[3]) : null,
                'fuel_consumption' => (isset($data[4]) && !empty($data[4])) ? trim($data[4]) : null,
                'first_maintenance_hr' => (isset($data[5]) && !empty($data[5])) ? trim($data[5]) : null,
                'maintenance_kilometer' => (isset($data[6]) && !empty($data[6])) ? trim($data[6]) : null,
                'running_km' => (isset($data[7]) && !empty($data[7])) ? trim($data[7]) : null,
                'brand' => (isset($data[8]) && !empty($data[8])) ? trim($data[8]) : null,
                'model' => (isset($data[9]) && !empty($data[9])) ? trim($data[9]) : null,
                'manufacture_date' => (isset($data[10]) && !empty($data[10])) ? date('Y-m-d', strtotime(trim($data[10]))) : null,
                'installation_time' => (isset($data[11]) && !empty($data[11])) ? date('Y-m-d H:i:s', strtotime(trim($data[11]))) : null,
                'installation_address' => (isset($data[12]) && !empty($data[12])) ? trim($data[12]) : null,
                'state_id' => Tractor::STATE_ACTIVE,
                'created_by' => $this->currentUser
            ];
            Tractor::create($tractorData);
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
