<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Repositories\ProductRepository;
use Illuminate\View\View;

/**
 * A vendor's public shop page. Only approved shops are public.
 * TODO: the template's vendor_details.html banner, ratings and sidebar.
 */
class StoreController extends Controller
{
    public function show(string $slug, ProductRepository $products): View
    {
        $vendor = Vendor::where('slug', $slug)->where('status', VendorStatus::Approved->value)->firstOrFail();

        return view('frontend.store.show', [
            'vendor' => $vendor,
            'products' => $products->paginate(['vendor_id' => $vendor->id]),
        ]);
    }
}
