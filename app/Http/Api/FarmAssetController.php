<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\FarmAsset;
use App\Models\User;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class FarmAssetController
 * @package App\Http\Controllers
 */
class FarmAssetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $rules = [
            'records_per_page' => 'required|integer|min:1',
            'page_no' => 'required|integer|min:1',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {

            $farmAssets = FarmAsset::query();
            if (Auth::user()->role_id == User::ROLE_SUB_ADMIN) {
                $farmAssets = $farmAssets->whereIn('created_by', [Auth::id(), User::ROLE_ADMIN, User::ROLE_GOVERNMENT, User::ROLE_SYSTEM_ADMIN]);
            }

            $farmAssets = $farmAssets->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
            $totalCount = $farmAssets->total();
            $total_pages = ceil($totalCount / $request->records_per_page);

            $farmAssets = $farmAssets->getCollection()->transform(function ($farmAsset) {
                return $farmAsset->jsonResponse();
            });
            $returnData = [
                'farm_assets' => $farmAssets,
                'page_no' => $request->page_no,
                'total_entries' => $totalCount,
                'total_pages' => $total_pages
            ];
            return returnSuccessResponse('Get farm assets list successfully ', $returnData);
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'number_plate' => 'required',
            'mileage' => 'required',
            'type_id' => 'required',
            'condition' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $farmAsset = FarmAsset::create($request->all());
            return returnSuccessResponse('Farm asset created successfully', $farmAsset->jsonResponse());
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
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
            $farmAsset = FarmAsset::findorFail($request->id);
            return returnSuccessResponse('Get farm asset detail successfully. ', $farmAsset->jsonResponse());
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Device $device
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $rules = [
            'number_plate' => 'required',
            'mileage' => 'required',
            'type_id' => 'required',
            'condition' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        try {
            $farmAsset = FarmAsset::findorFail($request->id);
            if (empty($farmAsset)) {
                return returnNotFoundResponse('No asset found.');
            }
            $farmAsset->update($request->all());
            return returnSuccessResponse('Farm asset updated successfully', $farmAsset->jsonResponse());
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
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
            $farmAsset = FarmAsset::findorFail($request->id);
            if (!empty($farmAsset)) {
                $farmAsset->delete();
                return response()->json(['status' => true, 'message' => 'Farm asset deleted successfully', 'data' => []]);
            } else {
                return returnNotFoundResponse('No asset found.');
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }
}
