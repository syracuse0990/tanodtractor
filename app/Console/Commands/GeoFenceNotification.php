<?php

namespace App\Console\Commands;

use App\Helpers\NotificationHelper;
use App\Models\DeviceGeoFence;
use App\Models\Jimi;
use App\Models\Notification;
use App\Models\TractorBooking;
use App\Models\User;
use Illuminate\Console\Command;

class GeoFenceNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geo-fence-notification:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification if device goes out from geo fence.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $admin = User::where('role_id', User::ROLE_ADMIN)->latest('id')->first();
        $tractorBookings = TractorBooking::where('state_id', TractorBooking::STATE_ACCEPTED)->whereDate('date', date('Y-m-d'))->get();
        foreach ($tractorBookings as $key => $tractorBooking) {
            $device = $tractorBooking->device;
            $imeis =  explode(',', $device->imei_no);
            $apiData = (new Jimi())->getDeviceLocation($imeis);
            $deviceGeoFence = DeviceGeoFence::where(['imei' => $device->imei_no, 'state_id' => deviceGeoFence::STATE_ACTIVE])->whereDate('date', date('Y-m-d'))->first();

            if ($deviceGeoFence) {
                $api_data = $apiData['result'][0];
                $targetLat = $api_data['lat']; // Target latitude
                $targetLng = $api_data['lng']; // Target longitude
                $circleLat = $deviceGeoFence->latitude; // Circle center latitude
                $circleLng = $deviceGeoFence->longitude; // Circle center longitude
                $radius = ($deviceGeoFence->radius * 100) / 1000; // Circle radius in kilometers

                // Radius of the Earth in kilometers
                $earthRadius = 6371;

                // Convert latitude and longitude from degrees to radians
                $targetLatRad = deg2rad($targetLat);
                $targetLngRad = deg2rad($targetLng);
                $circleLatRad = deg2rad($circleLat);
                $circleLngRad = deg2rad($circleLng);

                // Calculate the Haversine distance
                $deltaLat = $targetLatRad - $circleLatRad;
                $deltaLng = $targetLngRad - $circleLngRad;
                $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
                    cos($circleLatRad) * cos($targetLatRad) *
                    sin($deltaLng / 2) * sin($deltaLng / 2);
                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                $distance = $earthRadius * $c;

                if ($distance > $radius) {
                    $user = $tractorBooking->createdBy->name ?? $tractorBooking->createdBy->email;

                    if ($tractorBooking->created_by) {
                        $notification = new Notification();
                        $notification->user_id = $tractorBooking->created_by;
                        $notification->title = 'Exited Geo Fence';
                        $notification->message = $tractorBooking?->tractor?->id_no . ' (' . $tractorBooking?->tractor?->model . ') tractor has exited the geofence.';
                        $notification->tractor_id = $tractorBooking?->tractor_id;
                        $notification->booking_id = $tractorBooking?->id;
                        $notification->device_id = $tractorBooking?->device_id;
                        $notification->is_read = Notification::IS_NOT_READ;
                        $notification->type_id = Notification::TYPE_EXIT_GEOFENCE;
                        $notification->save();
                    }
                    if ($admin) {
                        $adminNotification = new Notification();
                        $adminNotification->user_id = $admin->id;
                        $adminNotification->title = 'Exited Geo Fence';
                        $adminNotification->message = $tractorBooking?->tractor?->id_no . ' (' . $tractorBooking?->tractor?->model . ') tractor has exited the geofence.';
                        $adminNotification->tractor_id = $tractorBooking?->tractor_id;
                        $adminNotification->booking_id = $tractorBooking?->id;
                        $adminNotification->device_id = $tractorBooking?->device_id;
                        $adminNotification->is_read = Notification::IS_NOT_READ;
                        $adminNotification->type_id = Notification::TYPE_EXIT_GEOFENCE;
                        $adminNotification->save();
                    }

                    $notificationdata = [
                        'body' => $tractorBooking?->tractor?->id_no . ' (' . $tractorBooking?->tractor?->model . ') tractor has exited the geofence.',
                        'message' => 'Exited the geofence',
                        'notification_type' => Notification::TYPE_EXIT_GEOFENCE,
                        'user_id' => $tractorBooking->created_by,
                        'booking_id' => $tractorBooking->id,
                        'notification_id' => $notification->id
                    ];
                    $adminNotificationdata = [
                        'body' => $tractorBooking?->tractor?->id_no . ' (' . $tractorBooking?->tractor?->model . ') tractor has exited the geofence.',
                        'message' => 'Exited the geofence',
                        'notification_type' => Notification::TYPE_EXIT_GEOFENCE,
                        'user_id' => $admin->id,
                        'booking_id' => $tractorBooking->id,
                        'notification_id' => $adminNotification->id
                    ];

                    $fcm_token = $tractorBooking->createdBy->fcm_token;
                    if ($fcm_token) {
                        NotificationHelper::sendPushNotification($fcm_token, $notificationdata);
                    }
                    $admin_fcm_token = $admin->fcm_token;
                    if ($admin_fcm_token) {
                        NotificationHelper::sendPushNotification($admin_fcm_token, $adminNotificationdata);
                    }
                }
            }
        }
    }
}
