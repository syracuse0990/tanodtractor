<?php

namespace App\Console\Commands;

use App\Helpers\NotificationHelper;
use App\Models\Alert;
use App\Models\Jimi;
use App\Models\Maintenance;
use App\Models\Notification;
use App\Models\TotalHours;
use App\Models\Tractor;
use App\Models\TractorBooking;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateTractorDistance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-tractor-distance:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daily update tractor kilometers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $bookings = TractorBooking::where([
                'state_id' => TractorBooking::STATE_ACCEPTED,
                'type_id' => TractorBooking::STATE_INACTIVE
            ])->latest('id')->get();
            foreach ($bookings as $booking) {
                $tractor = $booking->tractor;
                $device = $booking->device;
                echo $booking->device?->imei_no;
                echo "\n";
                if ($device) {
                    $alerts = Alert::where([
                        'imei' => $device->imei_no,
                        'type_id' => TractorBooking::STATE_INACTIVE
                    ])->whereIn('alarm_type', ['197', '198'])->whereDate('alarm_time', $booking->date)->orderBy('id', 'ASC')->get();
                    $lastOnEvent = null;
                    $totalSecondsDifference = 0;
                    foreach ($alerts as $key => $alert) {
                        if ($alert->alarm_type == '197') {
                            $lastOnEvent = $alert;
                        } elseif ($alert->alarm_type == '198' && $lastOnEvent !== null) {
                            $onTimestamp = strtotime($lastOnEvent->alarm_time);
                            $offTimestamp = strtotime($alert->alarm_time);

                            $secondsDifference = $offTimestamp - $onTimestamp;
                            $totalSecondsDifference += $secondsDifference;

                            $lastOnEvent = null;
                        } else {
                            continue;
                        }
                        $alert->type_id = TractorBooking::STATE_ACTIVE;
                        $alert->save();
                    }
                    $minutes = floor(($totalSecondsDifference / 60) % 60);
                    $hours = intdiv($minutes, 60) . '.' . ($minutes % 60);
                    if ($tractor) {
                        if (empty($tractor->running_km)) {
                            $tractor->running_km = 0;
                        }
                        if (empty($tractor->total_distance)) {
                            $tractor->total_distance = 0;
                        }
                        $tractor->running_km = $tractor->running_km + $hours;
                        $tractor->total_distance = $tractor->total_distance + $hours;
                        $tractor->save();

                        $totalHours = TotalHours::where([
                            'tractor_id' => $tractor?->id,
                            'user_id' => $booking->created_by
                        ])->first();
                        if ($totalHours) {
                            $totalHours->hours = $tractor->total_distance;
                            $totalHours->save();
                        } else {
                            $totalHours = new TotalHours();
                            $totalHours->tractor_id = $tractor?->id;
                            $totalHours->user_id = $booking->created_by;
                            $totalHours->hours = $tractor->total_distance;
                            $totalHours->save();
                        }

                        $maintenance = Maintenance::where(['tractor_ids' => $tractor?->id, 'state_id' => Maintenance::STATE_DOCUMENTATION])->latest('id')->first();
                        if ($maintenance) {
                            $oldNotification = Notification::where(['tractor_id' => $tractor?->id, 'type_id' => Notification::TYPE_MAINTENANCE, 'user_id' => $booking->created_by])->whereDate('created_at', date('Y-m-d'))->update([
                                'state_id' => Notification::IS_CLOSED,
                                'is_closed' => Notification::IS_CLOSED,
                            ]);
                        } else {
                            if ($tractor->last_alert_hours >= 50 && $tractor->last_alert_hours <= 100 && $tractor->first_alert != Tractor::STATE_ACTIVE) {
                                $oldNotification = Notification::where(['tractor_id' => $tractor?->id, 'type_id' => Notification::TYPE_MAINTENANCE, 'user_id' => $booking->created_by])->whereDate('created_at', date('Y-m-d'))->latest('id')->first();
                                if ($oldNotification) {
                                    $oldNotification->state_id = Notification::IS_CLOSED;
                                    $oldNotification->is_closed = Notification::IS_CLOSED;
                                    $oldNotification->save();
                                }
                                $notification = new Notification();
                                $notification->user_id = $booking->created_by;
                                $notification->title = 'Maintenance Required';
                                $notification->message = $tractor?->id_no . ' (' . $tractor?->model . ') tractor has completed the maintenance kilometer.';
                                $notification->tractor_id = $tractor->id;
                                $notification->is_read = Notification::IS_NOT_READ;
                                $notification->type_id = Notification::TYPE_MAINTENANCE;
                                $notification->save();

                                $notificationdata = [
                                    'body' => $tractor?->id_no . ' (' . $tractor?->model . ') tractor has completed the maintenance kilometer.',
                                    'message' => 'Maintenance Required',
                                    'notification_type' => Notification::TYPE_MAINTENANCE,
                                    'user_id' => $booking->created_by,
                                    'tractor_id' => $tractor->id,
                                    'notification_id' => $notification->id
                                ];

                                $fcm_token = $booking->createdBy?->fcm_token;
                                if ($fcm_token) {
                                    NotificationHelper::sendPushNotification($fcm_token, $notificationdata);
                                }
                            } else {
                                $diff = $tractor->total_distance - $tractor?->last_alert_hours;
                                if ($diff >= 100 && !empty($tractor->running_km)) {
                                    $oldNotification = Notification::where(['tractor_id' => $tractor?->id, 'type_id' => Notification::TYPE_MAINTENANCE, 'user_id' => $booking->created_by])->whereDate('created_at', date('Y-m-d'))->latest('id')->first();
                                    if ($oldNotification) {
                                        $oldNotification->state_id = Notification::IS_CLOSED;
                                        $oldNotification->is_closed = Notification::IS_CLOSED;
                                        $oldNotification->save();
                                    }
                                    $notification = new Notification();
                                    $notification->user_id = $booking->created_by;
                                    $notification->title = 'Maintenance Required';
                                    $notification->message = $tractor?->id_no . ' (' . $tractor?->model . ') tractor has completed the maintenance kilometer.';
                                    $notification->tractor_id = $tractor->id;
                                    $notification->is_read = Notification::IS_NOT_READ;
                                    $notification->type_id = Notification::TYPE_MAINTENANCE;
                                    $notification->save();

                                    $notificationdata = [
                                        'body' => $tractor?->id_no . ' (' . $tractor?->model . ') tractor has completed the maintenance kilometer.',
                                        'message' => 'Maintenance Required',
                                        'notification_type' => Notification::TYPE_MAINTENANCE,
                                        'user_id' => $booking->created_by,
                                        'tractor_id' => $tractor->id,
                                        'notification_id' => $notification->id
                                    ];
                                    
                                    $fcm_token = $booking->createdBy?->fcm_token;
                                    if ($fcm_token) {
                                        NotificationHelper::sendPushNotification($fcm_token, $notificationdata);
                                    }
                                }
                            }
                        }
                    }
                    $booking->type_id = TractorBooking::STATE_ACTIVE;
                    $booking->save();
                }
                $totalSecondsDifference = 0;
            }
            echo "Completed";
        } catch (Exception $e) {
            echo 'An error occurred: ' . $e->getMessage();
        }
    }
}
