<?php

namespace App\Http\Controllers;

use App\Models\AssignedDevice;
use App\Models\AssignedGroup;
use App\Models\AssignedTractor;
use App\Models\Device;
use App\Models\Tractor;
use App\Models\TractorBooking;
use App\Models\TractorGroup;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class TractorGroupController
 * @package App\Http\Controllers
 */
class TractorGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $tractorGroups = TractorGroup::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $tractorGroups = $tractorGroups->whereIn('id', $assignedGroups);
        }
        $search = null;
        if ($request->search) {
            $search = $request->search;
            $tractorGroups = $tractorGroups->where('name', 'LIKE', '%' . $request->search . '%')->latest('id')->paginate();
        } else {
            $tractorGroups = $tractorGroups->latest('id')->paginate();
        }

        return view('tractor-group.index', compact('tractorGroups', 'search'))
            ->with('i', (request()->input('page', 1) - 1) * $tractorGroups->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            abort(403, 'You are not allowed to perform this action!!');
        }
        $tractorGroup = new TractorGroup();
        $tractorGroup->state_id = TractorGroup::STATE_ACTIVE;
        $farmerData = TractorGroup::pluck('farmer_ids')->toArray();
        $tractorData = TractorGroup::pluck('tractor_ids')->toArray();
        $deviceData = TractorGroup::pluck('device_ids')->toArray();
        $farmer_ids = multiDimToSingleDim($farmerData);
        $tractor_ids = multiDimToSingleDim($tractorData);
        $device_ids = multiDimToSingleDim($deviceData);

        $farmers = User::where(['role_id' => User::ROLE_FARMER])->whereNotIn('id', $farmer_ids)->get();
        $tractors = Tractor::whereNotIn('id', $tractor_ids)->select('id', 'id_no', 'brand', 'model', 'no_plate')->get();
        $devices = Device::whereNotIn('id', $device_ids)->select('id', 'imei_no', 'device_name', 'device_modal')->get();

        return view('tractor-group.create', compact('tractorGroup', 'farmers', 'tractors', 'devices'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        request()->validate([
            'name' => 'required|unique:tractor_groups,name',
            'tractor_ids' => 'required',
            'farmer_ids' => 'required',
            'device_ids' => 'required',
        ]);
        $tractorGroupData = $request->all();
        if (is_array($request->farmer_ids)) {
            $tractorGroupData['farmer_ids'] = json_encode($request->farmer_ids);
        }
        if (is_array($request->tractor_ids)) {
            $tractorGroupData['tractor_ids'] = json_encode($request->tractor_ids);
        }
        if (is_array($request->device_ids)) {
            $tractorGroupData['device_ids'] = json_encode($request->device_ids);
        }
        $tractorGroup = TractorGroup::create($tractorGroupData);

        return redirect()->route('tractor-groups.index')
            ->with('success', 'TractorGroup created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $search = null;
        $tractorGroup = TractorGroup::findorFail($id);
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            if (!in_array($tractorGroup->id, $assignedGroups)) {
                abort(403, 'You are not allowed to perform this action!!!');
            }
        }
        $tractors = Tractor::whereIn('id', ($tractorGroup->tractor_ids ? json_decode($tractorGroup->tractor_ids) : []))->get();
        $farmers = User::whereIn('id', ($tractorGroup->farmer_ids ? json_decode($tractorGroup->farmer_ids) : []))->get();
        $devices = Device::whereIn('id', ($tractorGroup->device_ids ? json_decode($tractorGroup->device_ids) : []))->get();
        $bookings = TractorBooking::whereIn('tractor_id', ($tractorGroup->tractor_ids ? json_decode($tractorGroup->tractor_ids, true) : []))->orWhereIn('device_id', ($tractorGroup->device_ids ? json_decode($tractorGroup->device_ids, true) : []))->whereNotIn('state_id', [TractorBooking::STATE_DELETED])->orderBy('state_id', 'ASC')->orderBy('id', 'DESC');
        if ($request->search) {
            $search = $request->search;
            $bookings = $bookings->whereHas('tractor', function (Builder $query) use ($request) {
                return $query->where('id_no', 'like', '%' . $request->search . '%')->orWhere('model', 'like', '%' . $request->search . '%');
            })->get();
        } else {
            $bookings = $bookings->get();
        }

        $assignedGroup = AssignedGroup::where('group_id', $tractorGroup->id)->first();
        return view('tractor-group.show', compact('tractorGroup', 'tractors', 'farmers', 'devices', 'bookings', 'search', 'assignedGroup'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            abort(403, 'You are not allowed to perform this action!!');
        }
        $tractorGroup = TractorGroup::findorFail($id);

        $farmerData = TractorGroup::where('id', '!=', $tractorGroup->id)->pluck('farmer_ids')->toArray();
        $tractorData = TractorGroup::where('id', '!=', $tractorGroup->id)->pluck('tractor_ids')->toArray();
        $deviceData = TractorGroup::where('id', '!=', $tractorGroup->id)->pluck('device_ids')->toArray();
        $farmer_ids = multiDimToSingleDim($farmerData);
        $tractor_ids = multiDimToSingleDim($tractorData);
        $device_ids = multiDimToSingleDim($deviceData);


        $farmers = User::where(['role_id' => User::ROLE_FARMER])->whereNotIn('id', $farmer_ids)->get();
        $tractors = Tractor::whereNotIn('id', $tractor_ids)->select('id', 'id_no', 'brand', 'model', 'no_plate')->get();
        $devices = Device::whereNotIn('id', $device_ids)->select('id', 'imei_no', 'device_name', 'device_modal')->get();

        return view('tractor-group.edit', compact('tractorGroup', 'farmers', 'tractors', 'devices'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  TractorGroup $tractorGroup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TractorGroup $tractorGroup)
    {
        request()->validate([
            'name' => 'required|unique:tractor_groups,name,' . $tractorGroup->id,
            'tractor_ids' => 'required',
            'farmer_ids' => 'required',
            'device_ids' => 'required',
        ]);

        $tractorGroupData = $request->all();
        if (is_array($request->farmer_ids)) {
            $tractorGroupData['farmer_ids'] = json_encode($request->farmer_ids);
        }
        if (is_array($request->tractor_ids)) {
            $tractorGroupData['tractor_ids'] = json_encode($request->tractor_ids);
        }
        if (is_array($request->device_ids)) {
            $tractorGroupData['device_ids'] = json_encode($request->device_ids);
        }
        $tractorGroup->update($tractorGroupData);

        return redirect()->route('tractor-groups.index')
            ->with('success', 'TractorGroup updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $tractorGroup = TractorGroup::findorFail($id)->delete();

        return redirect()->route('tractor-groups.index')
            ->with('success', 'TractorGroup deleted successfully');
    }

    public function search(Request $request)
    {
        $search = null;
        $response['status'] = 'OK';
        if ($request->search) {
            $search = $request->search;
            $device = Device::query();
            if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
                $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
                $deviceIds = $groups->pluck('device_ids')->flatten()->toArray();
                $deviceIds = multiDimToSingleDim($deviceIds);
                $device = $device->whereIn('id', $deviceIds);
            }
            $device = $device->where('imei_no',  'LIKE', '%' . $search . '%')->get();
            $response['device'] = $device;
        }
        return $response;
    }

    public function assignIndex(Request $request)
    {
        $userId = $request->id;
        $assignedIds = AssignedGroup::where('user_id', '!=', $userId)->pluck('group_id')->toArray();
        $groups = TractorGroup::whereNotIn('id', $assignedIds)->latest('id');

        $search =  null;
        if ($request->search) {
            $search = $request->search;
            $groups = $groups->where('name', 'LIKE', '%' . $request->search . '%')->paginate();
        } else {
            $groups =  $groups->paginate();
        }

        $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();

        return view('tractor-group.assignIndex', compact('groups', 'userId', 'search', 'assignedGroups'))
            ->with('i', (request()->input('page', 1) - 1) * $groups->perPage());
    }

    public function assignGroup(Request $request)
    {
        if ($request->state == '1') {
            $assignedGroup = AssignedGroup::create([
                'user_id' => $request->user_id,
                'group_id' => $request->id,
            ]);
            // $group = TractorGroup::findorFail($request->id);
            // $tractorIds = json_decode($group?->tractor_ids, true);
            // $deviceIds = json_decode($group?->device_ids, true);
            // foreach($tractorIds as $tractor_id){
            //     $assignedTractor = AssignedTractor::create([
            //         'user_id' => $assignedGroup->user_id,
            //         'tractor_id' => $tractor_id,
            //         'group_id' => $request->id
            //     ]);
            // }
            // foreach($deviceIds as $device_id){
            //     $assignedDevice = AssignedDevice::create([
            //         'user_id' => $assignedGroup->user_id,
            //         'device_id' => $device_id,
            //         'group_id' => $request->id
            //     ]);
            // }
        } else {
            $assignedGroup = AssignedGroup::where(['group_id' => $request->id, 'user_id' => $request->user_id])->delete();
            // $assignedDevice = AssignedDevice::where(['group_id' => $request->id, 'user_id' => $request->user_id])->delete();
            // $assignedTractor = AssignedTractor::where(['group_id' => $request->id, 'user_id' => $request->user_id])->delete();
        }
        return redirect()->back();
    }
}
