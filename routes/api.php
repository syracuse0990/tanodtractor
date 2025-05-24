<?php

use App\Http\Api\AuthController;
use App\Http\Api\BookingController;
use App\Http\Api\DeviceController;
use App\Http\Api\DeviceGeoFenceController;
use App\Http\Api\FarmerFeedbackController;
use App\Http\Api\GroupController;
use App\Http\Api\IssueTypeController;
use App\Http\Api\JimiController;
use App\Http\Api\MaintenanceController;
use App\Http\Api\PageController;
use App\Http\Api\TractorController;
use App\Http\Api\UserController;
use App\Http\Api\AlertController;
use App\Http\Api\FarmAssetController;
use App\Http\Api\NotificationController;
use App\Http\Api\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication Routes
Route::prefix('user')->name('user.')->group(function () {
    Route::post('/all-alert-list', [AlertController::class, 'allAlertList']);
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/send-mobile-otp', [AuthController::class, 'verifyMobile']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/expire-otp', [AuthController::class, 'expireOtp']);

    // User Login Route
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);

    Route::post('/get-api-data', [AuthController::class, 'getData']);
    Route::get('/page-detail', [PageController::class, 'show']);
    Route::post('/send-maintenance-notification', [TractorController::class, 'sendMaintenanceNotification']);


    Route::middleware([
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
    ])->group(function () {
        //Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/details', [AuthController::class, 'details']);
        Route::post('/update-password', [AuthController::class, 'updatePassword']);
        Route::post('/update-profile', [AuthController::class, 'updateProfile']);


        //User
        Route::post('/user-list', [UserController::class, 'index']);
        Route::post('/user-detail', [UserController::class, 'show']);
        Route::post('/user-update', [UserController::class, 'update']);
        Route::post('/delete-user', [UserController::class, 'destroy']);
        Route::post('/create-sub-admin', [UserController::class, 'subAdmin']);
        Route::post('/update-sub-admin', [UserController::class, 'subAdminUpdate']);
        Route::get('/assign-users', [UserController::class, 'assignIndex']);
        Route::post('/assign-user', [UserController::class, 'assignUser']);
        Route::post('/farmer-import', [UserController::class, 'import']);
        Route::get('/farmer-export', [UserController::class, 'export']);
        Route::get('/farmer-import-format', [UserController::class, 'importFormat']);

        //Group
        Route::post('/group-list', [GroupController::class, 'index']);
        Route::post('/create-group', [GroupController::class, 'store']);
        Route::post('/group-detail', [GroupController::class, 'show']);
        Route::post('/update-group', [GroupController::class, 'update']);
        Route::post('/delete-group', [GroupController::class, 'destroy']);
        Route::get('/assign-groups', [GroupController::class, 'assignIndex']);
        Route::post('/assign-group', [GroupController::class, 'assignGroup']);

        //Device
        Route::post('/device-list', [DeviceController::class, 'index']);
        Route::post('/create-device', [DeviceController::class, 'store']);
        Route::get('/device-detail', [DeviceController::class, 'show']);
        Route::post('/update-device', [DeviceController::class, 'update']);
        Route::post('/delete-device', [DeviceController::class, 'destroy']);
        Route::get('/alerts-list', [DeviceController::class, 'alertsList']);
        Route::get('/assign-devices', [DeviceController::class, 'assignIndex']);
        Route::post('/assign-device', [DeviceController::class, 'assignDevice']);
        Route::get('/overview', [DeviceController::class, 'overview']);
        Route::get('/all-alerts', [AlertController::class, 'index']);

        //LiveView
        Route::get('/getDevices', [DeviceController::class, 'getDevices']);

        //tractor
        Route::post('/tractor-list', [TractorController::class, 'index']);
        Route::post('/create-tractor', [TractorController::class, 'store']);
        Route::post('/update-tractor', [TractorController::class, 'update']);
        Route::post('/delete-tractor', [TractorController::class, 'destroy']);
        // Route::post('/assign-group', [TractorController::class, 'assignGroup']);
        Route::get('/tractor-detail', [TractorController::class, 'show']);
        Route::get('/maintenance-tractor-list', [TractorController::class, 'maintenanceTractorList']);
        Route::get('/tractor-booking-list', [TractorController::class, 'bookingsList']);
        Route::get('/export-report', [TractorController::class, 'exportReport']);
        Route::get('/download-report', [TractorController::class, 'downloadReport']);
        Route::get('/assign-tractors', [TractorController::class, 'assignIndex']);
        Route::post('/assign-tractor', [TractorController::class, 'assignTractor']);

        //Booking
        Route::post('/create-tractor-booking', [BookingController::class, 'tractorBooking']);
        Route::post('/update-tractor-booking', [BookingController::class, 'updateTractorBooking']);
        Route::post('/delete-tractor-booking', [BookingController::class, 'deleteBooking']);
        Route::post('/tractor-booking-detail', [BookingController::class, 'show']);
        Route::post('/booking-list', [BookingController::class, 'index']);
        Route::get('/all-bookings', [BookingController::class, 'allBookings']);
        Route::post('/change-status', [BookingController::class, 'acceptReject']);
        Route::get('/accepted-bookings', [BookingController::class, 'acceptedBookings']);
        Route::get('/device-booking-list', [BookingController::class, 'deviceBookingList']);

        //Maintenance
        Route::get('/maintenance-list', [MaintenanceController::class, 'index']);
        Route::post('/create-maintenance', [MaintenanceController::class, 'store']);
        Route::post('/update-maintenance', [MaintenanceController::class, 'update']);
        Route::post('/update-conclusion', [MaintenanceController::class, 'updateConclusion']);
        Route::get('/maintenance-detail', [MaintenanceController::class, 'show']);
        Route::post('/delete-maintenance', [MaintenanceController::class, 'destroy']);
        Route::get('/change-maintenance-state', [MaintenanceController::class, 'changeStatus']);
        Route::get('/filter', [MaintenanceController::class, 'filter']);
        Route::get('/mainteance-data', [MaintenanceController::class, 'mainteanceData']);

        // Feedback
        Route::get('/feedback-list', [FarmerFeedbackController::class, 'index']);
        Route::post('/create-feedback', [FarmerFeedbackController::class, 'store']);
        Route::post('/update-feedback', [FarmerFeedbackController::class, 'update']);
        Route::get('/feedback-detail', [FarmerFeedbackController::class, 'show']);
        Route::post('/delete-feedback', [FarmerFeedbackController::class, 'destroy']);
        Route::get('/change-feedback-state', [FarmerFeedbackController::class, 'changeStatus']);
        Route::post('/conclusion', [FarmerFeedbackController::class, 'conclusion']);
        Route::get('/tractor-booking-list', [FarmerFeedbackController::class, 'tractorList']);

        //Issue Type
        Route::get('/issue-type-list', [IssueTypeController::class, 'index']);
        Route::post('/create-issue-type', [IssueTypeController::class, 'store']);
        Route::post('/update-issue-type', [IssueTypeController::class, 'update']);
        Route::get('/issue-type-detail', [IssueTypeController::class, 'show']);
        Route::post('/delete-issue-type', [IssueTypeController::class, 'destroy']);
        Route::get('/change-issue-type-state', [IssueTypeController::class, 'changeStatus']);

        //Device Geo Fence
        Route::get('/geo-fence-list', [DeviceGeoFenceController::class, 'index']);
        Route::get('/geo-fence-detail', [DeviceGeoFenceController::class, 'show']);
        Route::get('/geo-fence-imei-data', [DeviceGeoFenceController::class, 'detail']);

        //Jimi
        Route::get('/auth-token', [JimiController::class, 'authToken']);
        Route::post('/device-location', [JimiController::class, 'deviceLocation']);
        Route::post('/sharing-location-url', [JimiController::class, 'sharingLoation']);
        Route::post('/device-milage', [JimiController::class, 'deviceMilage']);
        Route::post('/device-track-data', [JimiController::class, 'deviceTrackData']);
        Route::post('/create-geo-fence', [JimiController::class, 'createGeoFence']);
        Route::post('/update-geo-fence', [JimiController::class, 'updateGeoFence']);
        Route::post('/delete-geo-fence', [JimiController::class, 'deleteGeoFence']);
        Route::get('/get-device-list', [JimiController::class, 'deviceList']);

        //Page
        Route::get('/page-list', [PageController::class, 'index']);
        Route::post('/create-page', [PageController::class, 'store']);
        Route::post('/update-page', [PageController::class, 'update']);
        Route::post('/delete-page', [PageController::class, 'destroy']);

        //Alert
        Route::post('/alert-list', [AlertController::class, 'index']);

        //Notification
        Route::get('/notification-list', [NotificationController::class, 'index']);
        Route::get('/unread-notifications', [NotificationController::class, 'unreadNotifications']);

        //FarmAsset
        Route::get('/asset-list', [FarmAssetController::class, 'index']);
        Route::post('/create-asset', [FarmAssetController::class, 'store']);
        Route::get('/asset-detail', [FarmAssetController::class, 'show']);
        Route::post('/update-asset', [FarmAssetController::class, 'update']);
        Route::post('/delete-asset', [FarmAssetController::class, 'destroy']);

        //Ticket Routes
        Route::get('/get-tickets', [TicketController::class, 'index']);
        Route::get('/ticket-detail', [TicketController::class, 'show']);
        Route::post('/create-ticket', [TicketController::class, 'store']);
        Route::post('/update-ticket', [TicketController::class, 'update']);
        Route::post('/delete-ticket', [TicketController::class, 'destroy']);
        Route::get('/update-state', [TicketController::class, 'changeState']);
    });
});
Route::get('/get-data', [JimiController::class, 'getData']);
