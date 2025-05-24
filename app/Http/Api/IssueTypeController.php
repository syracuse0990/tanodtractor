<?php

namespace App\Http\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\IssueType;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class IssueTypeController extends Controller
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
                $issueType = IssueType::with('createdBy')->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                $totalCount = $issueType->total();
                $total_pages = ceil($totalCount / $request->records_per_page);

                $listArr = [];
                foreach ($issueType as $value) {
                    $value->pending_state = $value->state_id == IssueType::STATE_ACTIVE ?  IssueType::STATE_INACTIVE : ($value->state_id == IssueType::STATE_INACTIVE ? IssueType::STATE_ACTIVE : null);
                    array_push($listArr, $value);
                }

                $returnArrData = [
                    'issueType' => $listArr,
                    'page_no' => $request->page_no,
                    'total_entries' => $totalCount,
                    'total_pages' => $total_pages
                ];
            } elseif (Auth::user()->role_id == User::ROLE_FARMER) {
                $issueType = IssueType::with('createdBy')->where('state_id', IssueType::STATE_ACTIVE)->latest('id')->get();

                $returnArrData = [
                    'issueType' => $issueType,
                ];
            }
            return returnSuccessResponse('Get issue type list successfully', $returnArrData);
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
            'title' => 'required',
            'state_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $issueType = IssueType::create($request->all());
            return returnSuccessResponse('Issue Type added successfully', $issueType->createJsonResponse());
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
            'id' => 'required',
            'title' => 'required',
            'state_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $issueType = IssueType::findorFail($request->id);
            if (!$issueType) {
                return returnNotFoundResponse('No Issue Type found');
            }
            $issueType->update($request->all());
            return returnSuccessResponse('Issue Type updated successfully', $issueType->createJsonResponse());
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
            $issueType = IssueType::with('createdBy')->where('id', $request->id)->first();
            $issueType->pending_state = $issueType->state_id == IssueType::STATE_ACTIVE ?  IssueType::STATE_INACTIVE : ($issueType->state_id == IssueType::STATE_INACTIVE ? IssueType::STATE_ACTIVE : null);

            if (empty($issueType)) {
                return returnNotFoundResponse('No Issue Type found');
            }
            return returnSuccessResponse('Issue Type detail', $issueType);
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
            $issueType = IssueType::findorFail($request->id);
            if (!$issueType) {
                return returnNotFoundResponse('No Issue Type found');
            }
            $issueType->delete();
            return response()->json(['status' => true, 'message' => 'Issue Type deleted successfully', 'data' => null]);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function changeStatus(Request $request)
    {
        $rules = [
            'id' => 'required',
            'state_id' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $issueType = IssueType::findorFail($request->id);
            if (!$issueType) {
                return returnNotFoundResponse('No Issue Type found');
            }
            if (!in_array($request->state_id, [IssueType::STATE_ACTIVE, IssueType::STATE_INACTIVE, IssueType::STATE_DELETED])) {
                return returnErrorResponse('Please select a valid status.');
            }
            $issueType->state_id = $request->state_id;
            $issueType->save();
            return returnSuccessResponse('Issue Type status updated successfully', $issueType->createJsonResponse());
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }
}
