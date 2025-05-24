<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use App\Models\Tractor;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class MaintenanceController
 * @package App\Http\Controllers
 */
class MaintenanceController extends Controller
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
            $tractorIds = [];
            $maintenance = Maintenance::query();
            if ($request->search) {
                $tractorIds = Tractor::where(function (Builder $query) use ($request) {
                    return $query->where('id_no', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('model', 'LIKE', '%' . $request->search . '%');
                })->pluck('id')->toArray();
                $maintenance = $maintenance->whereIn('tractor_ids', $tractorIds);
            }
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $maintenance = $maintenance->where('created_by', Auth::id());
            }
            $maintenance = $maintenance->with('createdBy', 'tractor', 'tractor.images')->latest()->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
            $totalCount = $maintenance->total();
            $total_pages = ceil($totalCount / $request->records_per_page);

            $returnArrData = [
                'maintenance' => $maintenance->all(),
                'page_no' => $request->page_no,
                'total_entries' => $totalCount,
                'total_pages' => $total_pages
            ];
            return returnSuccessResponse('Get maintenance list successfully', $returnArrData);
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
            'tractor_ids' => 'required',
            'maintenance_date' => 'required',
            'tech_name' => 'required',
            'tech_email' => 'required|email',
            'tech_number' => 'required|numeric',
            'tech_iso_code' => 'required',
            'tech_phone_code' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        $maintenanceTractors = Maintenance::get();
        foreach ($maintenanceTractors as $data) {
            if ($request->tractor_ids == $data->tractor_ids && date('Y-m-d', strtotime($request->maintenance_date)) == date('Y-m-d', strtotime($data->maintenance_date))) {
                throw new HttpResponseException(returnValidationErrorResponse('Tractor maintenance already exists'));
            }
        }
        try {

            $maintenanceData = $request->all();
            $maintenanceData['state_id'] = Maintenance::STATE_DOCUMENTATION;
            $maintenance = Maintenance::create($maintenanceData);
            return returnSuccessResponse('Maintenance created successfully', $maintenance->createJsonResponse());
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
            'tractor_ids' => 'required',
            'maintenance_date' => 'required',
            'tech_name' => 'required',
            'tech_email' => 'required|email',
            'tech_number' => 'required|numeric|digits_between:10,16',
            'tech_iso_code' => 'required',
            'tech_phone_code' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        $maintenance = Maintenance::findorFail($request->id);
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $maintenance = $maintenance->where('created_by', Auth::id());
        }
        $maintenanceTractors = Maintenance::where('id', '!=', $maintenance->id)->get();
        foreach ($maintenanceTractors as $data) {
            if ($request->tractor_ids == $data->tractor_ids && date('Y-m-d', strtotime($request->maintenance_date)) == date('Y-m-d', strtotime($data->maintenance_date))) {
                throw new HttpResponseException(returnValidationErrorResponse('Tractor maintenance already exists'));
            }
        }
        try {

            if ($maintenance) {
                $maintenanceData = $request->all();
                $maintenance->update($request->all());
                return returnSuccessResponse('Maintenance updated successfully', $maintenance->createJsonResponse());
            } else {
                return returnNotFoundResponse('No maintenance data found.');
            }
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function updateConclusion(Request $request)
    {
        $maintenance = Maintenance::findorFail($request->id);
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
            if ($maintenance) {
                $maintenanceData = $request->all();
                $maintenance->update($maintenanceData);
                return returnSuccessResponse('Maintenance updated successfully', $maintenance->createJsonResponse());
            } else {
                return returnNotFoundResponse('No maintenance data found.');
            }
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

            $maintenance = Maintenance::with('createdBy', 'tractor', 'tractor.images')->where('id', $request->id)->first();
            if (empty($maintenance)) {
                return returnNotFoundResponse('No maintenance found.');
            }
            $tractors = [];
            return returnSuccessResponse('maintenance detail', $maintenance);
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
            $maintenance = Maintenance::findorFail($request->id);
            if (empty($maintenance)) {
                return returnNotFoundResponse('No maintenance found.');
            }
            $maintenance->delete();
            return response()->json(['status' => true, 'message' => 'maintenance deleted successfully', 'data' => null]);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }


    public function changeStatus(Request $request)
    {
        $rules = [
            'id' => 'required',
            'state_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $maintenance = Maintenance::findorFail($request->id);
            if (empty($maintenance)) {
                return returnNotFoundResponse('Maintenance not found');
            }
            if (!in_array($request->state_id, [Maintenance::STATE_DOCUMENTATION, Maintenance::STATE_CANCELLED, Maintenance::STATE_FILLED, Maintenance::STATE_INPROGRESS, Maintenance::STATE_COMPLETED])) {
                return returnErrorResponse('Please send a valid status.');
            }
            if (in_array($request->state_id, [Maintenance::STATE_COMPLETED, Maintenance::STATE_CANCELLED]) && empty($maintenance->conclusion)) {
                return returnErrorResponse('Please add conclusion to change maintenance state to ' . $maintenance->getStateName($request->state_id) . '.');
            }
            $maintenance->state_id = $request->state_id;
            if (!$maintenance->save()) {
                return returnErrorResponse('unable to update booking status, please try again later.');
            }
            return returnSuccessResponse('Booking status updated successfully', $maintenance->createJsonResponse());
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function filter(Request $request)
    {
        $rules = [
            'tractor_id' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $startDate = $request->start_date . ' 00:00:00';
            $endDate = $request->end_date . ' 23:59:59';
            $maintenance =  Maintenance::with('createdBy', 'tractor', 'tractor.images')->where('tractor_ids', $request->tractor_id)->whereBetween('maintenance_date', [$startDate, $endDate]);
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $maintenance = $maintenance->where('created_by', Auth::id());
            }
            $maintenance = $maintenance->latest('id')->get();

            $returnArrData = [
                'maintenance' => $maintenance,
                'page_no' => null,
                'total_entries' => null,
                'total_pages' => null
            ];

            return returnSuccessResponse('Get filtered maintenance list successfully.', $returnArrData);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function mainteanceData()
    {
        try {
            $maintenanceCount = Maintenance::query();
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $maintenanceCount = $maintenanceCount->where('created_by', Auth::id())->count();
            } else {
                $maintenanceCount = $maintenanceCount->count();
            }
            $maintenances = Maintenance::query();
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $maintenances = $maintenances->where('created_by', Auth::id())->get();
            } else {
                $maintenances = $maintenances->get();
            }
            $documentation = $filled = $inprogress = $completed = $cancelled = 0;
            foreach ($maintenances as $key => $maintenance) {
                if ($maintenance->state_id == Maintenance::STATE_DOCUMENTATION) {
                    $documentation++;
                } elseif ($maintenance->state_id == Maintenance::STATE_FILLED) {
                    $filled++;
                } elseif ($maintenance->state_id == Maintenance::STATE_INPROGRESS) {
                    $inprogress++;
                } elseif ($maintenance->state_id == Maintenance::STATE_COMPLETED) {
                    $completed++;
                } elseif ($maintenance->state_id == Maintenance::STATE_CANCELLED) {
                    $cancelled++;
                }
            }
            $data = [
                'total' => $maintenanceCount,
                'documentation' => $documentation,
                'filled' => $filled,
                'inprogress' => $inprogress,
                'completed' => $completed,
                'cancelled' => $cancelled,
            ];

            return returnSuccessResponse('Get maintenance data successfully.', $data);
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }
}
