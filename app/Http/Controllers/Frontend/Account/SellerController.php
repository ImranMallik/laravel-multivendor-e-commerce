<?php

namespace App\Http\Controllers\Frontend\Account;

use App\Actions\Vendor\ReapplyVendorAction;
use App\Actions\Vendor\SubmitVendorApplicationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\ApplyForVendorRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * "Become a Seller": one page that shows the user's shop state (no shop, pending, rejected,
 * approved, suspended). The shop always comes from the signed-in user, never from the request.
 */
class SellerController extends Controller
{
    public function show(): View
    {
        $vendor = auth('web')->user()->vendor;

        if ($vendor) {
            $this->authorize('viewApplication', $vendor);
        }

        return view('frontend.account.seller', ['vendor' => $vendor]);
    }

    public function apply(ApplyForVendorRequest $request, SubmitVendorApplicationAction $action): RedirectResponse
    {
        try {
            $action->execute($request->user(), $request->safe()->except('logo'), $request->file('logo'));
        } catch (DomainException $e) {
            return redirect()->route('account.seller.show')->with('error', $e->getMessage());
        }

        return redirect()->route('account.seller.show')->with('success', 'Your application was submitted. We will email you once it has been reviewed.');
    }

    public function reapply(ApplyForVendorRequest $request, ReapplyVendorAction $action): RedirectResponse
    {
        $vendor = $request->user()->vendor;

        abort_unless($vendor, 404);
        $this->authorize('reapply', $vendor);

        try {
            $action->execute($vendor, $request->safe()->except('logo'), $request->file('logo'));
        } catch (DomainException $e) {
            return redirect()->route('account.seller.show')->with('error', $e->getMessage());
        }

        return redirect()->route('account.seller.show')->with('success', 'Your application was sent again and is under review.');
    }
}
