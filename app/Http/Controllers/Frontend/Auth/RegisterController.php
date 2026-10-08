<?php

namespace App\Http\Controllers\Frontend\Auth;

use App\Actions\Customer\RegisterCustomerAction;
use App\Actions\Vendor\RegisterVendorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\RegisterRequest;
use App\Http\Requests\Frontend\VendorRegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('frontend.auth.register', ['active' => 'customer']);
    }

    public function createVendor(): View
    {
        return view('frontend.auth.register', ['active' => 'vendor']);
    }

    public function storeCustomer(RegisterRequest $request, RegisterCustomerAction $register): RedirectResponse
    {
        $user = $register->execute($request->validated());

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route('account.dashboard')->with('success', 'Welcome! Your account has been created.');
    }

    public function storeVendor(VendorRegisterRequest $request, RegisterVendorAction $register): RedirectResponse
    {
        $vendor = $register->execute($request->validated());

        Auth::guard('web')->login($vendor->user);
        $request->session()->regenerate();

        return redirect()->route('vendor.status')->with('success', 'Your shop was submitted and is waiting for approval.');
    }
}
