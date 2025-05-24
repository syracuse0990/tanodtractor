<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ImportAllData;
use App\Models\AssignedGroup;
use App\Models\TractorBooking;
use App\Models\TractorGroup;
use App\Models\User;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
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
            $message = 'farmers';
            if ($request->allData) {
                $users = User::where('role_id', User::ROLE_FARMER)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                $totalCount = $users->total();
                $total_pages = ceil($totalCount / $request->records_per_page);

                $returnArrData = [
                    'farmers' => $users->all(),
                    'page_no' => $request->page_no,
                    'total_entries' => $totalCount,
                    'total_pages' => $total_pages
                ];
            } elseif ($request->subAdmin) {
                $message = 'sub admins';
                $users = User::where('role_id', User::ROLE_SUB_ADMIN)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                $totalCount = $users->total();
                $total_pages = ceil($totalCount / $request->records_per_page);

                $returnArrData = [
                    'farmers' => $users->all(),
                    'page_no' => $request->page_no,
                    'total_entries' => $totalCount,
                    'total_pages' => $total_pages
                ];
            } elseif (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                $userIds = $groups->pluck('farmer_ids')->flatten()->toArray();
                $userIds = multiDimToSingleDim($userIds);
                $users = User::whereIn('id', $userIds)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                $totalCount = $users->total();
                $total_pages = ceil($totalCount / $request->records_per_page);
                $returnArrData = [
                    'farmers' => $users->all(),
                    'page_no' => $request->page_no,
                    'total_entries' => $totalCount,
                    'total_pages' => $total_pages
                ];
            } else {
                if ($request->group_id) {
                    $farmerData = TractorGroup::where('id', '!=', $request->group_id)->pluck('farmer_ids')->toArray();
                    $farmer_ids = multiDimToSingleDim($farmerData);
                    $users = User::where('role_id', User::ROLE_FARMER)->whereNotIn('id', $farmer_ids)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                    $totalCount = $users->total();
                    $total_pages = ceil($totalCount / $request->records_per_page);
                    $returnArrData = [
                        'farmers' => $users->all(),
                        'page_no' => $request->page_no,
                        'total_entries' => $totalCount,
                        'total_pages' => $total_pages
                    ];
                } else {
                    $farmerData = TractorGroup::pluck('farmer_ids')->toArray();
                    $farmer_ids = multiDimToSingleDim($farmerData);
                    $users = User::where('role_id', User::ROLE_FARMER)->whereNotIn('id', $farmer_ids)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                    $totalCount = $users->total();
                    $total_pages = ceil($totalCount / $request->records_per_page);
                    $returnArrData = [
                        'farmers' => $users->all(),
                        'page_no' => $request->page_no,
                        'total_entries' => $totalCount,
                        'total_pages' => $total_pages
                    ];
                }
            }

            return returnSuccessResponse('Get all ' . $message . ' list successfully', $returnArrData);
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
            'user_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $user = User::findorFail($request->user_id);
            return returnSuccessResponse('User detail', $user);
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
            'user_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $user = User::findorFail($request->user_id);
            if (empty($user)) {
                return returnNotFoundResponse('No user found.');
            }
            $user->update($request->all());
            if ($request->file('profile_photo_path')) {
                $path = $request->file('profile_photo_path')->store('image', 'public');
                $user->profile_photo_path = $path;
            }
            if (!$user->save()) {
                return returnErrorResponse('Unable to update user data. Please try again later');
            }
            return returnSuccessResponse('User data updated successfully', $user);
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
            'user_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $user = User::findorFail($request->user_id);
            if (!empty($user)) {
                $bookings = TractorBooking::where('created_by', $user->id)->update(['state_id' => TractorBooking::STATE_DELETED]);
                $user->delete();
                return response()->json(['status' => true, 'message' => 'User deleted successfully', 'data' => null]);
            } else {
                return response()->json(['status' => false, 'message' => 'User not found', 'data' => null]);
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function subAdmin(Request $request)
    {
        request()->validate([
            'name' => 'required',
            'email' => 'required|email:rfc,dns|unique:users,email',
            'phone' => 'required|numeric|unique:users,phone',
            'gender' => 'required'
        ]);

        try {
            $userData = $request->all();
            $userData['role_id'] = User::ROLE_SUB_ADMIN;
            $userData['password'] = Hash::make('Password@123');
            $userData['phone_country'] = $request->phone_code;
            $userData['country_code'] = $request->iso_code;
            $userData['state_id'] = User::STATE_ACTIVE;
            $userData['email_verified_at'] = now();
            $userData['phone_verified_at'] = now();
            $user = User::create($userData);
            return response()->json(['status' => true, 'message' => 'User Created successfully', 'data' => $user]);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function subAdminUpdate(Request $request)
    {
        request()->validate([
            'name' => 'required',
            // 'email' => 'required|email:rfc,dns|unique:users,email,' . $request->id,
            'phone' => 'required|numeric|unique:users,phone,' . $request->id,
            'gender' => 'required'
        ]);

        try {
            $user = User::findorFail($request->id);
            $userData = $request->all();
            $userData['phone_country'] = $request->phone_code;
            $userData['country_code'] = $request->iso_code;
            $user->update($userData);
            return response()->json(['status' => true, 'message' => 'User Updated successfully', 'data' => $user]);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function assignIndex(Request $request)
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
            $assignedIds = AssignedGroup::pluck('user_id')->toArray();
            $users = User::where('role_id', User::ROLE_SUB_ADMIN)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
            $totalCount = $users->total();
            $total_pages = ceil($totalCount / $request->records_per_page);

            $returnArrData = [
                'users' => $users->all(),
                'page_no' => $request->page_no,
                'total_entries' => $totalCount,
                'total_pages' => $total_pages
            ];
            return returnSuccessResponse('Get all users list successfully', $returnArrData);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }


    public function assignUser(Request $request)
    {
        $rules = [
            'id' => 'required',
            'user_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            if ($request->state == 1) {
                $assignedGroup = AssignedGroup::create([
                    'group_id' => $request->id,
                    'user_id' => $request->user_id,
                ]);
                $status = 'assigned';
            } else {
                $assignedGroup = AssignedGroup::where(['group_id' => $request->id, 'user_id' => $request->user_id])->delete();
                $status = 'unassigned';
            }
            $user = User::findOrFail($request->user_id);
            return returnSuccessResponse('Sub admin ' . $status . ' successfully ', $user);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function import(Request $request)
    {
        $rules = [
            'fileInput' => 'required|mimes:csv,txt,xlsx,xls'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            $file = $request->file('fileInput');
            $extension = $file->getClientOriginalExtension();
            $fileName = uniqid('file_', true) . '.' . $extension;
            $filePath = $file->storeAs('import', $fileName, 'public');

            // ImportData::dispatch($filePath, Auth::id());
            ImportAllData::dispatch($filePath, Auth::id());
            return  response()->json(['status' => true, 'message' => 'Import request has been added to the queue. Please check back shortly']);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function importFormat()
    {
        try {
            $filename = 'data_import_format.csv';
            $filePath = asset('assets/format/' . $filename); // Use asset() to generate a public URL

            if (!file_exists(public_path('assets/format/' . $filename))) {
                return response()->json([
                    'status' => false,
                    'message' => 'File not found',
                    'data' => []
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Get import format successfully',
                'data' => ['path' => $filePath]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
}
