<?php

namespace App\Http\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class DeviceGeoFenceController
 * @package App\Http\Controllers
 */
class NotificationController extends Controller
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
            $notifications = Notification::where(['user_id' => Auth::id(), 'is_read' => Notification::IS_NOT_READ])->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
            $totalCount = $notifications->total();
            $total_pages = ceil($totalCount / $request->records_per_page);
            $returnData = [
                'notification' => $notifications->all(),
                'page_no' => $request->page_no,
                'total_entries' => $totalCount,
                'total_pages' => $total_pages
            ];
            return returnSuccessResponse('Get notification list successfully.', $returnData);
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * Get if there is any unread notifications.
     *
     * @param  int $id
     * @return boolean
     */
    public function unreadNotifications()
    {
        $unreadNotifications = false;
        try {
            $notifications = Notification::where('user_id', Auth::user()->id)->where('is_read', Notification::IS_NOT_READ)->count();
            if ($notifications) {
                $unreadNotifications = true;
            }
            return returnSuccessResponse('Get notification status successfully', $unreadNotifications);
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }
}
