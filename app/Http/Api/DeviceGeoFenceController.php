<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceGeoFence;
use App\Models\Maintenance;
use App\Models\Notification;
use App\Models\Tractor;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class DeviceGeoFenceController
 * @package App\Http\Controllers
 */
class DeviceGeoFenceController extends Controller
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
            $deviceGeoFence = DeviceGeoFence::query();
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $deviceGeoFence = $deviceGeoFence->where('created_by', Auth::id());
            }
            $deviceGeoFence = $deviceGeoFence->with('createdBy')->latest()->paginate($request->records_per_page, ['*'], 'page', $request->page_no);;
            $totalCount = $deviceGeoFence->total();
            $total_pages = ceil($totalCount / $request->records_per_page);

            $returnArrData = [
                'device_geo_fence' => $deviceGeoFence->all(),
                'page_no' => $request->page_no,
                'total_entries' => $totalCount,
                'total_pages' => $total_pages
            ];
            return returnSuccessResponse('Get device geo fence list successfully', $returnArrData);
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

            if ($request->notification_id) {
                $notification  = Notification::findorFail($request->notification_id);
                if ($notification) {
                    $notification->is_read = Notification::IS_READ;
                    $notification->save();
                }
            }

            $DeviceGeoFence = DeviceGeoFence::with('createdBy')->where('id', $request->id)->first();
            if (empty($DeviceGeoFence)) {
                return returnNotFoundResponse('No geo fence found.');
            }
            return returnSuccessResponse('Geo fence detail', $DeviceGeoFence);
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function detail(Request $request)
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

            $DeviceGeoFence = DeviceGeoFence::with('createdBy')->where(['imei' => $request->imei, 'state_id' => DeviceGeoFence::STATE_ACTIVE])->first();
            if (empty($DeviceGeoFence)) {
                return returnSuccessResponse('No geo fence found.');
            }
            return returnSuccessResponse('Get geo fence details successfully', $DeviceGeoFence);
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }
}
