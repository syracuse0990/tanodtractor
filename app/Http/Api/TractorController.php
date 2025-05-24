<?php

namespace App\Http\Api;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Jobs\ExportDeviceData;
use App\Jobs\ExportFarmers;
use App\Jobs\ExportFeedback;
use App\Jobs\ExportOverview;
use App\Jobs\ExportReport;
use App\Models\AssignedDevice;
use App\Models\AssignedGroup;
use App\Models\AssignedTractor;
use App\Models\Device;
use App\Models\Export;
use App\Models\FarmerFeedback;
use App\Models\Image;
use App\Models\Maintenance;
use App\Models\Notification;
use App\Models\Tractor;
use App\Models\TractorBooking;
use App\Models\TractorGroup;
use App\Models\User;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class TractorController
 * @package App\Http\Controllers
 */
class TractorController extends Controller
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
                if ($request->allData) {
                    $tractor = Tractor::query();
                    if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                        $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                        $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                        $tractorIds = [];
                        $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
                        $tractorIds = multiDimToSingleDim($tractorIds);
                        $tractor = $tractor->whereIn('id', $tractorIds);
                    }
                    $tractor = $tractor->with('images')->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                    $totalCount = $tractor->total();
                    $total_pages = ceil($totalCount / $request->records_per_page);

                    $returnArrData = [
                        'tractors' => $tractor->all(),
                        'page_no' => $request->page_no,
                        'total_entries' => $totalCount,
                        'total_pages' => $total_pages
                    ];
                } else {
                    if ($request->group_id) {
                        $tractorData = TractorGroup::where('id', '!=', $request->group_id)->pluck('tractor_ids')->toArray();
                        $tractor_ids = multiDimToSingleDim($tractorData);
                        $tractors = Tractor::with('images')->whereNotIn('id', $tractor_ids)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                        $totalCount = $tractors->total();
                        $total_pages = ceil($totalCount / $request->records_per_page);

                        $returnArrData = [
                            'tractors' => $tractors->all(),
                            'page_no' => $request->page_no,
                            'total_entries' => $totalCount,
                            'total_pages' => $total_pages
                        ];
                    } else {
                        $tractorData = TractorGroup::pluck('tractor_ids')->toArray();
                        $tractor_ids = multiDimToSingleDim($tractorData);
                        $tractors = Tractor::with('images')->whereNotIn('id', $tractor_ids)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                        $totalCount = $tractors->total();
                        $total_pages = ceil($totalCount / $request->records_per_page);

                        $returnArrData = [
                            'tractors' => $tractors->all(),
                            'page_no' => $request->page_no,
                            'total_entries' => $totalCount,
                            'total_pages' => $total_pages
                        ];
                    }
                }
                return returnSuccessResponse('Get all tractor list successfully', $returnArrData);
            } elseif (Auth::user()->role_id == User::ROLE_FARMER) {
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
                if (!empty($farmerGroup->tractor_ids)) {
                    $tractors = Tractor::with('images')->whereIn('id', json_decode($farmerGroup->tractor_ids, true))->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
                    $totalCount = $tractors->total();
                    $total_pages = ceil($totalCount / $request->records_per_page);

                    $returnArrData = [
                        'tractors' => $tractors->all(),
                        'page_no' => $request->page_no,
                        'total_entries' => $totalCount,
                        'total_pages' => $total_pages
                    ];
                    return returnSuccessResponse('Get all tractor list successfully ', $returnArrData);
                } else {
                    return returnSuccessResponse('No record found!!');
                }
            }
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
            'no_plate' => 'required',
            'id_no' => 'required',
            'engine_no' => 'required',
            'fuel_consumption' => 'required',
            'brand' => 'required',
            'model' => 'required',
            'installation_time' => 'required',
            'maintenance_kilometer' => 'required|numeric',
            'installation_address' => 'required',
            'path' => 'required|array|min:1|max:5',
            'path.*' => 'image|mimes:jpeg,png,jpg',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $tractorData = $request->all();
            $tractorData['state_id'] = (!empty($request->state_id)) ? $request->state_id : Tractor::STATE_ACTIVE;

            $tractor = Tractor::create($tractorData);
            if ($request->file('path')) {
                foreach ($request->file('path') as $imagefile) {
                    $image = new Image();
                    $path = $imagefile->store('image', 'public');
                    $image->path = $path;
                    $image->model_id = $tractor->id;
                    $image->model_type = Tractor::class;
                    $image->save();
                }
            }
            return returnSuccessResponse('Tractor created successfully', $tractor->tractorJsonResponse());
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
            if ($request->notification_id) {
                $notification  = Notification::findorFail($request->notification_id);
                if ($notification) {
                    $notification->is_read = Notification::IS_READ;
                    $notification->save();
                }
            }
            if (in_array(Auth::user()->role_id, [User::ROLE_ADMIN, User::ROLE_SUB_ADMIN, User::ROLE_GOVERNMENT])) {
                $tractor = Tractor::findorFail($request->id);
                if (empty($tractor)) {
                    return returnNotFoundResponse('No Tractor found');
                }
                return returnSuccessResponse('Tractor detail', $tractor->tractorJsonResponse());
            } elseif (Auth::user()->role_id == User::ROLE_FARMER) {
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
                if (!empty($farmerGroup->tractor_ids)) {
                    if (in_array($request->id, json_decode($farmerGroup->tractor_ids, true))) {

                        $tractor = Tractor::findorFail($request->id);
                        if (empty($tractor)) {
                            return returnSuccessResponse('No slots available.');
                        }
                        return returnSuccessResponse('Get tractor detail successfully. ', $tractor->tractorJsonResponse());
                    } else {
                        return returnNotFoundResponse('This tractor not found in your group.');
                    }
                } else {
                    return returnSuccessResponse('No record found!!');
                }
            }
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Tractor $tractor
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tractor $tractor)
    {
        $rules = [
            'id' => 'required',
            'no_plate' => 'required',
            'id_no' => 'required',
            'engine_no' => 'required',
            'fuel_consumption' => 'required',
            'brand' => 'required',
            'model' => 'required',
            'installation_time' => 'required',
            'maintenance_kilometer' => 'required|numeric',
            'installation_address' => 'required',
            'path' => 'array|min:1|max:5',
            'path.*' => 'image|mimes:jpeg,png,jpg',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        try {
            $tractorData = $request->all();
            $tractorData['state_id'] = (!empty($request->state_id)) ? $request->state_id : Tractor::STATE_ACTIVE;
            $tractor = Tractor::findorFail($request->id);
            $tractor->update($tractorData);
            if ($request->file('path')) {
                $oldImages = Image::where(['model_id' => $tractor->id, 'model_type' => Tractor::class]);
                if ($oldImages) {
                    $oldImages->delete();
                }
                foreach ($request->file('path') as $imagefile) {
                    $image = new Image();
                    $path = $imagefile->store('image', 'public');
                    $image->path = $path;
                    $image->model_id = $tractor->id;
                    $image->model_type = Tractor::class;
                    $image->save();
                }
            }
            return returnSuccessResponse('Tractor updated successfully', $tractor->tractorJsonResponse());
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
            $tractor = Tractor::findorFail($request->id);
            if (!empty($tractor)) {
                $images = Image::where(['model_id' => $tractor->id, 'model_type' => Tractor::class]);
                if ($images) {
                    $images->delete();
                }
                $tractor->delete();
                return response()->json(['status' => true, 'message' => 'Tractor deleted successfully', 'data' => null]);
            } else {
                return response()->json(['status' => false, 'message' => 'Tractor not found', 'data' => null]);
            }
        } catch (\Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * assign group to the specified resource.
     */
    public function assignGroup(Request $request)
    {
        $rules = [
            'tractor_id' => 'required',
            'group_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }

        try {
            $tractor = Tractor::findorFail($request->tractor_id);
            if (empty($tractor)) {
                return returnNotFoundResponse('No Tractor found');
            }
            $group = TractorGroup::findorFail($request->group_id);
            if (empty($group)) {
                return returnNotFoundResponse('No Group found.');
            }
            $tractor->group_id = $request->group_id;
            $tractor->save();
            return returnSuccessResponse('Tractor detail', $tractor);
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function maintenanceTractorList(Request $request)
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
            $maintenanceTractorIds = Maintenance::pluck('tractor_ids')->toArray();

            $tractor = Tractor::with('images')->whereIn('id', $maintenanceTractorIds)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
            $totalCount = $tractor->total();
            $total_pages = ceil($totalCount / $request->records_per_page);

            $returnArrData = [
                'tractors' => $tractor->all(),
                'page_no' => $request->page_no,
                'total_entries' => $totalCount,
                'total_pages' => $total_pages
            ];
            return returnSuccessResponse('Get all maintenance tractor list successfully', $returnArrData);
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function bookingsList(Request $request)
    {
        $rules = [
            'id' => 'required',
            'records_per_page' => 'required',
            'page_no' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {

            $tractorBookings = TractorBooking::with('tractor', 'tractor.images', 'device', 'createdBy')->where('tractor_id', $request->id)->orderBy('state_id', 'ASC')->orderBy('id', 'DESC')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
            $totalCount = $tractorBookings->total();
            $total_pages = ceil($totalCount / $request->records_per_page);

            $returnArrData = [
                'bookings' => $tractorBookings->all(),
                'page_no' => $request->page_no,
                'total_entries' => $totalCount,
                'total_pages' => $total_pages
            ];
            return returnSuccessResponse('Get all tractor booking list successfully', $returnArrData);
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function sendMaintenanceNotification(Request $request)
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
            $admin = User::where('role_id', User::ROLE_ADMIN)->latest('id')->first();

            $tractor = Tractor::findorFail($request->id);
            if ($tractor->running_km >= $tractor->maintenance_kilometer) {
                $notification = new Notification();
                $notification->user_id = $admin->id;
                $notification->title = 'Maintenance Required';
                $notification->message = $tractor?->id_no . ' (' . $tractor?->model . ') tractor has completed the maintenance kilometer.';
                $notification->tractor_id = $tractor->id;
                $notification->is_read = Notification::IS_NOT_READ;
                $notification->type_id = Notification::TYPE_MAINTENANCE;
                $notification->save();

                $notificationdata = [
                    'body' => $tractor?->id_no . ' (' . $tractor?->model . ') tractor has completed the maintenance kilometer.',
                    'message' => 'Maintenance Required',
                    'notification_type' => Notification::TYPE_MAINTENANCE,
                    'user_id' => $admin->id,
                    'tractor_id' => $tractor->id,
                    'notification_id' => $notification->id
                ];

                $fcm_token = $admin->fcm_token;
                if ($fcm_token) {
                    NotificationHelper::sendPushNotification($fcm_token, $notificationdata);
                }
            }

            return returnSuccessResponse('Notification sent.', $notification);
        } catch (\Exception $e) {

            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function exportReport(Request $request)
    {
        $request->validate([
            'type_id' => 'required|numeric'
        ]);
        if ($request->type_id == Export::TYPE_TRACTOR) {
            $tractorIds = $request->tractor_ids ?  explode(',', $request->tractor_ids) : [];
            $fileName = 'reports_' . date('Ymdhis') . '.csv';
            $assignIds = AssignedTractor::where('user_id', Auth::id())->pluck('tractor_id')->toArray();
            $tractors = Tractor::query();
            if ($tractorIds) {
                $tractors = $tractors->whereIn('id', $tractorIds);
            }
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
                $tractorIds = multiDimToSingleDim($tractorIds);
                $tractors = $tractors->whereIn('id', $tractorIds);
            }
            $tractors = $tractors->get();
            ExportReport::dispatch($tractors, $fileName);
        } elseif ($request->type_id == Export::TYPE_FEEDBACK) {
            $fileName = 'feedback_reports_' . date('Ymdhis') . '.csv';
            $farmerFeedbacks = FarmerFeedback::query();
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
                $tractorIds = multiDimToSingleDim($tractorIds);
                $farmerFeedbacks = $farmerFeedbacks->whereIn('tractor_id', $tractorIds);
            }
            $farmerFeedbacks = $farmerFeedbacks->get();
            ExportFeedback::dispatch($fileName, $farmerFeedbacks);
        } elseif ($request->type_id == Export::TYPE_DEVICE) {
            $deviceIds = $request->device_ids;
            $fileName = 'motion_overview_' . date('Ymdhis') . '.csv';
            $devices = Device::query();
            if ($deviceIds) {
                $devices = $devices->whereIn('id', $deviceIds);
            }
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                $deviceIds = [];
                $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
                $deviceIds = multiDimToSingleDim($deviceIds);
                $devices = $devices->whereIn('id', $deviceIds);
            }
            $devices = $devices->get();
            ExportDeviceData::dispatch($devices, $fileName);
        } elseif ($request->type_id == Export::TYPE_OVERVIEW) {
            $fileName = 'overview_' . date('Ymdhis') . '.csv';
            $bookings = TractorBooking::query();
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
                $deviceIds = multiDimToSingleDim($deviceIds);
                $bookings =  $bookings->whereIn('device_id', $deviceIds);
            }
            if ($request->search) {
                $device = Device::where('imei_no', $request->search)->first();
                $bookings =  $bookings->where('device_id', $device->id);
            }
            $bookings =  $bookings->orderBy('device_id', 'ASC')->get();
            ExportOverview::dispatch($bookings, $fileName);
        } elseif ($request->type_id == Export::TYPE_FARMER) {
            if ($request->id) {
                $group = TractorGroup::find($request->id);
                $fileName = 'farmers_' . date('Ymdhis') . '.csv';
                Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_FARMER])->latest('id')->delete();
                Export::create([
                    'file_name' => $fileName,
                    'type_id' => Export::TYPE_FARMER
                ]);

                ExportFarmers::dispatch($fileName, $group->getUsers());
                return returnSuccessResponse('Exporting added to queue !!', ['file_name' => $fileName]);
            } else {
                if (!User::where('role_id', User::ROLE_FARMER)->count()) {
                    return  response()->json(['status' => false, 'message' => 'No data found', 'data' => (object)[]]);
                }
                $fileName = 'farmers_' . date('Ymdhis') . '.csv';
                Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_FARMER])->latest('id')->delete();
                $export = Export::create([
                    'file_name' => $fileName,
                    'type_id' => Export::TYPE_FARMER
                ]);

                $farmers = User::where('role_id', User::ROLE_FARMER)->get();
                ExportFarmers::dispatch($fileName, $farmers);
                return returnSuccessResponse('Exporting added to queue !!', ['file_name' => $fileName]);
            }
        } else {
            return returnSuccessResponse('Please select a type from the valid options (i.e., 1, 2, 3, 4, 5)!!');
        }
        return returnSuccessResponse('Exporting added to queue !!', ['file_name' => $fileName]);
    }

    public function downloadReport(Request $request)
    {
        $rules = [
            'filename' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            throw new HttpResponseException(returnValidationErrorResponse($errorMessages[0]));
        }
        try {
            $originalFileName = $request->filename;
            $filePath = storage_path('app/public/csv/' . $originalFileName);

            if (!file_exists($filePath)) {
                return response()->json(['is_download' => false, 'download_url' => '']);
            }

            $downloadUrl = route('tractors.download', ['filename' => $request->filename]);
            return response()->json([
                'is_download' => true,
                'download_url' => $downloadUrl
            ]);
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
            $assignedIds = AssignedTractor::where('user_id', '!=', $request->user_id)->pluck('tractor_id')->toArray();
            $assignedTractors = AssignedTractor::where('user_id', $request->user_id)->pluck('tractor_id')->toArray();

            $tractor = Tractor::whereNotIn('id', $assignedIds)->latest('id')->paginate($request->records_per_page, ['*'], 'page', $request->page_no);
            $totalCount = $tractor->total();
            $total_pages = ceil($totalCount / $request->records_per_page);
            foreach ($tractor as $device) {
                $assigned = false;
                if (in_array($device->id, $assignedTractors)) {
                    $assigned = true;
                }
                $device->assign = $assigned;
            }
            $returnArrData = [
                'tractors' => $tractor->all(),
                'page_no' => $request->page_no,
                'total_entries' => $totalCount,
                'total_pages' => $total_pages
            ];
            return returnSuccessResponse('Get all device list successfully', $returnArrData);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }

    public function assignTractor(Request $request)
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

            $tractor = Tractor::findorFail($request->id);
            if ($request->state == '1') {
                $assignedTractor = AssignedTractor::create([
                    'user_id' => $request->user_id,
                    'tractor_id' => $request->id,
                ]);
                $status = 'assigned';
            } else {
                $assignedTractor = AssignedTractor::where(['tractor_id' => $request->id, 'user_id' => $request->user_id])->delete();
                $status = 'un assigned';
            }
            return returnSuccessResponse('Tractor ' . $status . ' successfully ', []);
        } catch (Exception $e) {
            return  response()->json(['status' => false, 'message' => 'An error occurred:' . $e->getMessage(), 'data' => []]);
        }
    }
}
