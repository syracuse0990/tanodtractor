<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Mail\TicketUpdateMail;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * Class TicketController
 * @package App\Http\Controllers
 */
class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tickets = Ticket::orderBy('state_id', 'ASC')->orderBy('id', 'DESC')->paginate();

        return view('ticket.index', compact('tickets'))
            ->with('i', (request()->input('page', 1) - 1) * $tickets->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $ticket = new Ticket();
        return view('ticket.create', compact('ticket'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(Ticket::$rules);

        $ticket = Ticket::create($request->all());

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $ids = explode('-', $id);
        $ticket = Ticket::find($ids[0]);
        if ($ids[1]) {
            $notification = Notification::findorFail($ids[1]);
            $notification->is_read = Notification::IS_READ;
            $notification->save();
        }
        return view('ticket.show', compact('ticket'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $ticket = Ticket::find($id);

        return view('ticket.edit', compact('ticket'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Ticket $ticket
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Ticket $ticket)
    {
        request()->validate(Ticket::$rules);

        $ticket->update($request->all());

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $ticket = Ticket::find($id)->delete();

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket deleted successfully');
    }

    public function changeState(Request $request)
    {
        try {
            $ticket = Ticket::findorFail($request->id);
            if (!$ticket) {
                return response()->json(['error' => 'Ticket not found'], 404);
            }
            if (in_array($request->state, [Ticket::STATE_COMPLETED, Ticket::STATE_REJECTED])) {
                $rules = ['conclusion' => 'required'];
                $validator = Validator::make($request->all(), $rules);
                if ($validator->fails()) {
                    return response()->json(['errors' => $validator->errors()], 422);
                }
                $ticket->conclusion = $request->conclusion;
                $ticket->state_id = $request->state;
                $ticket->save();
                if ($ticket->state_id == Ticket::STATE_COMPLETED) {
                    $title = 'Ticket status updated to completed.';
                    $message = $ticket->title . ' Ticket status has been updated to completed.';
                } elseif ($ticket->state_id == Ticket::STATE_REJECTED) {
                    $title = 'Ticket status updated to rejected.';
                    $message = $ticket->title . ' Ticket status has been updated to rejected.';
                }
            } else {
                $ticket->state_id = $request->state;
                $ticket->save();
                $title = 'Ticket status updated to in progress.';
                $message = $ticket->title . ' Ticket status has been updated to in progress.';
            }

            $notification = new Notification();
            $notification->user_id = $ticket->created_by;
            $notification->title = $title;
            $notification->message = $message;
            $notification->is_read = Notification::IS_NOT_READ;
            $notification->type_id = Notification::TYPE_TICKET;
            $notification->ticket_id = $ticket->id;
            $notification->save();

            $notificationdata = [
                'body' => $message,
                'message' => $title,
                'notification_type' => Notification::TYPE_TICKET,
                'user_id' => $ticket->created_by,
                'notification_id' => $notification->id,
                'ticket_id' => $ticket->id
            ];

            $fcm_token = $ticket->createdBy?->fcm_token;
            if ($fcm_token) {
                NotificationHelper::sendTicketNotification($fcm_token, $notificationdata);
            }
            Mail::to($ticket->createdBy?->email)->send(new TicketUpdateMail($title, $message, $ticket->createdBy, $ticket));
            return redirect()->back();
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occured ' . $e->getMessage()], 404);
        }
    }
}
