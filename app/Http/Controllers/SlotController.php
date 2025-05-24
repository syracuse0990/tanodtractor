<?php

namespace App\Http\Controllers;

use App\Models\Slot;
use App\Models\Tractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Class SlotController
 * @package App\Http\Controllers
 */
class SlotController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $slots = Slot::paginate();

        return view('slot.index', compact('slots'))
            ->with('i', (request()->input('page', 1) - 1) * $slots->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $slot = new Slot();
        $slot->state_id = Slot::STATE_ACTIVE;
        $tractors = Tractor::select('id', 'id_no', 'brand', 'model')->get();

        return view('slot.create', compact('slot', 'tractors'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), Slot::$rules);
        $validator->after(function ($validator) use ($request) {
            $slots = Slot::where('tractor_id', $request->tractor_id)->pluck('date')->toArray();
            if (in_array($request->date, $slots)) {
                $validator->errors()->add(
                    'date',
                    'Already added a slot for this Tractor.'
                );
            }
        });
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $slot = Slot::create($request->all());

        return redirect()->route('slots.index')
            ->with('success', 'Slot created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $slot = Slot::find($id);

        return view('slot.show', compact('slot'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $slot = Slot::find($id);

        $tractors = Tractor::select('id', 'id_no', 'brand', 'model')->get();

        return view('slot.edit', compact('slot', 'tractors'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Slot $slot
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Slot $slot)
    {

        $validator = Validator::make($request->all(), Slot::$rules);
        $validator->after(function ($validator) use ($request, $slot) {
            $slots = Slot::where('id', '!=', $slot->id)->where('tractor_id', $request->tractor_id)->pluck('date')->toArray();
            if (in_array($request->date, $slots)) {
                $validator->errors()->add(
                    'date',
                    'Already added a slot on this date for this Tractor.'
                );
            }
        });
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $slot->update($request->all());

        return redirect()->route('slots.index')
            ->with('success', 'Slot updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $slot = Slot::find($id)->delete();

        return redirect()->route('slots.index')
            ->with('success', 'Slot deleted successfully');
    }
}
