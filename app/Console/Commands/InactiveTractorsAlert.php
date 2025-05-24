<?php

namespace App\Console\Commands;

use App\Helpers\NotificationHelper;
use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\Jimi;
use App\Models\Notification;
use App\Models\Tractor;
use App\Models\TractorBooking;
use App\Models\TractorGroup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class InactiveTractorsAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inactive-tractors-alert:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $roleId = Auth::user()->role_id;
        $userId = Auth::id();
        $gmt_date = gmdate('Y-m-d H:i:s');

        // Fetch devices
        $query = Device::select('id', 'imei_no', 'device_modal', 'device_name', 'subscription_expiration', 'expiration_date', 'sim')
            ->whereNotNull('activation_time');

        if ($roleId == User::ROLE_SUB_ADMIN) {
            $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id');
            $deviceIds = TractorGroup::whereIn('id', $assignedGroups)->pluck('device_ids')->flatten()->unique();
            $query->whereIn('id', $deviceIds);
        }

        $devices = $query->get();
        $imeis = $devices->pluck('imei_no')->toArray();
        $imeisChunks = array_chunk($imeis, 99);

        // Fetch admins once
        $notifyUsers = User::whereIn('role_id', [User::ROLE_ADMIN, User::ROLE_GOVERNMENT, User::ROLE_SUB_ADMIN])->get()->keyBy('id');

        foreach ($imeisChunks as $chunk) {
            $apiData = (new Jimi())->getDeviceLocation($chunk)['result'] ?? [];
            $apiData = array_column($apiData, null, 'imei');

            foreach ($devices as $device) {
                if (!isset($apiData[$device->imei_no])) {
                    continue;
                }

                $tractor = Tractor::where('device_id', $device->id)->first();
                if (!$tractor) continue;

                $days = floor((strtotime($gmt_date) - strtotime($apiData[$device->imei_no]['hbTime'])) / 86400);
                if ($days <= 5) continue;

                foreach ($notifyUsers as $admin) {
                    if ($admin->role_id == User::ROLE_SUB_ADMIN) {
                        // Fetch groups once per sub-admin
                        static $subAdminGroups = [];
                        if (!isset($subAdminGroups[$admin->id])) {
                            $assignedGroups = AssignedGroup::where('user_id', $admin->id)->pluck('group_id');
                            $subAdminGroups[$admin->id] = TractorGroup::whereIn('id', $assignedGroups)->pluck('tractor_ids')->flatten()->unique()->toArray();
                        }

                        if (!in_array($tractor->id, $subAdminGroups[$admin->id])) {
                            continue;
                        }
                    }

                    // Close previous notifications
                    Notification::where([
                        'tractor_id' => $tractor->id,
                        'type_id' => Notification::TYPE_INACTIVE,
                        'user_id' => $admin->id
                    ])->whereDate('created_at', now())->update([
                        'state_id' => Notification::IS_CLOSED,
                        'is_closed' => Notification::IS_CLOSED
                    ]);

                    // Generate notification message
                    $tractorName = $tractor->id_no ?: $tractor->no_plate;
                    if ($tractorName && !empty($tractor->model)) {
                        $tractorName .= " ({$tractor->model})";
                    }

                    $notification = Notification::create([
                        'user_id' => $admin->id,
                        'title' => 'Tractor Inactive',
                        'message' => "$tractorName tractor has been Offline: $days days.",
                        'tractor_id' => $tractor->id,
                        'is_read' => Notification::IS_NOT_READ,
                        'type_id' => Notification::TYPE_INACTIVE
                    ]);

                    // Send push notification
                    if ($admin->fcm_token) {
                        NotificationHelper::sendPushNotification($admin->fcm_token, [
                            'body' => "$tractorName tractor has been Offline: $days days.",
                            'message' => 'Tractor Inactive',
                            'notification_type' => Notification::TYPE_INACTIVE,
                            'user_id' => $admin->id,
                            'tractor_id' => $tractor->id,
                            'notification_id' => $notification->id
                        ]);
                    }
                }
            }
        }
    }
}
