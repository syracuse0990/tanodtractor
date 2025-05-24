<?php

namespace App\Http\Controllers;

use App\Jobs\ImportMaintenances;
use App\Models\AssignedGroup;
use App\Models\Export;
use App\Models\Maintenance;
use App\Models\Tractor;
use App\Models\TractorGroup;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class MaintenanceController
 * @package App\Http\Controllers
 */
class MaintenanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $maintenances = Maintenance::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $maintenances = $maintenances->where('created_by', Auth::id());
        }
        $maintenances = $maintenances->latest()->paginate();
        $importInfo = Export::where([
            'created_by' => Auth::id(),
            'type_id' => Export::TYPE_MAINTENANCE_IMPORT
        ])->first();

        return view('maintenance.index', compact('maintenances', 'importInfo'))
            ->with('i', (request()->input('page', 1) - 1) * $maintenances->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $maintenance = new Maintenance();
        $tractors = Tractor::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
            $tractorIds = multiDimToSingleDim($tractorIds);
            $tractors = $tractors->whereIn('id', $tractorIds);
        }
        $tractors = $tractors->latest('id')->get();
        return view('maintenance.create', compact('maintenance', 'tractors'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $formFields = request()->validate(
            [
                'tractor_ids' => 'required',
                'maintenance_date' => 'required',
                'tech_name' => 'required',
                'tech_email' => 'required|email',
                'tech_number' => 'required|numeric|digits_between:10,16'

            ],
            [
                'tech_email.email' => 'The Email field must be a valid email address.',
                'farmer_email.email' => 'The Email field must be a valid email address.',
                'tech_number.max' => 'The technician number field must not be greater than 10 characters.',
                'farmer_number.max' => 'The technician number field must not be greater than 10 characters.'

            ],
        );

        $maintenanceData = $request->all();
        $maintenance = Maintenance::create($maintenanceData);

        return redirect()->route('maintenances.index')
            ->with('success', 'Maintenance created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $maintenance = Maintenance::findorFail($id);
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]) && $maintenance->created_by != Auth::id()) {
            abort(403, 'You are not allowed to perform this action!!!');
        }
        $tractor = $maintenance->tractor;

        return view('maintenance.show', compact('maintenance', 'tractor'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request)
    {
        $maintenance = Maintenance::findorFail($id);
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]) && $maintenance->created_by != Auth::id()) {
            abort(403, 'You are not allowed to perform this action!!!');
        }
        $tractors = Tractor::latest()->get();
        $tractors = Tractor::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
            $tractorIds = multiDimToSingleDim($tractorIds);
            $tractors = $tractors->whereIn('id', $tractorIds);
        }
        $tractors = $tractors->latest()->get();
        if ($request->conclusion) {
            return view('maintenance.conclusion_form', compact('maintenance'));
        } else {
            return view('maintenance.edit', compact('maintenance', 'tractors'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Maintenance $maintenance
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Maintenance $maintenance)
    {
        if ($request->conclusion) {
            $formFields = request()->validate(
                [
                    'conclusion' => 'required',

                ]
            );
        } else {
            $formFields = request()->validate(
                [
                    'tractor_ids' => 'required',
                    'maintenance_date' => 'required',
                    'tech_name' => 'required',
                    'tech_email' => 'required|email',
                    'tech_number' => 'required|numeric|digits_between:10,16'
                ],
                [
                    'tech_email.email' => 'The Email field must be a valid email address.',
                    'tech_email.unique' => 'The Email already exists.',
                    'farmer_email.email' => 'The Email field must be a valid email address.',
                    'farmer_email.unique' => 'The Email already exists.',

                ],
            );
        }
        $maintenanceData = $request->all();
        $maintenance->update($maintenanceData);
        return redirect()->route('maintenances.show', $maintenance->id)
            ->with('success', 'Maintenance updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $maintenance = Maintenance::findorFail($id)->delete();

        return redirect()->route('maintenances.index')
            ->with('success', 'Maintenance deleted successfully');
    }

    public function changeState(Request $request)
    {
        $maintenance = Maintenance::findorFail($request->id);
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN]) && $maintenance->created_by != Auth::id()) {
            abort(403, 'You are not allowed to perform this action!!!');
        }
        if ($maintenance) {
            if ($request->state_id == Maintenance::STATE_COMPLETED) {
                $tractor  = $maintenance->tractor;
                if ($tractor) {
                    $tractor->running_km = 0;
                    $tractor->save();
                }
            }
            if (in_array($request->state_id, [Maintenance::STATE_COMPLETED, Maintenance::STATE_CANCELLED]) && empty($maintenance->conclusion)) {
                return redirect()->route('maintenances.show', $maintenance->id)
                    ->with('error', 'Please add conclusion to change maintenance state to ' . $maintenance->getStateName($request->state_id) . '.');
            }
            $maintenance->state_id = $request->state_id;
            $maintenance->save();
        }
        return redirect()->back();
    }

    public function import(Request $request)
    {
        $rules = [
            'fileInput' => 'required|mimes:csv,txt'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('fileInput');
            $extension = $file->getClientOriginalExtension();
            $fileName = uniqid('file_', true) . '.' . $extension;
            $filePath = $file->storeAs('import', $fileName, 'public');

            Export::where(['created_by' => Auth::id(), 'type_id' => Export::TYPE_MAINTENANCE_IMPORT])->latest('id')->delete();
            $export = Export::create([
                'file_name' => $file->getClientOriginalName(),
                'type_id' => Export::TYPE_MAINTENANCE_IMPORT,
            ]);

            ImportMaintenances::dispatch($filePath, Auth::id(), $export->id);
            return redirect()->back()->with('success', 'Import request has been added to the queue. Please check back shortly.');
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function closeProgress(Request $request)
    {
        try {
            $response['status'] = 'NOK';
            $export = Export::where(['created_by' => Auth::id(), 'type_id' => $request->type])->latest('id')->delete();
            if ($export) {
                $response['status'] = 'OK';
            }
            return $response;
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function ImportStatus(Request $request)
    {
        try {
            $response['status'] = 'NOK';
            $exportInfo = Export::where(['created_by' => Auth::id(), 'type_id' => $request->type])->latest('id')->first();
            if ($exportInfo) {
                $response['status'] = 'OK';
                $response['progress'] = $exportInfo->progress ?? 0;
            }
            return $response;
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }


    public function getFormat()
    {
        try {
            $filename = 'maintenance_import_format.csv';
            $filePath = public_path('/assets/format/' . $filename);
            if (!file_exists($filePath)) {
                return redirect()->back()->with('error', 'File not found.');
            }
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($filePath));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            // Delete the file after download

            exit;
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
