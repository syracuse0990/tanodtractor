<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Tractor;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class NotificationController
 * @package App\Http\Controllers
 */
class NotificationController extends Controller
{

    public function __invoke() {}

    public function sse()
    {

        $response =  new StreamedResponse(function () {
            while (true) {
                $notifications = Notification::where('user_id', Auth::id())->where('is_read', Notification::IS_NOT_READ)->latest('id')->get();
                // $notifications = Notification::where('user_id', Auth::id())->where('is_read', Notification::IS_NOT_READ)->latest('id')->limit(10)->get();

                if (!empty($notifications)) {
                    echo "data: " . json_encode($notifications) . "\n\n";
                } else {
                    echo "data: " . json_encode([]) . "\n\n";
                }

                ob_flush();
                flush();

                // Sleep for a few seconds before checking for new notifications
                sleep(60);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');
        return $response;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())->latest('id')->paginate();

        return view('notification.index', compact('notifications'))
            ->with('i', (request()->input('page', 1) - 1) * $notifications->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $notification = new Notification();
        return view('notification.create', compact('notification'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(Notification::$rules);

        $notification = Notification::create($request->all());

        return redirect()->route('notifications.index')
            ->with('success', 'Notification created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $notification = Notification::findorFail($id);

        $notification->is_read = Notification::IS_READ;
        $notification->save();

        return view('notification.show', compact('notification'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $notification = Notification::findorFail($id);

        return view('notification.edit', compact('notification'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Notification $notification
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Notification $notification)
    {
        request()->validate(Notification::$rules);

        $notification->update($request->all());

        return redirect()->route('notifications.index')
            ->with('success', 'Notification updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $notification = Notification::findorFail($id)->delete();

        return redirect()->route('notifications.index')
            ->with('success', 'Notification deleted successfully');
    }

    public function notificationData()
    {
        $notifications = Notification::where('user_id', Auth::id())->where('is_read', Notification::IS_NOT_READ)->latest('id')->limit(10)->get();
        if ($notifications->isEmpty()) {
            return response()->json([
                'status' => 'OK',
                'count' => 0,
                'html' => [],
                'message' => 'No notifications found.'
            ]);
        }
        $html = [];
        foreach ($notifications as $key => $notification) {
            if ($notification->geofence_id) {
                $message = $notification->type_id == Notification::TYPE_ENTER_GEOFENCE ? 'Entered the geozone.' : 'Exited the geozone.';
                $response['count'] = count($notifications);
                $html[] =
                    '<li class="mb-2">
                    <a class="dropdown-item border-radius-md p-1" href="' . route('device-geo-fences.show', [$notification->geofence_id, 'notification_id' => $notification->id]) . '"><div class="py-1">
                           <div class="notification d-flex align-items-center">
                               <h6 class="text-sm font-weight-normal mb-0">' . $notification->device?->imei_no . '</h6>
                               <p class="text-xs text-secondary mb-0 ms-4">
                               <i class="fa fa-clock me-1"></i>' .
                    date('Y-m-d h:i A', strtotime($notification->created_at)) .
                    '</p>
                           </div>
                           <p>' . $message . '</p>

                       </div>
                   </a>
               </li>';
            }
            if ($notification->tractor_id && $notification->type_id != Notification::TYPE_INACTIVE) {
                $response['count'] = count($notifications);
                $tractor = $notification?->tractor  ? $notification->tractor?->id_no : null;
                if ($tractor && $notification->tractor?->model) {
                    $tractor = $notification->tractor?->id_no . ' (' . $notification->tractor?->model . ')';
                }
                $html[] =
                    '<li class="mb-2">
                    <a class="dropdown-item border-radius-md p-1" href="' . route('tractors.show', [$notification->tractor_id, 'notification_id' => $notification->id]) . '"><div class="py-1">
                           <div class="notification d-flex align-items-center">
                               <h6 class="text-sm font-weight-normal mb-0">' . $tractor . '</h6>
                               <p class="text-xs text-secondary mb-0 ms-4">
                               <i class="fa fa-clock me-1"></i>' .
                    date('Y-m-d h:i A', strtotime($notification->created_at)) .
                    '</p>
                           </div>
                           <p>Tractor need maintenance.</p>

                       </div>
                   </a>
               </li>';
            }
            if ($notification->type_id == Notification::TYPE_INACTIVE) {
                $response['count'] = count($notifications);
                $html[] =
                    '<li class="mb-2">
                    <a class="dropdown-item border-radius-md p-1" href="' . route('tractors.show', [$notification->tractor_id, 'notification_id' => $notification->id]) . '"><div class="py-1">
                           <div class="notification d-flex align-items-center">
                               <h6 class="text-sm font-weight-normal mb-0">' . $notification->title . '</h6>
                               <p class="text-xs text-secondary mb-0 ms-4">
                               <i class="fa fa-clock me-1"></i>' .
                    date('Y-m-d h:i A', strtotime($notification->created_at)) .
                    '</p>
                           </div>
                           <p> ' . $notification->message . '</p>

                       </div>
                   </a>
               </li>';
            }
            if ($notification->type_id == Notification::TYPE_TICKET) {
                $response['count'] = count($notifications);
                $html[] =
                    '<li class="mb-2">
                    <a class="dropdown-item border-radius-md p-1" href="' . route('tickets.show', [$notification->ticket_id . '-' . $notification->id]) . '"><div class="py-1">
                           <div class="notification d-flex align-items-center">
                               <h6 class="text-sm font-weight-normal mb-0">' . $notification->title . '</h6>
                               <p class="text-xs text-secondary mb-0 ms-4">
                               <i class="fa fa-clock me-1"></i>' .
                    date('Y-m-d h:i A', strtotime($notification->created_at)) .
                    '</p>
                           </div>
                           <p> ' . $notification->message . '</p>

                       </div>
                   </a>
               </li>';
            }
        }



        $response['status'] = 'OK';
        $response['html'] = $html;
        return $response;
    }

    public function alert()
    {
        $admin = User::whereIn('role_id', [User::ROLE_ADMIN, User::ROLE_SUB_ADMIN])->first();

        // Check if the admin user exists
        if (!$admin) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'No admin user found.'
            ]);
        }

        $notifications = Notification::where('user_id', Auth::id())
            ->where('is_read', Notification::IS_NOT_CLOSED)
            ->where(function (Builder $query) {
                return $query->where('state_id', Notification::STATE_NOT_ALERTED)
                    ->orWhere('is_closed', Notification::IS_NOT_CLOSED);
            })
            ->whereIn('type_id', [
                Notification::TYPE_ENTER_GEOFENCE,
                Notification::TYPE_EXIT_GEOFENCE,
                Notification::TYPE_MAINTENANCE,
                Notification::TYPE_INACTIVE
            ])
            ->latest('id')
            ->get();

        // Check if notifications exist
        if ($notifications->isEmpty()) {
            return response()->json([
                'status' => 'OK',
                'count' => 0,
                'html' => [],
                'message' => 'No notifications found.'
            ]);
        }

        $alertHtml = [];
        $closeModalIds = [];

        foreach ($notifications as $key => $notification) {
            $user = $notification->booking?->createdBy?->name ?? $notification->booking?->createdBy?->email;
            $user = ($notification->booking?->createdBy?->role_id != User::ROLE_ADMIN) ? $user : null;
            $tractor = $notification?->tractor ? $notification->tractor?->id_no : null;
            if ($tractor && $notification->tractor?->model) {
                $tractor = $notification->tractor?->id_no . ' (' . $notification->tractor?->model . ')';
            }
            $url = 'javascript:void(0)';
            if ($notification->geofence_id) {
                $url = route('device-geo-fences.show', [$notification->geofence_id, 'notification_id' => $notification->id]);
            } elseif ($notification->tractor_id) {
                $url = route('tractors.show', [$notification->tractor_id, 'notification_id' => $notification->id]);
            } elseif ($notification->device_id) {
                $url = route('devices.show', [$notification->device_id, 'notification_id' => $notification->id]);
            }

            if ($notification->type_id == Notification::TYPE_EXIT_GEOFENCE) {
                $html = '<div class="position-relative" id="close_alert' . $notification->id . '">
                        <i class="fa-solid fa-xmark close-alert-btn" onClick="closeAlert(this)" data-id="' . $notification->id . '"></i>
                        <a href="' . $url . '"><div class="alert alert-danger alert-notification-div p-3 shadow me-2">
                        <div class="text-center">
                            <i class="fa-solid fa-triangle-exclamation h1 text-danger my-2 alert-close-icon"></i>
                        </div>
                        <div class="title-heading">
                            <h5 class="text-center">' . $notification?->title . '</h5>
                        </div>
                        <div class="d-flex justify-content-center">
                            <p class="mb-0">' . $notification?->device?->imei_no . '</p>
                        </div>';
                if ($user) {
                    $html .= '<div class="d-flex justify-content-center">
                            <h6>User :</h6>
                            <p class="mb-0">' . $user . '</p>
                        </div>';
                }
                if ($tractor) {
                    $html .= '<div class="d-flex justify-content-center">
                            <h6>Tractor :</h6>
                            <p class="mb-0">' . $tractor . '</p>
                        </div>';
                }
                $html .= '<div class="d-flex justify-content-center">
                        <h6>Date/Time :</h6>
                        <p class="mb-0">' . date('Y-m-d h:i A', strtotime($notification?->created_at)) . '</p>
                    </div>
                </div></a></div>';

                $alertHtml[] = $html;
            } elseif ($notification->type_id == Notification::TYPE_ENTER_GEOFENCE) {
                $closeModalId = $duration = null;
                $closeModalId = $notification->exit_id;
                $exitNotification = Notification::find($notification->exit_id);

                if ($exitNotification) {
                    $date1 = $notification?->created_at;
                    $date2 = $exitNotification?->created_at;
                    $interval = $date1->diff($date2);
                    $hours = $interval->h;
                    $minutes = $interval->i;
                    $seconds = $interval->s;

                    if ($hours) {
                        $duration = $hours . ':' . $minutes . ' Hour';
                    } elseif ($minutes) {
                        $duration = $minutes . ' Minutes';
                    } else {
                        $duration = $seconds . ' Seconds';
                    }
                }

                $html = '<div class="position-relative" id="close_alert' . $notification?->id . '">
                        <i class="fa-solid fa-xmark close-alert-btn" onClick="closeAlert(this)" data-id="' . $notification?->id . '"></i>
                        <a href="' . $url . '"><div class="alert alert-success alert-notification-div p-3 shadow me-2">
                        <div class="text-center">
                            <i class="fa-regular fa-circle-check h1 text-success my-3"></i>
                        </div>
                        <div class="title-heading">
                            <h5 class="text-center">' . $notification?->title . '</h5>
                        </div>
                        <div class="d-flex justify-content-center">
                            <p class="mb-0">' . $notification?->device?->imei_no . '</p>
                        </div>';
                if ($user) {
                    $html .= '<div class="d-flex justify-content-center">
                            <h6>User :</h6>
                            <p class="mb-0">' . $user . '</p>
                        </div>';
                }
                if ($tractor) {
                    $html .= '<div class="d-flex justify-content-center">
                            <h6>Tractor :</h6>
                            <p class="mb-0">' . $tractor . '</p>
                        </div>';
                }
                $html .= '<div class="d-flex justify-content-center">
                            <h6>In Time :</h6>
                            <p class="mb-0">' . date('Y-m-d h:i A', strtotime($notification?->created_at)) . '</p>
                        </div>';
                if ($exitNotification?->created_at) {
                    $html .= '<div class="d-flex justify-content-center">
                            <h6>Out Time :</h6>
                            <p class="mb-0">' . date('Y-m-d h:i A', strtotime($exitNotification?->created_at)) . '</p>
                        </div>';
                    $html .= '<div class="d-flex justify-content-center">
                            <h6>Duration :</h6>
                            <p class="mb-0">' . $duration . '</p>
                        </div>';
                }
                $html .= '</div></a></div>';

                $alertHtml[] = $html;
                if ($closeModalId) {
                    $closeModalIds[] = $closeModalId;
                }
            } elseif ($notification->type_id == Notification::TYPE_MAINTENANCE) {
                $html = '<div class="position-relative" id="close_alert' . $notification->id . '">
                        <i class="fa-solid fa-xmark close-alert-btn" onClick="closeAlert(this)" data-id="' . $notification->id . '"></i>
                        <a href="' . $url . '"><div class="alert alert-danger alert-notification-div p-3 shadow me-2">
                        <div class="text-center">
                            <i class="fa-solid fa-triangle-exclamation h1 text-danger my-2 alert-close-icon"></i>
                        </div>
                        <div class="title-heading">
                            <h5 class="text-center">' . $notification?->title . '</h5>
                        </div>
                        <div class="d-flex justify-content-center">
                            <p class="mb-0">' . $notification?->device?->imei_no . '</p>
                        </div>';
                if ($user) {
                    $html .= '<div class="d-flex justify-content-center">
                            <h6>User :</h6>
                            <p class="mb-0">' . $user . '</p>
                        </div>';
                }
                if ($tractor) {
                    $html .= '<div class="d-flex justify-content-center">
                            <h6>Tractor :</h6>
                            <p class="mb-0">' . $tractor . '</p>
                        </div>';
                }
                $html .= '<div class="d-flex justify-content-center">
                        <h6>Date/Time :</h6>
                        <p class="mb-0">' . date('Y-m-d h:i A', strtotime($notification?->created_at)) . '</p>
                    </div>
                </div></a></div>';

                $alertHtml[] = $html;
            } elseif ($notification->type_id == Notification::TYPE_INACTIVE) {
                $html = '<div class="position-relative" id="close_alert' . $notification->id . '">
                        <i class="fa-solid fa-xmark close-alert-btn" onClick="closeAlert(this)" data-id="' . $notification->id . '"></i>
                        <a href="' . $url . '"><div class="alert alert-danger alert-notification-div p-3 shadow me-2">
                        <div class="text-center">
                            <i class="fa-solid fa-triangle-exclamation h1 text-danger my-2 alert-close-icon"></i>
                        </div>
                        <div class="title-heading">
                            <h5 class="text-center">' . $notification?->title . '</h5>
                        </div>
                        <div class="d-flex justify-content-center">
                            <p class="mb-0">' . $notification?->device?->imei_no . '</p>
                        </div>';
                $html .= '<div class="d-flex justify-content-center">
                        <p class="mb-0">' . $notification->message . '</p>
                    </div>
                </div></a></div>';

                $alertHtml[] = $html;
            }

            $notification->state_id = Notification::STATE_ALERTED;
            $notification->save();
        }

        $clearAll = '<div class="alert alert-danger alert-notification-div p-3 shadow me-2">
                        <div class="text-center">
                            <button class="btn text-danger fw-bold w-100" id="closeAllBtn">Close All</button>
                        </div>
                    </div>';

        return response()->json([
            'status' => 'OK',
            'count' => count($notifications),
            'html' => $alertHtml,
            'closeModalIds' => $closeModalIds,
            'clearAll' => $clearAll
        ]);
    }


    public function closeAlert(Request $request)
    {
        $notification = Notification::findorFail($request->id);
        $notification->is_closed = Notification::IS_CLOSED;
        $notification->save();

        $response['status'] = 'OK';
        return $response;
    }

    public function maintenanceNotification(Request $request)
    {
        $tractor = Tractor::findorFail($request->id);
        $response['status'] = 'NOK';
        if ($tractor->last_alert_hours >= 50 && $tractor->last_alert_hours <= 100 && $tractor->first_alert != Tractor::STATE_ACTIVE) {
            $response['status'] = 'ALERT';
        } else {
            $diff = $tractor->total_distance - $tractor?->last_alert_hours;
            if ($diff >= 100 && !empty($tractor->running_km)) {
                $response['status'] = 'ALERT';
            }
        }
        return $response;
    }

    public function closeAllAlerts(Request $request)
    {
        $response['status'] = 'NOK';

        $notifications = Notification::where('user_id', $request->user_id)
            ->where('is_read', Notification::IS_NOT_CLOSED)
            ->where(function (Builder $query) {
                return $query->where('state_id', Notification::STATE_NOT_ALERTED)
                    ->orWhere('is_closed', Notification::IS_NOT_CLOSED);
            })
            ->whereIn('type_id', [
                Notification::TYPE_ENTER_GEOFENCE,
                Notification::TYPE_EXIT_GEOFENCE,
                Notification::TYPE_MAINTENANCE,
                Notification::TYPE_INACTIVE
            ])->update([
                'is_closed' => Notification::IS_CLOSED
            ]);
        if ($notifications) {
            $response['status'] = 'OK';
        }
        return $response;
    }
}
