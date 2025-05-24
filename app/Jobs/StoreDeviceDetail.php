<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\Jimi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StoreDeviceDetail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $devices = Device::whereNull('activation_time')->where('is_check', 0)->latest('id')->get();

        foreach ($devices as $device) {
            $apiData = (new Jimi())->getDeviceDetail($device->imei_no);
            Log::debug(json_encode($apiData));
            if (!empty($apiData['result']['activationTime'])) {
                $activationTime = $apiData['result']['activationTime'];

                if ($device->activation_time !== $activationTime) {
                    $device->update(['activation_time' => $activationTime]);
                }
            }
            $device->update(['is_check' => 1]);
        }
    }
}
