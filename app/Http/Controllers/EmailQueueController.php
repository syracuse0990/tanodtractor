<?php

namespace App\Http\Controllers;

use App\Models\EmailQueue;
use Illuminate\Http\Request;

/**
 * Class EmailQueueController
 * @package App\Http\Controllers
 */
class EmailQueueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $emailQueues = EmailQueue::paginate();

        return view('email-queue.index', compact('emailQueues'))
            ->with('i', (request()->input('page', 1) - 1) * $emailQueues->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $emailQueue = new EmailQueue();
        return view('email-queue.create', compact('emailQueue'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate(EmailQueue::$rules);

        $emailQueue = EmailQueue::create($request->all());

        return redirect()->route('email-queues.index')
            ->with('success', 'EmailQueue created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $emailQueue = EmailQueue::findorFail($id);

        return view('email-queue.show', compact('emailQueue'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $emailQueue = EmailQueue::findorFail($id);

        return view('email-queue.edit', compact('emailQueue'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  EmailQueue $emailQueue
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EmailQueue $emailQueue)
    {
        request()->validate(EmailQueue::$rules);

        $emailQueue->update($request->all());

        return redirect()->route('email-queues.index')
            ->with('success', 'EmailQueue updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $emailQueue = EmailQueue::findorFail($id)->delete();

        return redirect()->route('email-queues.index')
            ->with('success', 'EmailQueue deleted successfully');
    }
}
