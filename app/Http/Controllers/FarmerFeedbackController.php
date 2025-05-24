<?php

namespace App\Http\Controllers;

use App\Jobs\ExportFeedback;
use App\Models\AssignedGroup;
use App\Models\Export;
use App\Models\FarmerFeedback;
use App\Models\Image;
use App\Models\TractorGroup;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class FarmerFeedbackController
 * @package App\Http\Controllers
 */
class FarmerFeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $farmerFeedbacks = FarmerFeedback::query();
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
            $tractorIds = multiDimToSingleDim($tractorIds);
            $farmerFeedbacks = $farmerFeedbacks->whereIn('tractor_id', $tractorIds);
        }
        $farmerFeedbacks =  $farmerFeedbacks->orderBy('state_id', 'ASC')->orderBy('id', 'DESC')->paginate();

        return view('farmer-feedback.index', compact('farmerFeedbacks'))
            ->with('i', (request()->input('page', 1) - 1) * $farmerFeedbacks->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $farmerFeedback = new FarmerFeedback();
        return view('farmer-feedback.create', compact('farmerFeedback'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(FarmerFeedback::$rules);

        $farmerFeedback = FarmerFeedback::create($request->all());

        return redirect()->route('farmer-feedbacks.index')
            ->with('success', 'FarmerFeedback created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $farmerFeedback = FarmerFeedback::findorFail($id);
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
            $tractorIds = multiDimToSingleDim($tractorIds);
            if (!in_array($farmerFeedback->tractor_id, $tractorIds)) {
                abort(403, 'You are not allowed to perform this action!!!');
            }
        }
        return view('farmer-feedback.show', compact('farmerFeedback'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $farmerFeedback = FarmerFeedback::findorFail($id);
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
            $tractorIds = multiDimToSingleDim($tractorIds);
            if (!in_array($farmerFeedback->tractor_id, $tractorIds)) {
                abort(403, 'You are not allowed to perform this action!!!');
            }
        }
        return view('farmer-feedback.edit', compact('farmerFeedback'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  FarmerFeedback $farmerFeedback
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FarmerFeedback $farmerFeedback)
    {
        request()->validate(FarmerFeedback::$rules);

        $farmerFeedback->update($request->all());

        return redirect()->route('farmer-feedbacks.show', $farmerFeedback->id)
            ->with('success', 'FarmerFeedback updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $farmerFeedback = FarmerFeedback::findorFail($id)->delete();

        return redirect()->route('farmer-feedbacks.index')
            ->with('success', 'FarmerFeedback deleted successfully');
    }

    public function changeStatus(Request $request)
    {
        $farmerFeedback = FarmerFeedback::findorFail($request->id);
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $tractorIds = $groups->pluck('tractor_ids')->flatten()->toArray();
            $tractorIds = multiDimToSingleDim($tractorIds);
            if (!in_array($farmerFeedback->tractor_id, $tractorIds)) {
                abort(403, 'You are not allowed to perform this action!!!');
            }
        }
        if ($farmerFeedback) {
            $farmerFeedback->state_id = $request->state_id;
            $farmerFeedback->save();
        }
        return redirect()->back();
    }

    public function exportFeedback()
    {
        if (!FarmerFeedback::count()) {
            return redirect()->route('farmer-feedbacks.index')->with('error', 'No data found.');
        }
        $fileName = 'reports_' . date('Ymdhis') . '.csv';
        Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_FEEDBACK])->latest('id')->delete();
        $export = Export::create([
            'file_name' => $fileName, 'type_id' => Export::TYPE_FEEDBACK
        ]);

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
        return redirect()->back()->with('success', 'Export added to queue. Please wait!');
    }

    public function download(Request $request)
    {
        try {

            if ($request->filename) {
                $originalFileName = $request->filename;
            } else {
                $export = Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_FEEDBACK])->latest('id')->first();
                $originalFileName = $export->file_name;
            }
            $filePath = storage_path('app/public/csv/' . $originalFileName);

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
            unlink($filePath);

            exit;
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function checkFile()
    {
        $response['status'] = 'NOK';
        $export = Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_FEEDBACK])->latest('id')->first();
        if ($export) {
            $fileName = $export->file_name;
            $filePath = storage_path('app/public/csv/' . $fileName);
            if (file_exists($filePath)) {
                $response['status'] = 'OK';
            }
        }
        return $response;
    }
}
