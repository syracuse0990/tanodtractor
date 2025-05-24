<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\TractorGroup;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class AlertController
 * @package App\Http\Controllers
 */
class AlertController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
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
        $allData = Alert::query();
        if ($request->alarm_type) {
          $allData = $allData->where('alarm_type', $request->alarm_type);
        }
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
          $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
          $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
          $deviceIds = [];
          $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
          $deviceIds = multiDimToSingleDim($deviceIds);
          $imeis = Device::whereIn('id', $deviceIds)->pluck('imei_no')->toArray();
          $allData = $allData->whereIn('imei', $imeis);
        }
        $allData = $allData->with('createdBy', 'deviceDetail')->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
        $totalCount = $allData->total();
        $total_pages = ceil($totalCount / $request->records_per_page);

        $returnArrData = [
          'alerts' => $allData->all(),
          'page_no' => $request->page_no,
          'total_entries' => $totalCount,
          'total_pages' => $total_pages
        ];
        return returnSuccessResponse('Get alerts list successfully', $returnArrData);
      } elseif (Auth::user()->role_id == User::ROLE_FARMER) {

        $allData = Alert::where('user_id', Auth::user()->id);
        if ($request->alarm_type) {
          $allData = $allData->where('alarm_type', $request->alarm_type);
        }
        $allData = $allData->with('createdBy', 'deviceDetail')->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
        $totalCount = $allData->total();
        $total_pages = ceil($totalCount / $request->records_per_page);

        $returnArrData = [
          'alerts' => $allData->all(),
          'page_no' => $request->page_no,
          'total_entries' => $totalCount,
          'total_pages' => $total_pages
        ];
        return returnSuccessResponse('Get alerts list successfully', $returnArrData);
      }
    } catch (\Exception $e) {
      return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
    }
  }
}
