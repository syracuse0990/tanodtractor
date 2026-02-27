<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceGeoFenceController;
use App\Http\Controllers\FarmerFeedbackController;
use App\Http\Controllers\IssueTypeController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SlotController;
use App\Http\Controllers\TractorBookingController;
use App\Http\Controllers\TractorController;
use App\Http\Controllers\TractorGroupController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AutoReportController;
use App\Http\Controllers\FarmAssetController;
use App\Http\Controllers\LiveviewController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketController;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::redirect('/', '/login');



Route::get('/update-profile', function () {
    return view('profile.profile-update');
});

Route::post('webhook', [DeviceController::class, 'geoFenceWebhook'])->name('devices.webhook');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'check_admin'
])->group(function () {
    Route::get('/sub-admin', [UserController::class, 'subAdmin'])->name('users.subAdmin');
    Route::get('/technicians', [UserController::class, 'technicians'])->name('users.technicians');
    Route::get('/assign-users', [UserController::class, 'assignIndex'])->name('users.assignIndex');
    Route::get('/assign-user', [UserController::class, 'assignUser'])->name('users.assignUser');
    Route::get('/assign-groups', [TractorGroupController::class, 'assignIndex'])->name('tractor-groups.assignIndex');
    Route::get('/assign-group', [TractorGroupController::class, 'assignGroup'])->name('tractor-groups.assignGroup');
    Route::get('sync-devices', [DeviceController::class, 'syncDevices'])->name('devices.sync-devices');
    Route::get('/assign-devices', [DeviceController::class, 'assignIndex'])->name('devices.assignIndex');
    Route::get('/assign-device', [DeviceController::class, 'assignDevice'])->name('devices.assignDevice');
    Route::get('/change-type-status', [IssueTypeController::class, 'changeStatus'])->name('issue-types.change-type-status');
    Route::resource('issue-types', IssueTypeController::class);
    Route::get('/assign-tractors', [TractorController::class, 'assignIndex'])->name('tractors.assignIndex');
    Route::get('/assign-tractor', [TractorController::class, 'assignTractor'])->name('tractors.assignTractor');
    Route::get('/tagging', [TractorController::class, 'tagging'])->name('tractors.tagging');
    Route::post('/tag-unit', [TractorController::class, 'tagUnit'])->name('tractors.tagUnit');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('getDeviceDetail', [AdminController::class, 'getDeviceDetail'])->name('admins.getDeviceDetail');

    //Settings
    Route::get('/operational-settings', function () {
        return view('operation.index');
    })->name('settings');
    Route::get('/migration-operation', [OperationController::class, 'runMigrations'])->name('settings.runMigration');
    Route::get('/composer-install-operation', [OperationController::class, 'composerInstall'])->name('settings.composerInstall');

    Route::get('/dashboard', function () {
        return view('dashboard.new-dashboard');
    })->name('dashboard');
    Route::get('/profile', function () {
        $user = Auth::user();
        return view('user.show', compact('user'));
    });
    Route::get('/password-update', function () {
        return view('components.change-password');
    });
    //Report Routes
    Route::get('download-report', [ReportController::class, 'download'])->name('reports.download');
    Route::get('check-report', [ReportController::class, 'checkFile'])->name('reports.check-file');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/maintenance-reports', [ReportController::class, 'maintenanceReports'])->name('reports.maintenanceReports');
    Route::get('/maintenance-reports/export-excel', [ReportController::class, 'exportMaintenanceExcel'])->name('reports.exportMaintenanceExcel');
    Route::get('/api-documentation', [ReportController::class, 'apiDocumentation'])->name('reports.apiDocumentation');

    Route::get('/device-reports', [ReportController::class, 'deviceReports'])->name('reports.deviceReports');

    //Device Routes
    Route::get('export-device', [DeviceController::class, 'export'])->name('devices.export-device');
    Route::get('exportOverview', [DeviceController::class, 'exportOverview'])->name('devices.exportOverview');
    Route::get('download-export-device', [DeviceController::class, 'download'])->name('devices.download-export-device');
    Route::get('/check-device-file', [DeviceController::class, 'checkFile'])->name('devices.check-device-file');
    Route::get('/overview', [DeviceController::class, 'overview'])->name('devices.overview');
    Route::resource('devices', DeviceController::class);

    //Tractor Routes
    // Route::get('/assign-group', [TractorController::class, 'assignGroup'])->name('tractors.assign-group');
    Route::get('reassign', [TractorController::class, 'reassign'])->name('tractors.reassign');
    Route::get('live-view', [TractorController::class, 'liveView'])->name('tractors.live-view');
    Route::post('ajax-live-view', [TractorController::class, 'ajaxLiveView'])->name('tractors.ajax-live-view');
    Route::post('current-device-data', [TractorController::class, 'currentDeviceData'])->name('tractors.current-device-data');
    Route::post('history-data', [TractorController::class, 'historyData'])->name('tractors.history-data');
    Route::post('booking-data', [TractorController::class, 'bookingData'])->name('tractors.booking-data');
    Route::get('export-report', [TractorController::class, 'export'])->name('tractors.export');
    Route::get('download', [TractorController::class, 'download'])->name('tractors.download');
    Route::get('check-file', [TractorController::class, 'checkFile'])->name('tractors.check-file');
    Route::post('getTractData', [TractorController::class, 'getTractData'])->name('tractors.getTractData');
    Route::post('/jimiData', [TractorController::class, 'jimiData'])->name('tractors.jimiData');
    Route::post('/tractors/import', [TractorController::class, 'import'])->name('tractors.import');
    Route::post('/tractors/closeProgress', [TractorController::class, 'closeProgress'])->name('tractors.closeProgress');
    Route::get('/tractors/ImportStatus', [TractorController::class, 'ImportStatus'])->name('tractors.ImportStatus');
    Route::get('/tractors-import-format', [TractorController::class, 'getFormat'])->name('tractors.getFormat');
    Route::resource('tractors', TractorController::class);
    //
    Route::resource('slots', SlotController::class);

    //Liveview Routes
    Route::get('appendGroupDevices', [LiveviewController::class, 'appendGroupDevices'])->name('liveview.appendGroupDevices');
    Route::get('markersData', [LiveviewController::class, 'markersData'])->name('liveview.markersData');
    Route::get('currentDevice', [LiveviewController::class, 'currentDevice'])->name('liveview.currentDevice');
    Route::get('getTrackData', [LiveviewController::class, 'getTrackData'])->name('liveview.getTrackData');
    Route::get('liveview/serach', [LiveviewController::class, 'search'])->name('liveview.search');
    Route::get('getDeviceWithState', [LiveviewController::class, 'getDeviceWithState'])->name('liveview.getDeviceWithState');
    Route::get('getDevicesCount', [LiveviewController::class, 'getDevicesCount'])->name('liveview.getDevicesCount');
    Route::get('dashboardStats', [LiveviewController::class, 'dashboardStats'])->name('liveview.dashboardStats');
    Route::get('dashboardMarkersData', [LiveviewController::class, 'dashboardMarkersData'])->name('liveview.dashboardMarkersData');
    Route::get('dashboardGetDevicesCount', [LiveviewController::class, 'dashboardGetDevicesCount'])->name('liveview.dashboardGetDevicesCount');
    Route::get('dashboardAppendGroupDevices', [LiveviewController::class, 'dashboardAppendGroupDevices'])->name('liveview.dashboardAppendGroupDevices');
    Route::get('getFilteredDevices', [LiveviewController::class, 'getFilteredDevices'])->name('liveview.getFilteredDevices');
    Route::post('updateGroup', [LiveviewController::class, 'updateGroup'])->name('liveview.updateGroup');
    Route::resource('liveview', LiveviewController::class);

    //Booking Routes
    Route::get('tractor-bookings/change-status', [TractorBookingController::class, 'changeStatus'])->name('tractor-bookings.change-status');
    Route::get('tractor-bookings/booking-list', [TractorBookingController::class, 'bookingList'])->name('tractor-bookings.booking-list');
    Route::resource('tractor-bookings', TractorBookingController::class);

    //Maintenance Rotues
    Route::get('/state', [MaintenanceController::class, 'changeState'])->name('maintenances.state');
    Route::post('/maintenances/import', [MaintenanceController::class, 'import'])->name('maintenances.import');
    Route::post('/maintenances/closeProgress', [MaintenanceController::class, 'closeProgress'])->name('maintenances.closeProgress');
    Route::get('/maintenances/ImportStatus', [MaintenanceController::class, 'ImportStatus'])->name('maintenances.ImportStatus');
    Route::get('/maintenance-import-format', [MaintenanceController::class, 'getFormat'])->name('maintenances.getFormat');
    Route::resource('maintenances', MaintenanceController::class);

    //Farmer Feedback Routes
    Route::get('/change-status', [FarmerFeedbackController::class, 'changeStatus'])->name('farmer-feedbacks.change-status');
    Route::get('/export-feedback', [FarmerFeedbackController::class, 'exportFeedback'])->name('farmer-feedbacks.export-feedback');
    Route::get('/download-feedback', [FarmerFeedbackController::class, 'download'])->name('farmer-feedbacks.download-feedback');
    Route::get('/check-feedback-file', [FarmerFeedbackController::class, 'checkFile'])->name('farmer-feedbacks.check-feedback-file');
    Route::resource('farmer-feedbacks', FarmerFeedbackController::class);

    //Geo fence routes
    Route::post('device-data', [DeviceGeoFenceController::class, 'deviceData'])->name('device-geo-fences.device-data');
    Route::resource('device-geo-fences', DeviceGeoFenceController::class);

    //Notification routes
    Route::post('/notification-data', [NotificationController::class, 'notificationData'])->name('notifications.notification-data');
    Route::post('/notification-alert', [NotificationController::class, 'alert'])->name('notifications.notification-alert');
    Route::post('/close-alert', [NotificationController::class, 'closeAlert'])->name('notifications.close-alert');
    Route::post('/maintenance-notification', [NotificationController::class, 'maintenanceNotification'])->name('notifications.maintenance-notification');
    Route::post('/closeAllAlerts', [NotificationController::class, 'closeAllAlerts'])->name('notifications.closeAllAlerts');
    Route::get('/notifications/socket', [NotificationController::class, 'sse']);
    Route::get('/mark-all-as-read', function () {
        Notification::where('user_id', Auth::id())->update(['is_read' => Notification::IS_READ]);
        return redirect()->back()->with('success', 'All Notification Marked as Read.'); // Redirect to the previous page
    })->name('notifications.allRead');
    Route::resource('notifications', NotificationController::class);

    //Page Routes
    Route::resource('pages', PageController::class);

    //Alert Routes
    Route::get('alerts/current-month-stats', [AlertController::class, 'currentMonthStats'])->name('alerts.current-month-stats');
    Route::resource('alerts', AlertController::class);

    //Group Routes
    Route::post('/search-group', [TractorGroupController::class, 'search'])->name('tractor-groups.search-group');
    Route::resource('tractor-groups', TractorGroupController::class);

    //User Routes
    Route::post('/update-password', [UserController::class, 'updatePassword'])->name('update-password');
    Route::post('/update-image', [UserController::class, 'updateImage'])->name('users.update-image');
    Route::get('/export-farmers', [UserController::class, 'exportFarmer'])->name('users.export-farmers');
    Route::get('/download-csv', [UserController::class, 'download'])->name('users.download-farmers');
    Route::get('/check-farmers-file', [UserController::class, 'checkFile'])->name('users.check-farmers-file');
    Route::post('/farmers/import', [UserController::class, 'import'])->name('users.import');
    Route::get('/get-format', [UserController::class, 'getFormat'])->name('users.getFormat');
    Route::get('/remove-duplicacy', [UserController::class, 'removeDuplicateUsers']);
    Route::resource('users', UserController::class);

    //Farm Asset Routes
    Route::post('/farm-assets/import', [FarmAssetController::class, 'import'])->name('farm-assets.import');
    Route::post('/farm-assets/closeProgress', [FarmAssetController::class, 'closeProgress'])->name('farm-assets.closeProgress');
    Route::get('/farm-assets/ImportStatus', [FarmAssetController::class, 'ImportStatus'])->name('farm-assets.ImportStatus');
    Route::get('/assets-import-format', [FarmAssetController::class, 'getFormat'])->name('farm-assets.getFormat');
    Route::resource('farm-assets', FarmAssetController::class);

    //Ticket Routes
    Route::get('/ticket-update-state', [TicketController::class, 'changeState'])->name('tickets.changeState');
    Route::resource('tickets', TicketController::class);

    Route::post('/push', [PushController::class, 'store']);

    Route::resource('auto-reports', AutoReportController::class);

    Route::post('/tractors/new-import', [TractorController::class, 'newImport'])->name('tractors.newImport');
});
