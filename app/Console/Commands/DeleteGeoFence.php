<?php

namespace App\Console\Commands;

use App\Models\DeviceGeoFence;
use App\Models\Jimi;
use Illuminate\Console\Command;

class DeleteGeoFence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete-geo-fence:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Geo Fence after one day.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $previousDate = date('Y-m-d', strtotime("-1 days"));
        $geoFences = DeviceGeoFence::where('state_id', DeviceGeoFence::STATE_ACTIVE)->where('date', '<=', $previousDate)->get();
        if (count($geoFences)) {
            $bar = $this->output->createProgressBar(count($geoFences));
            $bar->start();

            foreach ($geoFences as $key => $geoFence) {
                $existImeis = $geoFence->imei ? explode(',', $geoFence->imei) : [];
                $existGeoFenceIds = $geoFence->geo_fence_id ? explode(',', $geoFence->geo_fence_id) : [];
                foreach ($existImeis as $key => $imei) {
                    $callDeleteApi = (new Jimi())->deleteGeoFence($imei, $existGeoFenceIds[$key]);
                    if ($callDeleteApi['code'] == 0) {
                        $geoFence->state_id = DeviceGeoFence::STATE_DELETED;
                        $geoFence->save();
                    } else {
                        echo 'Delete geo fence failur. Code ' . $callDeleteApi['code'] . ',message ' . $callDeleteApi['message'] . ', geo fence ID ' . $geoFence->id . ', geo fence IMEI ' . $geoFence->imei;
                    }
                }
                $bar->advance();
            }
            $bar->finish();
        }
    }
}
