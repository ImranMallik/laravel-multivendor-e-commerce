<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('vendor.dashboard', ['vendor' => auth('web')->user()->vendor]);
    }
}
