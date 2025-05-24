<?php

namespace App\Http\Controllers;

use App\Jobs\StoreDeviceDetail;

class AdminController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function getDeviceDetail()
    {
        StoreDeviceDetail::dispatch();
        echo "Added to queue";
    }
}
