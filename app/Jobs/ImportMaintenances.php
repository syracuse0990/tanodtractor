<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\Maintenance;
use App\Models\Tractor;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class ImportMaintenances implements ShouldQueue
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
            if (empty($imei)) {
                continue;
            }
            $tractor = Tractor::where('imei', $imei)->latest('id')->first();
            if (empty($tractor)) {
                continue;
            }

            $maintenanceExists = Maintenance::where('tractor_ids', $tractor?->id)->whereNotIn('state_id', [Maintenance::STATE_COMPLETED, Maintenance::STATE_CANCELLED])->first();
            if ($maintenanceExists) {
                continue;
            }

            $maintenance_date = (isset($data[1]) && !empty($data[1])) ? date('Y-m-d H:i', strtotime(trim($data[1]))) : null;
            if (strtotime($maintenance_date) < strtotime(date('Y-m-d H:i'))) {
                continue;
            }

            $phoneNumber = (isset($data[6]) && !empty($data[6])) ? trim($data[6]) : null;

            if ($phoneNumber && !preg_match('/^[1-9]\d{9}$/', $phoneNumber)) {
                throw new Exception("Inavlid phone format : $phoneNumber");
                continue;
            }
            $maintenanceData = [
                'tractor_ids' => $tractor?->id ?? null,
                'maintenance_date' => $maintenance_date,
                'tech_name' => (isset($data[2]) && !empty($data[2])) ? trim($data[2]) : null,
                'tech_email' => (isset($data[3]) && !empty($data[3])) ? trim($data[3]) : null,
                'tech_iso_code' => (isset($data[4]) && !empty($data[4])) ? trim($data[4]) : null,
                'tech_phone_code' => (isset($data[5]) && !empty($data[5])) ? trim($data[5]) : null,
                'tech_number' => $phoneNumber,
            ];

            Maintenance::create($maintenanceData);

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
