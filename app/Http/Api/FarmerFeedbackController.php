<?php

namespace App\Http\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\ExportFeedback;
use App\Models\Device;
use App\Models\FarmerFeedback;
use App\Models\Image;
use App\Models\Tractor;
use App\Models\TractorBooking;
use App\Models\TractorGroup;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FarmerFeedbackController extends Controller
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
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            if (in_array(Auth::user()->role_id, [User::ROLE_ADMIN, User::ROLE_SUB_ADMIN, User::ROLE_GOVERNMENT])) {
                $farmerFeedback = FarmerFeedback::with('images', 'createdBy', 'issueType', 'issueType.createdBy', 'tractor')->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                $totalCount = $farmerFeedback->total();
                $total_pages = ceil($totalCount / $request->records_per_page);
                $listArr = [];
                foreach ($farmerFeedback as $value) {
                    if ($value->state_id == FarmerFeedback::STATE_ACTIVE) {
                        $value->pending_states = [FarmerFeedback::STATE_CLOSED, FarmerFeedback::STATE_COMPLETED];
                    } elseif ($value->state_id == FarmerFeedback::STATE_CLOSED) {
                        $value->pending_states = [FarmerFeedback::STATE_ACTIVE, FarmerFeedback::STATE_COMPLETED];
                    } elseif ($value->state_id == FarmerFeedback::STATE_COMPLETED) {
                        $value->pending_states = [FarmerFeedback::STATE_ACTIVE, FarmerFeedback::STATE_CLOSED];
                    }
                    array_push($listArr, $value);
                }
                $returnArrData = [
                    'feedback' => $listArr,
                    'page_no' => $request->page_no,
                    'total_entries' => $totalCount,
                    'total_pages' => $total_pages
                ];
            } elseif (Auth::user()->role_id == User::ROLE_FARMER) {
                $farmerFeedback = FarmerFeedback::with('images', 'createdBy', 'issueType', 'issueType.createdBy')->where('created_by', Auth::user()->id)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                $totalCount = $farmerFeedback->total();
                $total_pages = ceil($totalCount / $request->records_per_page);

                $returnArrData = [
                    'feedback' => $farmerFeedback->all(),
                    'page_no' => $request->page_no,
                    'total_entries' => $totalCount,
                    'total_pages' => $total_pages
                ];
            }
            return returnSuccessResponse('Get feedbacks list successfully', $returnArrData);
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
            'email' => 'required|email',
            'issue_type_id' => 'required',
            'tractor_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $farmerFeedbackData = $request->all();
            $farmerFeedbackData['state_id'] = FarmerFeedback::STATE_ACTIVE;
            $farmerFeedback = FarmerFeedback::create($farmerFeedbackData);
            if ($request->file('path')) {
                foreach ($request->file('path') as $imagefile) {
                    $image = new Image();
                    $path = $imagefile->store('image', 'public');
                    $image->path = $path;
                    $image->model_id = $farmerFeedback->id;
                    $image->model_type = FarmerFeedback::class;
                    $image->save();
                }
            }
            $feedbackResult  = FarmerFeedback::with('createdBy', 'issueType', 'issueType.createdBy', 'images', 'tractor')->where('id', $farmerFeedback->id)->first();
            return returnSuccessResponse('Feedback added successfully', $feedbackResult);
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
                $farmerFeedback = FarmerFeedback::with('images', 'createdBy', 'issueType', 'issueType.createdBy', 'tractor')->where('id', $request->id)->first();

                if ($farmerFeedback->state_id == FarmerFeedback::STATE_ACTIVE) {
                    $farmerFeedback->pending_states = [FarmerFeedback::STATE_CLOSED, FarmerFeedback::STATE_COMPLETED];
                } elseif ($farmerFeedback->state_id == FarmerFeedback::STATE_CLOSED) {
                    $farmerFeedback->pending_states = [FarmerFeedback::STATE_ACTIVE, FarmerFeedback::STATE_COMPLETED];
                } elseif ($farmerFeedback->state_id == FarmerFeedback::STATE_COMPLETED) {
                    $farmerFeedback->pending_states = [FarmerFeedback::STATE_ACTIVE, FarmerFeedback::STATE_CLOSED];
                }

                return returnSuccessResponse('Feedback detail', $farmerFeedback);
            } elseif (Auth::user()->role_id == User::ROLE_FARMER) {
                $farmerFeedback = FarmerFeedback::with('createdBy', 'issueType', 'issueType.createdBy')->where('id', $request->id)->first();
                if ($farmerFeedback->created_by != Auth::user()->id) {
                    return returnNotAllowedResponse('403, You are not allowed to perform this action.');
                }
                return returnSuccessResponse('Feedback detail', $farmerFeedback);
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
            'id' => 'required',
            'name' => 'required',
            'email' => 'required|email',
            'issue_type_id' => 'required',
            'tractor_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $farmerFeedback = FarmerFeedback::with('createdBy', 'issueType', 'issueType.createdBy', 'tractor')->where('id', $request->id)->first();
            if (!$farmerFeedback) {
                return returnNotFoundResponse('No feedback found');
            } elseif ($farmerFeedback->state_id != FarmerFeedback::STATE_ACTIVE) {
                return returnNotAllowedResponse('Not allowed to update feedback, because it is not in active state');
            }
            $farmerFeedback->update($request->all());
            if ($request->file('path')) {
                $oldImages = Image::where(['model_id' => $farmerFeedback->id, 'model_type' => FarmerFeedback::class]);
                if ($oldImages) {
                    $oldImages->delete();
                }
                foreach ($request->file('path') as $imagefile) {
                    $image = new Image();
                    $path = $imagefile->store('image', 'public');
                    $image->path = $path;
                    $image->model_id = $farmerFeedback->id;
                    $image->model_type = Tractor::class;
                    $image->save();
                }
            }
            return returnSuccessResponse('Feedback updated successfully', $farmerFeedback->createJsonResponse());
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
            $farmerFeedback = FarmerFeedback::findorFail($request->id);
            if (!$farmerFeedback) {
                return returnNotFoundResponse('No feedback found');
            } elseif ($farmerFeedback->state_id != FarmerFeedback::STATE_ACTIVE) {
                return returnNotAllowedResponse('Not allowed to update feedback, because it is not in active state');
            } elseif ($farmerFeedback->created_by != Auth::user()->id || in_array(Auth::user()->role_id, [User::ROLE_ADMIN, User::ROLE_SUB_ADMIN, User::ROLE_GOVERNMENT])) {
                return returnNotAllowedResponse('403, You are not allowed to perform this action.');
            }
            $farmerFeedback->delete();
            return response()->json(['status' => true, 'message' => 'Feedback deleted successfully', 'data' => null]);
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
            if (Auth::user()->role_id != User::ROLE_ADMIN) {
                return returnNotAllowedResponse('403, You are not allowed to perform this action.');
            }
            $farmerFeedback = FarmerFeedback::with('images', 'createdBy', 'issueType', 'issueType.createdBy', 'tractor')->where('id', $request->id)->first();
            if (!$farmerFeedback) {
                return returnNotFoundResponse('No feedback found');
            }
            if (!in_array($request->state_id, [FarmerFeedback::STATE_ACTIVE, FarmerFeedback::STATE_CLOSED, FarmerFeedback::STATE_COMPLETED])) {
                return returnErrorResponse('Please select a valid status.');
            }
            $farmerFeedback->state_id = $request->state_id;
            $farmerFeedback->save();
            return returnSuccessResponse('Feedback status updated successfully', $farmerFeedback);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function conclusion(Request $request)
    {
        $rules = [
            'id' => 'required',
            'conclusion' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $farmerFeedback = FarmerFeedback::with('images', 'createdBy', 'issueType', 'issueType.createdBy', 'tractor')->where('id', $request->id)->first();
            if (!$farmerFeedback) {
                return returnNotFoundResponse('No feedback found');
            }

            $farmerFeedback->state_id = $request->state_id ? $request->state_id : $farmerFeedback->state_id;
            $farmerFeedback->conclusion = $request->conclusion;
            $farmerFeedback->tech_details = $request->tech_details;
            $farmerFeedback->save();
            return returnSuccessResponse('Conclusion updated successfully', $farmerFeedback);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function tractorList(Request $request)
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
            $tractorIds = TractorBooking::where('created_by', Auth::id())->pluck('tractor_id')->toArray();
            $tractors = Tractor::whereIn('id', $tractorIds)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
            $totalCount = $tractors->total();
            $total_pages = ceil($totalCount / $request->records_per_page);

            $returnArrData = [
                'tractors' => $tractors->all(),
                'page_no' => $request->page_no,
                'total_entries' => $totalCount,
                'total_pages' => $total_pages
            ];
            return returnSuccessResponse('Get all tractor list successfully ', $returnArrData);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }
}
