<?php

namespace App\Console\Commands;

use App\Helpers\NotificationHelper;
use App\Models\AssignedGroup;
use App\Models\Maintenance;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\Tractor;
use App\Models\TractorGroup;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;

class CheckDistance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check-distance:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check device travel distance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $notifyUsers = User::whereIn('role_id', [User::ROLE_ADMIN, User::ROLE_GOVERNMENT, User::ROLE_SUB_ADMIN])->get();
            $tractors = Tractor::get();

            foreach ($tractors as $tractor) {
                $firstMaintenaceHr = $tractor->first_maintenance_hr ?? 50;
                $subsequentMaintenaceHr = $tractor->maintenance_kilometer ?? 100;

                $maintenance = Maintenance::where(['tractor_ids' => $tractor?->id, 'state_id' => Maintenance::STATE_DOCUMENTATION])->latest('id')->first();
                if ($maintenance) {
                    $oldNotification = Notification::where(['tractor_id' => $tractor?->id, 'type_id' => Notification::TYPE_MAINTENANCE])->whereDate('created_at', date('Y-m-d'))->update([
                        'state_id' => Notification::IS_CLOSED,
                        'is_closed' => Notification::IS_CLOSED,
                    ]);
                } else {
                    if ($tractor->running_km >= $firstMaintenaceHr && $tractor->running_km <= $subsequentMaintenaceHr && $tractor->first_alert != Tractor::STATE_ACTIVE) {
                        $tractor->first_alert = Notification::IS_CLOSED;
                        $tractor->last_alert_hours = $tractor?->total_distance;
                        $tractor->Save();
                        //Notifications
                        foreach ($notifyUsers as $admin) {
                            if (in_array($admin->role_id, [User::ROLE_SUB_ADMIN])) {
                                $assignedGroups = AssignedGroup::where('user_id', $admin->id)->pluck('group_id')->toArray();
                                $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                                $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
                                $tractorIds = multiDimToSingleDim($tractorIds);
                                if (!in_array($tractor->id, $tractorIds)) {
                                    continue;
                                }
                            }
                            $oldNotification = Notification::where(['tractor_id' => $tractor?->id, 'type_id' => Notification::TYPE_MAINTENANCE, 'user_id' => $admin->id])->whereDate('created_at', date('Y-m-d'))->latest('id')->first();
                            if ($oldNotification) {
                                $oldNotification->state_id = Notification::IS_CLOSED;
                                $oldNotification->is_closed = Notification::IS_CLOSED;
                                $oldNotification->save();
                            }
                            $notification = new Notification();
                            $notification->user_id = $admin->id;
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
                                'user_id' => $admin->id,
                                'tractor_id' => $tractor->id,
                                'notification_id' => $notification->id
                            ];

                            $fcm_token = $admin->fcm_token;
                            if ($fcm_token) {
                                NotificationHelper::sendPushNotification($fcm_token, $notificationdata);
                            }

                            $ticket = Ticket::create([
                                'title' => 'Maintenance Required',
                                'description' => $tractor?->id_no . ' (' . $tractor?->model . ') tractor has completed the maintenance kilometer.',
                            ]);
                            $notification = new Notification();
                            $notification->user_id = $admin?->id;
                            $notification->title = 'New ticket received';
                            $notification->message = $ticket->title;
                            $notification->is_read = Notification::IS_NOT_READ;
                            $notification->type_id = Notification::TYPE_TICKET;
                            $notification->ticket_id = $ticket->id;
                            $notification->save();
                            if ($admin->fcm_token) {
                                $notificationData = [
                                    'body' => 'New ticket received',
                                    'message' => $ticket->title,
                                    'notification_type' => Notification::TYPE_TICKET,
                                    'user_id' => $admin->id,
                                    'ticket_id' => $ticket?->id,
                                    'notification_id' => $notification->id
                                ];
                                $fcm_token = $admin->fcm_token;
                                if ($fcm_token) {
                                    NotificationHelper::sendTicketNotification($fcm_token, $notificationData);
                                }
                            }
                        }
                    } else {
                        $diff = $tractor->total_distance - $tractor?->last_alert_hours;
                        if ($diff >= $subsequentMaintenaceHr && !empty($tractor->running_km)) {
                            // $tractor->total_distance = $tractor->total_distance + 100;
                            $tractor->last_alert_hours = $tractor?->total_distance;
                            $tractor->Save();

                            //Notifications
                            foreach ($notifyUsers as $admin) {
                                if (in_array($admin->role_id, [User::ROLE_SUB_ADMIN])) {
                                    $assignedGroups = AssignedGroup::where('user_id', $admin->id)->pluck('group_id')->toArray();
                                    $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                                    $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
                                    $tractorIds = multiDimToSingleDim($tractorIds);
                                    if (!in_array($tractor->id, $tractorIds)) {
                                        continue;
                                    }
                                }
                                $oldNotification = Notification::where(['tractor_id' => $tractor?->id, 'type_id' => Notification::TYPE_MAINTENANCE, 'user_id' => $admin->id])->whereDate('created_at', date('Y-m-d'))->latest('id')->first();
                                if ($oldNotification) {
                                    $oldNotification->state_id = Notification::IS_CLOSED;
                                    $oldNotification->is_closed = Notification::IS_CLOSED;
                                    $oldNotification->save();
                                }
                                $notification = new Notification();
                                $notification->user_id = $admin->id;
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
                                    'user_id' => $admin->id,
                                    'tractor_id' => $tractor->id,
                                    'notification_id' => $notification->id
                                ];


                                $fcm_token = $admin->fcm_token;
                                if ($fcm_token) {
                                    NotificationHelper::sendPushNotification($fcm_token, $notificationdata);
                                }

                                $ticket = Ticket::create([
                                    'title' => 'Maintenance Required',
                                    'description' => $tractor?->id_no . ' (' . $tractor?->model . ') tractor has completed the maintenance kilometer.',
                                ]);
                                $notification = new Notification();
                                $notification->user_id = $admin?->id;
                                $notification->title = 'New ticket received';
                                $notification->message = $ticket->title;
                                $notification->is_read = Notification::IS_NOT_READ;
                                $notification->type_id = Notification::TYPE_TICKET;
                                $notification->ticket_id = $ticket->id;
                                $notification->save();
                                if ($admin->fcm_token) {
                                    $notificationData = [
                                        'body' => 'New ticket received',
                                        'message' => $ticket->title,
                                        'notification_type' => Notification::TYPE_TICKET,
                                        'user_id' => $admin->id,
                                        'ticket_id' => $ticket?->id,
                                        'notification_id' => $notification->id
                                    ];
                                    $fcm_token = $admin->fcm_token;
                                    if ($fcm_token) {
                                        NotificationHelper::sendTicketNotification($fcm_token, $notificationdata);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            echo "Completed";
        } catch (Exception $e) {
            echo 'An error occur ' . $e->getMessage();
        }
    }
}
