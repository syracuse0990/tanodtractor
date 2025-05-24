<?php

namespace App\Http\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\Jimi;
use App\Models\Maintenance;
use App\Models\Notification;
use App\Models\Slot;
use App\Models\Tractor;
use App\Models\TractorBooking;
use App\Models\TractorGroup;
use App\Models\User;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index(Request $request)
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
            $user = Auth::user();
            if (in_array($user->role_id, [User::ROLE_ADMIN, User::ROLE_SUB_ADMIN, User::ROLE_GOVERNMENT])) {
                $bookings = TractorBooking::whereNotIn('state_id', [TractorBooking::STATE_DELETED])->whereYear('date', $request->year);
                if ($request->month) {
                    $bookings = $bookings->whereMonth('date', $request->month);
                }
                if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                    $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                    $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                    $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
                    $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
                    $tractorIds = multiDimToSingleDim($tractorIds);
                    $deviceIds = multiDimToSingleDim($deviceIds);
                    $bookings = $bookings->whereIn('tractor_id', $tractorIds)->orWhereIn('device_id', $deviceIds);
                }
                $bookings = $bookings->orderBy('state_id', 'ASC')->get();
                if (count($bookings) == 0) {
                    return returnSuccessResponse('No bookings found.');
                }
                $returnArrData = [
                    'bookings' => $bookings->all()
                ];
            } elseif ($user->role_id == User::ROLE_FARMER) {

                $bookings = TractorBooking::whereNotIn('state_id', [TractorBooking::STATE_DELETED])->whereYear('date', $request->year)->where('created_by', $user->id)->orderBy('state_id', 'ASC');
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
            }
            return returnSuccessResponse('Get all booking list successfully. ', $returnArrData);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function tractorBooking(Request $request)
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
                $farmerIds = $group->farmer_ids ? json_decode($group->farmer_ids, true) : [];
                if (in_array($user_id, $farmerIds)) {
                    $currentUserGroup = $group;
                }
            }
            $farmerGroup = $currentUserGroup;

            if (!empty($farmerGroup)) {
                $maintenance = Maintenance::where('tractor_ids', $request->tractor_id)->whereIn('state_id', [Maintenance::STATE_INPROGRESS])->latest('id')->get();
                if (count($maintenance)) {
                    return notAuthorizedResponse('Unable to create booking. Tractor is in maintenance.', [], 'error');
                }
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
                            return notAuthorizedResponse('Unable to create booking. Please try again later', [], 'error');
                        }
                        return returnSuccessResponse('Booking create successfully', $tractorBooking);
                    } else {
                        return notAuthorizedResponse('Device booked for this date.', [], 'error');
                    }
                } else {
                    return notAuthorizedResponse('Tractor booked for this date.', [], 'error');
                }
            } else {
                return returnNotFoundResponse('No group found for user.');
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function updateTractorBooking(Request $request)
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
                $farmerIds = $group->farmer_ids ? json_decode($group->farmer_ids, true) : [];
                if (in_array($user_id, $farmerIds)) {
                    $currentUserGroup = $group;
                }
            }
            $farmerGroup = $currentUserGroup;

            if (!empty($farmerGroup)) {
                $tractorBooking = TractorBooking::findorFail($request->id);
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
                $farmerIds = $group->farmer_ids ? json_decode($group->farmer_ids, true) : [];
                if (in_array($user_id, $farmerIds)) {
                    $currentUserGroup = $group;
                }
            }
            $farmerGroup = $currentUserGroup;

            if (!empty($farmerGroup)) {
                $tractorBooking = TractorBooking::findorFail($request->id);
                $slot = Slot::findorFail($tractorBooking->slot_id);
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
        try {
            $currentUserGroup = $farmerGroup = null;
            $user_id = Auth::user()->id;
            $groups = TractorGroup::get();
            foreach ($groups as $group) {
                $farmerIds = $group->farmer_ids ? json_decode($group->farmer_ids, true) : [];
                if (in_array($user_id, $farmerIds)) {
                    $currentUserGroup = $group;
                }
            }
            $farmerGroup = $currentUserGroup;

            if (!empty($farmerGroup)) {
                $tractorBooking = TractorBooking::with('tractor', 'tractor.images', 'device')->findorFail($request->id);
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

    public function show(Request $request)
    {
        $rules = [
            'id' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $user = Auth::user();
            if ($request->notification_id) {
                $notification = Notification::findorFail($request->notification_id);
                $notification->is_read = Notification::IS_READ;
                $notification->save();
            }
            if (in_array($user->role_id, [User::ROLE_ADMIN, User::ROLE_SUB_ADMIN, User::ROLE_GOVERNMENT])) {
                $tractorBooking = TractorBooking::with('tractor', 'tractor.images', 'device')->findorFail($request->id);
                if (empty($tractorBooking)) {
                    return returnNotFoundResponse('No booking found');
                }
                return returnSuccessResponse('Get tractor booking detail successfully', $tractorBooking);
            } elseif ($user->role_id == User::ROLE_FARMER) {

                $currentUserGroup = $farmerGroup = null;
                $user_id = $user->id;
                $groups = TractorGroup::get();
                foreach ($groups as $group) {
                    $farmerIds = $group->farmer_ids ? json_decode($group->farmer_ids, true) : [];
                    if (in_array($user_id, $farmerIds)) {
                        $currentUserGroup = $group;
                    }
                }
                $farmerGroup = $currentUserGroup;
                if (!empty($farmerGroup)) {
                    $tractorBooking = TractorBooking::with('tractor', 'tractor.images', 'device')->findorFail($request->id);
                    if (empty($tractorBooking)) {
                        return returnNotFoundResponse('No booking found');
                    }
                    if ($tractorBooking->created_by != $user_id) {
                        return returnNotAllowedResponse('You are not allowed to perform this action!!');
                    }
                    return returnSuccessResponse('Get tractor booking detail successfully', $tractorBooking);
                } else {
                    return returnNotFoundResponse('No group found for user.');
                }
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function allBookings(Request $request)
    {
        $rules = [
            'records_per_page' => 'required',
            'page_no' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $bookings = TractorBooking::with('tractor', 'device', 'createdBy')->whereNotIn('state_id', [TractorBooking::STATE_DELETED]);
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
                $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
                $tractorIds = multiDimToSingleDim($tractorIds);
                $deviceIds = multiDimToSingleDim($deviceIds);
            }
            if ($request->tractor_id) {
                $bookings = $bookings->where('tractor_id', $request->tractor_id);
            } else {
                if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                    $bookings = $bookings->whereIn('tractor_id', $tractorIds)->orWhereIn('device_id', $deviceIds);
                }
            }
            $bookings = $bookings->orderBy('state_id', 'ASC')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
            $totalCount = $bookings->total();
            $total_pages = ceil($totalCount / $request->records_per_page);

            $returnArrData = [
                'bookings' => $bookings->all(),
                'page_no' => $request->page_no,
                'total_entries' => $totalCount,
                'total_pages' => $total_pages
            ];
            return returnSuccessResponse('Get all booking list successfully', $returnArrData);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function acceptReject(Request $request)
    {

        $rules = [
            'id' => 'required',
            'status' => 'required',
            'reason' => [Rule::requiredIf($request->status == TractorBooking::STATE_REJECTED)]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $booking = TractorBooking::findorFail($request->id);

            if (empty($booking)) {
                return returnNotFoundResponse('Booking not found');
            }
            if ($booking->state_id != TractorBooking::STATE_ACTIVE) {
                return returnSuccessResponse('Unable to update, booking is not in active state.');
            }
            if ($request->status == TractorBooking::STATE_ACCEPTED) {
                $booking->state_id = $request->status;
                if (!$booking->save()) {
                    return returnErrorResponse('unable to update booking status, please try again later.');
                }
                $allBookings = TractorBooking::where('id', '!=', $booking->id)->where(['tractor_id' => $booking->tractor_id, 'date' => $booking->date])->update(['state_id' => TractorBooking::STATE_REJECTED]);
                return returnSuccessResponse('Booking request accepted successfully', $booking->bookingJsonResponse());
            } elseif ($request->status == TractorBooking::STATE_REJECTED) {
                $booking->state_id = $request->status;
                $booking->reason = $request->reason;
                if (!$booking->save()) {
                    return returnErrorResponse('unable to update booking status, please try again later.');
                }
                return returnSuccessResponse('Booking request rejected successfully', $booking->bookingJsonResponse());
            }
            return returnNotFoundResponse('Invalid Status');
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    //Get current user all accepted booking list
    public function acceptedBookings()
    {
        try {
            $user = Auth::user();
            $date = date('Y-m-d');
            if (in_array($user->role_id, [User::ROLE_ADMIN, User::ROLE_SUB_ADMIN, User::ROLE_GOVERNMENT])) {
                $devices = Device::query();
                if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                    $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                    $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                    $deviceIds = [];
                    $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
                    $deviceIds = multiDimToSingleDim($deviceIds);
                    $devices = $devices->whereIn('id', $deviceIds);
                }
                $devices = $devices->pluck('imei_no')->toArray();
                $imeis = is_array($devices) ? $devices : explode(',', $devices);
                $apiData = (new Jimi())->getDeviceLocation($imeis);
                $bookingArr = [];
                foreach ($devices as $key => $device) {
                    $imei = !empty($apiData['result'][$key]) ?  $apiData['result'][$key]['imei'] : '';
                    if (!empty($imei)) {
                        $devcieImei = Device::where('imei_no', $imei)->first();
                        $bookingData = TractorBooking::with('tractor', 'device', 'createdBy')->where(['device_id' => $devcieImei->id, 'state_id' => TractorBooking::STATE_ACCEPTED, 'date' => $date])->orderBy('date', 'ASC')->first();
                        if (!$bookingData) {
                            $bookingData = new tractorBooking();
                        } else {
                            $groupsFarmerIds = TractorGroup::pluck('farmer_ids', 'id')->toArray();
                            $data = array_filter($groupsFarmerIds, function ($farmerIds) use ($bookingData) {
                                $farmersArray = json_decode($farmerIds, true);
                                return (in_array($bookingData->createdBy->id, $farmersArray));
                            });
                            $data = array_map(function ($key) {
                                return TractorGroup::where('id', $key)->select('name')->first();
                            }, array_keys($data));
                            $bookingData->group = isset($data[0]) && $data[0]['name'] ? $data[0]['name'] : '';
                        }
                        $bookingData->api_data = !empty($apiData['result'][$key]) ? [$apiData['result'][$key]] : [];
                        array_push($bookingArr, $bookingData);
                    }
                }
                $returnArrData = [
                    'bookings' => $bookingArr,
                ];
            } elseif ($user->role_id == User::ROLE_FARMER) {
                $booking = TractorBooking::with('tractor', 'device', 'createdBy')->where(['state_id' => TractorBooking::STATE_ACCEPTED, 'created_by' => $user->id, 'date' => $date])->orderBy('date', 'ASC')->first();
                if (!$booking) {
                    return returnSuccessResponse('No bookings found.');
                }
                $device = Device::where('id', $booking->device_id)->first();
                $imeis = is_array($device->imei_no) ? $device->imei_no : explode(',', $device->imei_no);
                $apiData = (new Jimi())->getDeviceLocation($imeis);
                $booking->api_data = $apiData['result'];

                $returnArrData = [
                    'bookings' => [$booking],
                ];
            }
            return returnSuccessResponse('Get all accepted booking list successfully. ', $returnArrData);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    //Get current user all accepted booking list for choosen device
    public function deviceBookingList(Request $request)
    {
        $rules = [
            'imei' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $user = Auth::user();
            if (in_array($user->role_id, [User::ROLE_ADMIN, User::ROLE_SUB_ADMIN, User::ROLE_GOVERNMENT])) {
                $device = Device::where('imei_no', $request->imei)->latest('id')->first();

                $bookings = TractorBooking::with('tractor', 'device', 'createdBy')->where(['device_id' => $device->id, 'state_id' => TractorBooking::STATE_ACCEPTED])->orderBy('date', 'ASC')->get();

                if (count($bookings) == 0) {
                    return returnSuccessResponse('No bookings found.');
                }
                $returnArrData = [
                    'bookings' => $bookings
                ];
            } elseif ($user->role_id == User::ROLE_FARMER) {
                $device = Device::where('imei_no', $request->imei)->latest('id')->first();

                $bookings = TractorBooking::with('tractor', 'device', 'createdBy')->where(['device_id' => $device->id, 'state_id' => TractorBooking::STATE_ACCEPTED, 'created_by' => $user->id])->orderBy('date', 'ASC')->get();

                if (count($bookings) == 0) {
                    return returnSuccessResponse('No bookings found.');
                }
                $returnArrData = [
                    'bookings' => $bookings
                ];
            }
            return returnSuccessResponse('Get all accepted booking list for choosen device successfully. ', $returnArrData);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }
}
