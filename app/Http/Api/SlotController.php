<?php

namespace App\Http\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Slot;
use App\Models\TractorBooking;
use App\Models\TractorGroup;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SlotController extends Controller
{
    public function index(Request $request)
    {
        $rules = [
            'tractor_id' => 'required',
            'records_per_page' => 'required',
            'page_no' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $currentUserGroup = $farmerGroup = null;
            $user_id = Auth::user()->id;
            $groups = TractorGroup::get();
            foreach ($groups as $group) {
                if (in_array($user_id, json_decode($group->farmer_ids, true))) {
                    $currentUserGroup = $group;
                }
            }
            $farmerGroup = $currentUserGroup;

            if (!empty($farmerGroup->tractor_ids)) {
                if (in_array($request->tractor_id, json_decode($farmerGroup->tractor_ids, true))) {
                    $slots = Slot::where('tractor_id', $request->tractor_id)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                    $totalCount = $slots->total();
                    if ($totalCount == 0) {
                        return returnSuccessResponse('No slots available.');
                    }
                    $total_pages = ceil($totalCount / $request->records_per_page);

                    $returnArrData = [
                        'slots' => $slots->all(),
                        'page_no' => $request->page_no,
                        'total_entries' => $totalCount,
                        'total_pages' => $total_pages
                    ];
                    return returnSuccessResponse('Get all slots list for selected tractor successfully. ', $returnArrData);
                } else {
                    return returnNotFoundResponse('This tractor not found in your group.');
                }
            } else {
                return returnSuccessResponse('No record found!!');
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function slotBooking(Request $request)
    {
        $rules = [
            'tractor_id' => 'required',
            'device_id' => 'required',
            'date' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $currentUserGroup = $farmerGroup = null;
            $user_id = Auth::user()->id;
            $groups = TractorGroup::get();
            foreach ($groups as $group) {
                if (in_array($user_id, json_decode($group->farmer_ids, true))) {
                    $currentUserGroup = $group;
                }
            }
            $farmerGroup = $currentUserGroup;

            if (!empty($farmerGroup)) {
                $checkTractor = TractorBooking::where(['tractor_id' => $request->tractor_id, 'state_id' => TractorBooking::STATE_ACCEPTED, 'date' => $request->date])->get();
                if (count($checkTractor) == 0) {
                    $checkDevice = TractorBooking::where(['device_id' => $request->device_id, 'state_id' => TractorBooking::STATE_ACCEPTED, 'date' => $request->date])->get();
                    if (count($checkDevice) == 0) {
                        $tractorBooking = new TractorBooking();
                        $tractorBooking->tractor_id = $request->tractor_id;
                        $tractorBooking->device_id = $request->device_id;
                        $tractorBooking->date = $request->date;
                        $tractorBooking->purpose = $request->purpose;
                        $tractorBooking->state_id = TractorBooking::STATE_ACTIVE;
                        if (!$tractorBooking->save()) {
                            return returnErrorResponse('Unable to create booking. Please try again later');
                        }
                        return returnSuccessResponse('Booking create successfully', $tractorBooking);
                    } else {
                        return returnSuccessResponse('Device booked for this date.');
                    }
                } else {
                    return returnSuccessResponse('Tractor booked for this date.');
                }
            } else {
                return returnNotFoundResponse('No group found for user.');
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function updateSlotBooking(Request $request)
    {
        $rules = [
            'id' => 'required',
            'tractor_id' => 'required',
            'device_id' => 'required',
            'date' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $currentUserGroup = $farmerGroup = null;
            $user_id = Auth::user()->id;
            $groups = TractorGroup::get();
            foreach ($groups as $group) {
                if (in_array($user_id, json_decode($group->farmer_ids, true))) {
                    $currentUserGroup = $group;
                }
            }
            $farmerGroup = $currentUserGroup;

            if (!empty($farmerGroup)) {
                $tractorBooking = TractorBooking::find($request->id);
                if (!empty($tractorBooking)) {
                    $checkTractor = TractorBooking::where('id', '!=', $tractorBooking->id)->where(['tractor_id' => $request->tractor_id, 'state_id' => TractorBooking::STATE_ACCEPTED, 'date' => $request->date])->get();
                    if (count($checkTractor) == 0) {
                        $checkDevice = TractorBooking::where('id', '!=', $tractorBooking->id)->where(['device_id' => $request->device_id, 'state_id' => TractorBooking::STATE_ACCEPTED, 'date' => $request->date])->get();
                        if (count($checkDevice) == 0) {
                            if ($tractorBooking->state_id == TractorBooking::STATE_ACTIVE) {
                                $tractorBooking->tractor_id = $request->tractor_id;
                                $tractorBooking->device_id = $request->device_id;
                                $tractorBooking->date = $request->date;
                                $tractorBooking->purpose = $request->purpose;
                                $tractorBooking->state_id = TractorBooking::STATE_ACTIVE;
                                if (!$tractorBooking->save()) {
                                    return returnErrorResponse('Unable to update booking. Please try again later');
                                }
                            } else {
                                return returnErrorResponse('unable to update booking, because it is not in active state.', $tractorBooking);
                            }
                            return returnSuccessResponse('Booking updated successfully', $tractorBooking);
                        } else {
                            return returnSuccessResponse('Device booked for this date.');
                        }
                    } else {
                        return returnSuccessResponse('Tractor booked for this date.');
                    }
                } else {
                    return returnSuccessResponse('No booking found.');
                }
            } else {
                return returnNotFoundResponse('No group found for user.');
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function deleteBooking(Request $request)
    {
        $rules = [
            'id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $currentUserGroup = $farmerGroup = null;
            $user_id = Auth::user()->id;
            $groups = TractorGroup::get();
            foreach ($groups as $group) {
                if (in_array($user_id, json_decode($group->farmer_ids, true))) {
                    $currentUserGroup = $group;
                }
            }
            $farmerGroup = $currentUserGroup;

            if (!empty($farmerGroup)) {
                $tractorBooking = TractorBooking::find($request->id);
                $slot = Slot::find($tractorBooking->slot_id);
                if ($slot->state_id == Slot::STATE_ACTIVE) {
                    if ($tractorBooking->state_id == TractorBooking::STATE_ACTIVE) {
                        if (!$tractorBooking->delete()) {
                            return returnErrorResponse('Unable to update booking. Please try again later');
                        }
                    } else {
                        return returnErrorResponse('unable to delete booking, because it is not in active state.', $tractorBooking);
                    }
                    return returnSuccessResponse('Booking deleted successfully');
                } else {
                    return returnSuccessResponse('Slot is in booked state.');
                }
            } else {
                return returnNotFoundResponse('No group found for user.');
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function bookingDetail(Request $request)
    {
        $rules = [
            'id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $currentUserGroup = $farmerGroup = null;
            $user_id = Auth::user()->id;
            $groups = TractorGroup::get();
            foreach ($groups as $group) {
                if (in_array($user_id, json_decode($group->farmer_ids, true))) {
                    $currentUserGroup = $group;
                }
            }
            $farmerGroup = $currentUserGroup;

            if (!empty($farmerGroup)) {
                $tractorBooking = TractorBooking::with('tractor', 'tractor.images', 'device')->find($request->id);
                if ($tractorBooking->created_by != $user_id) {
                    return returnNotAllowedResponse('You are not allowed to perform this action!!');
                }
                return returnSuccessResponse('Get tractor booking detail successfully', $tractorBooking);
            } else {
                return returnNotFoundResponse('No group found for user.');
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function bookingList(Request $request)
    {
        $rules = [
            'year' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $user_id = Auth::user()->id;
            $bookings = TractorBooking::whereYear('date', $request->year)->where('created_by', $user_id)->latest('id');
            if ($request->month) {
                $bookings = $bookings->whereMonth('date', $request->month)->get();
            } else {
                $bookings = $bookings->get();
            }
            if (count($bookings) == 0) {
                return returnSuccessResponse('No bookings found.');
            }
            $returnArrData = [
                'bookings' => $bookings->all()
            ];
            return returnSuccessResponse('Get all booking list successfully. ', $returnArrData);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }
}
