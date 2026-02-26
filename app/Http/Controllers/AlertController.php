<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Alert;
use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\TractorGroup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $alerts = Alert::query();
        if ($request->alarm_type) {
            $alerts = $alerts->where('alarm_type', $request->alarm_type);
        }
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $deviceIds = [];
            $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
            $deviceIds = multiDimToSingleDim($deviceIds);
            $imeis = Device::whereIn('id', $deviceIds)->pluck('imei_no')->toArray();
            $alerts = $alerts->whereIn('imei', $imeis);
        }
        $alerts = $alerts->latest('id')->paginate(20);
        return view('alerts.index', compact('alerts'))
            ->with('i', (request()->input('page', 1) - 1) * $alerts->perPage());
    }

    /**
     * Return alerts statistics for current month.
     */
    public function currentMonthStats(Request $request)
    {
        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay();
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $groupId = $request->get('group_id');
        $isDefaultCurrentMonth = true;

        // Default to current month; use selected date range when provided.
        if (!empty($startDate)) {
            $startOfMonth = Carbon::parse($startDate)->startOfDay();
            $isDefaultCurrentMonth = false;
        }
        if (!empty($endDate)) {
            $endOfMonth = Carbon::parse($endDate)->endOfDay();
            $isDefaultCurrentMonth = false;
        }

        $alerts = Alert::query();
        if ($request->alarm_type) {
            $alerts->where('alarm_type', $request->alarm_type);
        }

        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
            $deviceIds = multiDimToSingleDim($deviceIds);
            $imeis = Device::whereIn('id', $deviceIds)->pluck('imei_no')->toArray();
            $alerts->whereIn('imei', $imeis);
        }

        if (!empty($groupId)) {
            $selectedGroup = TractorGroup::find($groupId);
            $groupDeviceIds = $selectedGroup?->device_ids ?? [];

            if (!empty($groupDeviceIds)) {
                $groupImeis = Device::whereIn('id', $groupDeviceIds)->pluck('imei_no')->toArray();
                if (!empty($groupImeis)) {
                    $alerts->whereIn('imei', $groupImeis);
                } else {
                    $alerts->whereRaw('1 = 0');
                }
            } else {
                $alerts->whereRaw('1 = 0');
            }
        }

        $rows = $alerts
            ->whereBetween('alarm_time', [$startOfMonth, $endOfMonth])
            ->selectRaw('alarm_type, COUNT(*) as total')
            ->groupBy('alarm_type')
            ->orderBy('alarm_type')
            ->get();

        $alertTypes = Alert::alertOptions();
        $labels = [];
        $counts = [];

        foreach ($rows as $type => $row) {
            $alarmType = (int) $row->alarm_type;
            $labels[] = $alertTypes[$alarmType] ?? ('Type ' . $alarmType);
            $counts[] = (int) $row->total;
        }

        $periodLabel = $isDefaultCurrentMonth
            ? $startOfMonth->format('F Y')
            : $startOfMonth->format('M d, Y') . ' - ' . $endOfMonth->format('M d, Y');

        return response()->json([
            'labels' => $labels,
            'counts' => $counts,
            'period_label' => $periodLabel,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $alert = Alert::findorFail($id);
        return view('alerts.show', compact('alert'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
