<?php

namespace App\Http\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\Tractor;
use App\Models\TractorBooking;
use App\Models\TractorGroup;
use App\Models\User;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $rules = [
            'records_per_page' => 'required',
            'page_no' => 'required',
        ];
        if (in_array(Auth::user()->role_id, [User::ROLE_ADMIN, User::ROLE_SUB_ADMIN, User::ROLE_GOVERNMENT])) {
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $errorMessages = $validator->errors()->all();
                throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
            }
        }
        try {
            if (in_array(Auth::user()->role_id, [User::ROLE_ADMIN, User::ROLE_SUB_ADMIN, User::ROLE_GOVERNMENT])) {
                $tractorGroups = TractorGroup::select('id', 'name', 'created_at', 'updated_at', 'created_by');
                if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                    $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                    $tractorGroups = $tractorGroups->whereIn('id', $assignedGroups);
                }
                $tractorGroups = $tractorGroups->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                $totalCount = $tractorGroups->total();
                $total_pages = ceil($totalCount / $request->records_per_page);

                $returnArrData = [
                    'groups' => $tractorGroups->all(),
                    'page_no' => $request->page_no,
                    'total_entries' => $totalCount,
                    'total_pages' => $total_pages
                ];

                return returnSuccessResponse('Get all tractor group list successfully', $returnArrData);
            } elseif (Auth::user()->role_id == User::ROLE_FARMER) {
                $currentUserGroup = $farmerGroup = null;
                $user_id = Auth::user()->id;
                $groups = TractorGroup::select('id', 'name', 'created_at', 'updated_at', 'created_by')->get();
                foreach ($groups as $group) {
                    $farmerIds = $group->farmer_ids ? json_decode($group->farmer_ids, true) : [];
                    if (in_array($user_id, $farmerIds)) {
                        $currentUserGroup = $group;
                    }
                }
                $farmerGroup = $currentUserGroup;

                if (empty($farmerGroup)) {
                    return returnSuccessResponse('User is not in a group');
                }
                $returnArrData = [
                    'groups' => [$farmerGroup],
                    'page_no' => 1,
                    'total_entries' => 1,
                    'total_pages' => 1
                ];

                return returnSuccessResponse('Get farmer group successfully', $returnArrData);
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'tractor_ids' => 'required',
            'farmer_ids' => 'required',
            'device_ids' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {

            $tractorGroup = new TractorGroup();
            $tractorGroup->name = $request->name;
            $tractorGroup->tractor_ids = is_array($request->tractor_ids) ? json_encode($request->tractor_ids) : $request->tractor_ids;
            $tractorGroup->device_ids = is_array($request->device_ids) ? json_encode($request->device_ids) : $request->device_ids;
            $tractorGroup->farmer_ids = is_array($request->farmer_ids) ? json_encode($request->farmer_ids) : $request->farmer_ids;
            $tractorGroup->state_id = TractorGroup::STATE_ACTIVE;

            if (!$tractorGroup->save()) {
                return returnErrorResponse('Unable to create new group. Please try again later');
            }
            return returnSuccessResponse('Group created successfully', $tractorGroup->createJsonResponse());
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
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
            if (in_array(Auth::user()->role_id, [User::ROLE_ADMIN, User::ROLE_SUB_ADMIN, User::ROLE_GOVERNMENT])) {
                $tractorGroup = TractorGroup::findorFail($request->id);
                $tractors = $farmers = $devices = [];
                if (!empty($tractorGroup->tractor_ids)) {
                    $tractors = Tractor::with('images')->whereIn('id', json_decode($tractorGroup->tractor_ids, true))->get();
                }
                if (!empty($tractorGroup->farmer_ids)) {
                    $farmers = User::whereIn('id', json_decode($tractorGroup->farmer_ids, true))->get();
                }
                if (!empty($tractorGroup->device_ids)) {
                    $devices = Device::whereIn('id', json_decode($tractorGroup->device_ids, true))->get();
                }
                $tractorGroup->farmers = $farmers;
                $tractorGroup->tractors = $tractors;
                $tractorGroup->devices = $devices;
                $tractorGroup->sub_admin =  $tractorGroup->subAdmin?->user;
                unset($tractorGroup->tractor_ids);
                unset($tractorGroup->farmer_ids);
                unset($tractorGroup->device_ids);
                return returnSuccessResponse('Group detail', $tractorGroup);
            } elseif (Auth::user()->role_id == User::ROLE_FARMER) {
                $user_id = Auth::user()->id;
                $groupData = TractorGroup::findorFail($request->id);
                if (in_array($user_id, $groupData->farmer_ids ? json_decode($groupData->farmer_ids) : [])) {
                    $tractors = $farmers = $devices = [];
                    if (!empty($groupData->tractor_ids)) {
                        $tractors = Tractor::with('images')->whereIn('id', json_decode($groupData->tractor_ids, true))->get();
                    }
                    if (!empty($groupData->farmer_ids)) {
                        $farmers = User::whereIn('id', json_decode($groupData->farmer_ids, true))->get();
                    }
                    if (!empty($groupData->device_ids)) {
                        $devices = Device::whereIn('id', json_decode($groupData->device_ids, true))->get();
                    }
                    $groupData->farmers = $farmers;
                    $groupData->tractors = $tractors;
                    $groupData->devices = $devices;
                    $groupData->sub_admin =  $groupData->subAdmin?->user;
                    return returnSuccessResponse('Get tractor booking detail successfully', $groupData);
                } else {
                    return returnNotAllowedResponse('No group found for user.');
                }
            }
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $rules = [
            'group_id' => 'required',
            'name' => 'required',
            'tractor_ids' => 'required',
            'farmer_ids' => 'required',
            'device_ids' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $tractorGroup = TractorGroup::findorFail($request->group_id);
            if (empty($tractorGroup)) {
                return returnNotFoundResponse('No group found.');
            }
            $tractorGroup->name = $request->name;
            $tractorGroup->tractor_ids = is_array($request->tractor_ids) ? json_encode($request->tractor_ids) : $request->tractor_ids;
            $tractorGroup->device_ids = is_array($request->device_ids) ? json_encode($request->device_ids) : $request->device_ids;
            $tractorGroup->farmer_ids = is_array($request->farmer_ids) ? json_encode($request->farmer_ids) : $request->farmer_ids;
            if (!$tractorGroup->save()) {
                return returnErrorResponse('Unable to update group. Please try again later');
            }
            return returnSuccessResponse('Group updated successfully', $tractorGroup->createJsonResponse());
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
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
            $tractorGroup = TractorGroup::findorFail($request->id);
            if (!empty($tractorGroup)) {
                $tractorGroup->delete();
                return response()->json(['status' => true, 'message' => 'Group deleted successfully', 'data' => null]);
            } else {
                return returnNotFoundResponse('No group found.');
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }



    public function groupDetail(Request $request)
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
            $user_id = Auth::user()->id;
            $groupData = TractorGroup::findorFail($request->id);
            if (in_array($user_id, $groupData->farmer_ids ? json_decode($groupData->farmer_ids) : [])) {
                $tractors = $farmers = $devices = [];
                if (!empty($groupData->tractor_ids)) {
                    $tractors = Tractor::with('images')->where('id', json_decode($groupData->tractor_ids, true))->get();
                }
                if (!empty($groupData->farmer_ids)) {
                    $farmers = User::whereIn('id', json_decode($groupData->farmer_ids, true))->get();
                }
                if (!empty($groupData->device_ids)) {
                    $devices = Device::whereIn('id', json_decode($groupData->device_ids, true))->get();
                }
                $groupData->farmers = $farmers;
                $groupData->tractors = $tractors;
                $groupData->devices = $devices;
                return returnSuccessResponse('Get tractor booking detail successfully', $groupData);
            } else {
                return returnNotAllowedResponse('No group found for user.');
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function assignIndex(Request $request)
    {
        $rules = [
            'records_per_page' => 'required',
            'page_no' => 'required',
            'user_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        try {
            $user = User::findorFail($request->user_id);
            if (!$user) {
                return returnNotFoundResponse('Invalid user id.');
            }
            if ($user && $user->role_id != User::ROLE_SUB_ADMIN) {
                return returnNotFoundResponse('No sub admin found with given user id.');
            }
            $assignedIds = AssignedGroup::where('user_id', '!=', $request->user_id)->pluck('group_id')->toArray();
            $assignedGroups = AssignedGroup::where('user_id', $request->user_id)->pluck('group_id')->toArray();

            $tractorGroups = TractorGroup::whereNotIn('id', $assignedIds)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
            $totalCount = $tractorGroups->total();
            $total_pages = ceil($totalCount / $request->records_per_page);

            foreach ($tractorGroups as $groupValue) {
                $assigned = false;
                if (in_array($groupValue->id, $assignedGroups)) {
                    $assigned = true;
                }
                $groupValue->assign = $assigned;
                $tractors = $farmers = $devices = [];
                if (!empty($groupValue->tractor_ids)) {
                    $groupValue->tractor_ids = json_decode($groupValue->tractor_ids, true);
                    $tractors = Tractor::with('images')->whereIn('id', ($groupValue->tractor_ids))->get();
                }
                if (!empty($groupValue->farmer_ids)) {
                    $groupValue->farmer_ids = json_decode($groupValue->farmer_ids, true);
                    $farmers = User::whereIn('id', ($groupValue->farmer_ids))->get();
                }
                if (!empty($groupValue->device_ids)) {
                    $groupValue->device_ids = json_decode($groupValue->device_ids, true);
                    $devices = Device::whereIn('id', ($groupValue->device_ids))->get();
                }
                $groupValue->tractors = $tractors;
                $groupValue->farmers = $farmers;
                $groupValue->devices = $devices;
            }
            $returnArrData = [
                'groups' => $tractorGroups->all(),
                'page_no' => $request->page_no,
                'total_entries' => $totalCount,
                'total_pages' => $total_pages
            ];
            return returnSuccessResponse('Get all group list successfully', $returnArrData);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function assignGroup(Request $request)
    {
        $rules = [
            'user_id' => 'required',
            'id' => 'required',
            'state' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $group = TractorGroup::findorFail($request->id);
            if ($request->state == '1') {
                $assignedGroup = AssignedGroup::create([
                    'user_id' => $request->user_id,
                    'group_id' => $request->id,
                ]);
                $status = 'assigned';
            } else {
                $assignedGroup = AssignedGroup::where(['group_id' => $request->id, 'user_id' => $request->user_id])->delete();
                $status = 'un assigned';
            }
            $user = User::findOrFail($request->user_id);
            return returnSuccessResponse('Group ' . $status . ' successfully ', $user);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }
}
