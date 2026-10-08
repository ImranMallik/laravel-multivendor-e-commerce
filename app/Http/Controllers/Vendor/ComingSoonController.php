<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Placeholder for vendor sections that are not built yet (products, orders, earnings, withdrawals).
 */
class ComingSoonController extends Controller
{
    public function __invoke(string $section): View
    {
        return view('vendor.coming-soon', ['section' => ucfirst($section)]);
    }
}
