<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Alert;
use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\TractorGroup;
use App\Models\User;
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
