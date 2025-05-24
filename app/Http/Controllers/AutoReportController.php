<?php

namespace App\Http\Controllers;

use App\Models\AssignedGroup;
use App\Models\AutoReport;
use App\Models\Device;
use App\Models\TractorGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class AutoReportController
 * @package App\Http\Controllers
 */
class AutoReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $autoReports = AutoReport::paginate();

        return view('auto-report.index', compact('autoReports'))
            ->with('i', (request()->input('page', 1) - 1) * $autoReports->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $autoReport = new AutoReport();

        $deviceList = Device::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
            $deviceIds = multiDimToSingleDim($deviceIds);
            $deviceList = $deviceList->whereIn('id', $deviceIds);
        }
        $deviceList = $deviceList->latest('id')->get();
        return view('auto-report.create', compact('autoReport', 'deviceList'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $data['device_ids'] = implode(',', $request->device_ids);
            $autoReport = AutoReport::create($data);
            session()->flash('success', 'AutoReport created successfully');
            return response()->json(['success' => true, 'url' => route('auto-reports.index')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error :' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $autoReport = AutoReport::find($id);

        return view('auto-report.show', compact('autoReport'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $autoReport = AutoReport::find($id);
        $deviceList = Device::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
            $deviceIds = multiDimToSingleDim($deviceIds);
            $deviceList = $deviceList->whereIn('id', $deviceIds);
        }
        $deviceList = $deviceList->latest('id')->get();
        return view('auto-report.edit', compact('autoReport', 'deviceList'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  AutoReport $autoReport
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AutoReport $autoReport)
    {
        try {
            $data = $request->all();
            $data['device_ids'] = implode(',', $request->device_ids);
            $autoReport->update($data);
            session()->flash('success', 'AutoReport updated successfully');
            return response()->json(['success' => true, 'url' => route('auto-reports.index')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error : ' . $e->getMessage()], 500);
        }
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $autoReport = AutoReport::find($id)->delete();

        return redirect()->route('auto-reports.index')
            ->with('success', 'AutoReport deleted successfully');
    }
}
