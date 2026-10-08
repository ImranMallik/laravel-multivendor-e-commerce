<?php

use App\Http\Controllers\Vendor\ComingSoonController;
use App\Http\Controllers\Vendor\DashboardController;
use App\Http\Controllers\Vendor\ProfileController;
use App\Http\Controllers\Vendor\ShopSettingsController;
use App\Http\Controllers\Vendor\StatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vendor Routes
|--------------------------------------------------------------------------
|
| Loaded by RouteServiceProvider with the "vendor" prefix, "vendor." name prefix and
| the web, auth:web, active and role:vendor middleware. Logout is the shared "logout" route.
|
*/

// Reachable while pending, rejected or suspended.
Route::get('status', [StatusController::class, 'show'])->name('status');

Route::middleware('vendor.approved')->group(function () {
    Route::redirect('/', '/vendor/dashboard');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('shop', [ShopSettingsController::class, 'edit'])->name('shop.edit');
    Route::put('shop', [ShopSettingsController::class, 'update'])->name('shop.update');

    Route::controller(ProfileController::class)->prefix('profile')->name('profile.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
        Route::delete('avatar', 'destroyAvatar')->name('avatar.destroy');
        Route::put('password', 'updatePassword')->name('password');
    });

    // TODO: replace each placeholder with its real module (products, orders, earnings, withdrawals).
    foreach (['products', 'orders', 'earnings', 'withdrawals'] as $section) {
        Route::get($section, ComingSoonController::class)->defaults('section', $section)->name($section);
    }
});
