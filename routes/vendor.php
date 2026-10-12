<?php

use App\Http\Controllers\Catalog\ChildCategoryOptionsController;
use App\Http\Controllers\Catalog\SubCategoryOptionsController;
use App\Http\Controllers\Vendor\ComingSoonController;
use App\Http\Controllers\Vendor\DashboardController;
use App\Http\Controllers\Vendor\ProductController;
use App\Http\Controllers\Vendor\ProductStateController;
use App\Http\Controllers\Vendor\ProfileController;
use App\Http\Controllers\Vendor\ShopSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vendor Routes
|--------------------------------------------------------------------------
|
| Loaded by RouteServiceProvider with the "vendor" prefix, "vendor." name prefix and
| the web, auth:web, active and vendor (user owns a shop) middleware. Logout is the shared "logout" route.
|
*/

// Shop status now lives on the account page (Become a Seller); old links keep working.
Route::redirect('status', '/account/seller')->name('status');

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

    // Products: only the signed-in seller's own (ProductPolicy on every action).
    Route::get('products/data', [ProductController::class, 'data'])->name('products.data');
    Route::patch('products/{product}/status', [ProductStateController::class, 'status'])->name('products.status');
    Route::delete('products/{product}/images/{image}', [ProductStateController::class, 'destroyImage'])->scopeBindings()->name('products.images.destroy');
    Route::resource('products', ProductController::class)->except('show');

    // Dependent category dropdowns of the product form.
    Route::get('categories/{category}/sub-categories', SubCategoryOptionsController::class)->name('categories.sub-categories');
    Route::get('sub-categories/{subCategory}/child-categories', ChildCategoryOptionsController::class)->name('sub-categories.child-categories');

    // TODO: replace each placeholder with its real module (orders, earnings, withdrawals).
    foreach (['orders', 'earnings', 'withdrawals'] as $section) {
        Route::get($section, ComingSoonController::class)->defaults('section', $section)->name($section);
    }
});
