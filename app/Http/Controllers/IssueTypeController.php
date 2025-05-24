<?php

namespace App\Http\Controllers;

use App\Models\IssueType;
use Illuminate\Http\Request;

/**
 * Class IssueTypeController
 * @package App\Http\Controllers
 */
class IssueTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $issueTypes = IssueType::latest('id')->paginate();

        return view('issue-type.index', compact('issueTypes'))
            ->with('i', (request()->input('page', 1) - 1) * $issueTypes->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $issueType = new IssueType();
        $issueType->state_id = IssueType::STATE_ACTIVE;
        return view('issue-type.create', compact('issueType'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(IssueType::$rules);

        $issueType = IssueType::create($request->all());

        return redirect()->route('issue-types.index')
            ->with('success', 'IssueType created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $issueType = IssueType::findorFail($id);

        return view('issue-type.show', compact('issueType'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $issueType = IssueType::findorFail($id);

        return view('issue-type.edit', compact('issueType'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  IssueType $issueType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, IssueType $issueType)
    {
        request()->validate(IssueType::$rules);

        $issueType->update($request->all());

        return redirect()->route('issue-types.show', $issueType->id)
            ->with('success', 'IssueType updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $issueType = IssueType::findorFail($id)->delete();

        return redirect()->route('issue-types.index')
            ->with('success', 'IssueType deleted successfully');
    }

    public function changeStatus(Request $request)
    {
        $issueType = IssueType::findorFail($request->id);
        if ($issueType) {
            $issueType->state_id = $request->state_id;
            $issueType->save();
        }
        return redirect()->back();
    }
}
