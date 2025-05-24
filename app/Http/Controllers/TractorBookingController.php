<?php

namespace App\Http\Controllers;

use App\Models\AssignedGroup;
use App\Models\Notification;
use App\Models\Slot;
use App\Models\Tractor;
use App\Models\TractorBooking;
use App\Models\TractorGroup;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class TractorBookingController
 * @package App\Http\Controllers
 */
class TractorBookingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $tractor_id = null;
        if (!empty($request->tractor_id)) {
            $tractor_id = $request->tractor_id;
        }

        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
            $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
            $tractorIds = multiDimToSingleDim($tractorIds);
            $deviceIds = multiDimToSingleDim($deviceIds);
        }

        $tractors = Tractor::select('id', 'id_no', 'brand', 'model');
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $tractors = $tractors->whereIn('id', $tractorIds);
        }
        $tractors = $tractors->get();
        $bookingsData = TractorBooking::query();
        if ($tractor_id) {
            $bookingsData = $bookingsData->where('tractor_id', $tractor_id);
        } else {
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $bookingsData = $bookingsData->whereIn('tractor_id', $tractorIds)->orWhereIn('device_id', $deviceIds);
            }
        }
        $bookingsData = $bookingsData->whereNotIn('state_id', [TractorBooking::STATE_DELETED])->orderBy('state_id', 'ASC')->orderBy('id', 'DESC')->get();
        return view('tractor-booking.index', compact('tractors', 'tractor_id', 'bookingsData'));
    }

    public function bookingList(Request $request)
    {
        $tractor_id = null;
        if (!empty($request->tractor_id)) {
            $tractor_id = $request->tractor_id;
        }

        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
            $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
            $tractorIds = multiDimToSingleDim($tractorIds);
            $deviceIds = multiDimToSingleDim($deviceIds);
        }
        $bookings = TractorBooking::query();
        if ($tractor_id) {
            $bookings = $bookings->where('tractor_id', $tractor_id);
        } else {
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $bookings = $bookings->whereIn('tractor_id', $tractorIds)->orWhereIn('device_id', $deviceIds);
            }
        }

        $bookings = $bookings->whereNotIn('state_id', [TractorBooking::STATE_DELETED])->orderBy('state_id', 'ASC')->orderBy('id', 'DESC')->paginate();

        $tractors = Tractor::select('id', 'id_no', 'brand', 'model');
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $tractors = $tractors->whereIn('id', $tractorIds);
        }
        $tractors = $tractors->get();

        return view('tractor-booking.booking-list', compact('bookings', 'tractors', 'tractor_id'))
            ->with('i', (request()->input('page', 1) - 1) * $bookings->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort(403, 'You are not allowed to perform this action!!');
        $tractorBooking = new TractorBooking();
        return view('tractor-booking.create', compact('tractorBooking'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(TractorBooking::$rules);

        $tractorBooking = TractorBooking::create($request->all());

        return redirect()->route('tractor-bookings.index')
            ->with('success', 'TractorBooking created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $tractorBooking = TractorBooking::findorFail($id);

        return view('tractor-booking.show', compact('tractorBooking'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        abort(403, 'You are not allowed to perform this action!!');

        $tractorBooking = TractorBooking::findorFail($id);

        return view('tractor-booking.edit', compact('tractorBooking'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  TractorBooking $tractorBooking
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TractorBooking $tractorBooking)
    {
        request()->validate(TractorBooking::$rules);

        $tractorBooking->update($request->all());

        return redirect()->route('tractor-bookings.index')
            ->with('success', 'TractorBooking updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $tractorBooking = TractorBooking::findorFail($id)->delete();

        return redirect()->route('tractor-bookings.index')
            ->with('success', 'TractorBooking deleted successfully');
    }

    public function changeStatus(Request $request)
    {
        try {
            $booking = TractorBooking::findorFail($request->id);
            if (!$booking) {
                return response()->json(['error' => 'TractorBooking not found'], 404);
            }
            if ($booking && $request->state_id == TractorBooking::STATE_ACCEPTED) {
                $booking->state_id = $request->state_id;
                if ($booking->save()) {
                    $allBookings = TractorBooking::where('id', '!=', $booking->id)->where(['tractor_id' => $booking->tractor_id, 'date' => $booking->date])->update(['state_id' => TractorBooking::STATE_REJECTED]);
                }
            } elseif ($booking && $request->state_id == TractorBooking::STATE_REJECTED) {
                $rules = ['reason' => 'required'];
                $validator = Validator::make($request->all(), $rules);
                if ($validator->fails()) {
                    return response()->json(['errors' => $validator->errors()], 422);
                }
                $booking->state_id = $request->state_id;
                $booking->reason = $request->reason;
                $booking->save();
                return response()->json(['success' => 'States changes successfully.']);
            }
            return redirect()->back();
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occured ' . $e->getMessage()], 404);
        }
    }
}
