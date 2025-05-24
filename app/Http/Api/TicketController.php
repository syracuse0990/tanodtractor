<?php

namespace App\Http\Api;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Mail\TicketUpdateMail;
use App\Models\Alert;
use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TractorGroup;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Class TicketController
 * @package App\Http\Controllers
 */
class TicketController extends Controller
{

  public function index(Request $request)
  {
    try {
      $request['records_per_page'] = isset($request->records_per_page) ? $request->records_per_page : 10;
      $request['page_no'] = isset($request->page_no) ? $request->page_no : 1;
      $tickets = Ticket::query();
      if (Auth::user()->role_id == User::ROLE_FARMER) {
        $tickets->where('created_by', Auth::id());
      }
      $tickets = $tickets->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
      $totalCount = $tickets->total();
      $total_pages = ceil($totalCount / $request->records_per_page);
      $data = [
        'tickets' => $tickets->all(),
        'page_no' => $request->page_no,
        'total_entries' => $totalCount,
        'total_pages' => $total_pages
      ];
      return returnSuccessResponse('Get tickets list successfully.', $data);
    } catch (\Exception $e) {
      return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => (object)[]]);
    }
  }

  public function store(Request $request)
  {
    $request->validate([
      'title' => 'required',
      'description' => 'required'
    ]);
    try {
      $ticketData = $request->all();
      $ticket = Ticket::create($ticketData);
      //Notification to admin
      $admin = User::where('role_id', User::ROLE_ADMIN)->first();
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
      return returnSuccessResponse('Ticket created successfully.', $ticket);
    } catch (\Exception $e) {
      return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => (object)[]]);
    }
  }

  public function update(Request $request)
  {
    $request->validate([
      'id' => 'required',
      'title' => 'required',
      'description' => 'required'
    ]);
    try {
      $ticketData = $request->all();
      $ticket = Ticket::find($request->id);
      if (!in_array($ticket->state_id, [Ticket::STATE_ACTIVE])) {
        return  response()->json(['status' => false, 'message' => '403,You are not allowed to perform this action', 'data' => (object)[]], 403);
      }
      $ticket->update($ticketData);
      return returnSuccessResponse('Ticket updated successfully.', $ticket);
    } catch (\Exception $e) {
      return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => (object)[]]);
    }
  }

  public function destroy(Request $request)
  {
    $request->validate([
      'id' => 'required'
    ]);
    try {
      $ticket = Ticket::find($request->id);
      if (!$ticket) {
        return  response()->json(['status' => false, 'message' => 'Ticked not found.', 'data' => (object)[]]);
      }
      if (!in_array($ticket->state_id, [Ticket::STATE_ACTIVE])) {
        return  response()->json(['status' => false, 'message' => '403,You are not allowed to perform this action', 'data' => (object)[]], 403);
      }
      $ticket->state_id = Ticket::STATE_DELETED;
      $ticket->save();
      return returnSuccessResponse('Ticket deleted successfully.', $ticket);
    } catch (\Exception $e) {
      return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => (object)[]]);
    }
  }

  public function show(Request $request)
  {
    $request->validate([
      'id' => 'required'
    ]);
    try {
      $ticket = Ticket::find($request->id);
      if (!$ticket) {
        return  response()->json(['status' => false, 'message' => 'Ticked not found.', 'data' => (object)[]]);
      }
      return returnSuccessResponse('Get ticket details successfully.', $ticket);
    } catch (\Exception $e) {
      return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => (object)[]]);
    }
  }

  public function changeState(Request $request)
  {
    $request->validate([
      'id' => 'required',
      'state' => 'required',
      'conclusion' => Rule::requiredIf(in_array($request->state, [Ticket::STATE_COMPLETED, Ticket::STATE_REJECTED]))
    ]);
    try {
      $ticket = Ticket::findorFail($request->id);
      if (!$ticket) {
        return  response()->json(['status' => false, 'message' => 'Ticked not found.', 'data' => (object)[]]);
      }
      if (in_array($ticket->state_id, [Ticket::STATE_COMPLETED, Ticket::STATE_REJECTED]) || Auth::user()->role_id != User::ROLE_ADMIN) {
        return  response()->json(['status' => false, 'message' => '403,You are not allowed to perform this action.', 'data' => (object)[]]);
      }
      if (in_array($request->state, [Ticket::STATE_COMPLETED, Ticket::STATE_REJECTED])) {
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
      return returnSuccessResponse('Ticket status updated successfully.', $ticket);
    } catch (\Exception $e) {
      return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => (object)[]]);
    }
  }
}
