<?php

namespace App\Http\Controllers;

use App\Models\AssignedGroup;
use Illuminate\Http\Request;

/**
 * Class AssignedGroupController
 * @package App\Http\Controllers
 */
class AssignedGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $assignedGroups = AssignedGroup::paginate();

        return view('assigned-group.index', compact('assignedGroups'))
            ->with('i', (request()->input('page', 1) - 1) * $assignedGroups->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $assignedGroup = new AssignedGroup();
        return view('assigned-group.create', compact('assignedGroup'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(AssignedGroup::$rules);

        $assignedGroup = AssignedGroup::create($request->all());

        return redirect()->route('assigned-groups.index')
            ->with('success', 'AssignedGroup created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $assignedGroup = AssignedGroup::findorFail($id);

        return view('assigned-group.show', compact('assignedGroup'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $assignedGroup = AssignedGroup::findorFail($id);

        return view('assigned-group.edit', compact('assignedGroup'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  AssignedGroup $assignedGroup
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AssignedGroup $assignedGroup)
    {
        request()->validate(AssignedGroup::$rules);

        $assignedGroup->update($request->all());

        return redirect()->route('assigned-groups.index')
            ->with('success', 'AssignedGroup updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $assignedGroup = AssignedGroup::findorFail($id)->delete();

        return redirect()->route('assigned-groups.index')
            ->with('success', 'AssignedGroup deleted successfully');
    }
}
