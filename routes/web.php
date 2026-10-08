<?php

use App\Http\Controllers\Frontend\Account\DashboardController;
use App\Http\Controllers\Frontend\Account\PlaceholderController;
use App\Http\Controllers\Frontend\Account\ProfileController;
use App\Http\Controllers\Frontend\Auth\ForgotPasswordController;
use App\Http\Controllers\Frontend\Auth\LoginController;
use App\Http\Controllers\Frontend\Auth\RegisterController;
use App\Http\Controllers\Frontend\Auth\ResetPasswordController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront Routes
|--------------------------------------------------------------------------
|
| Customer and vendor routes on the "web" guard. Admin routes live in routes/admin.php
| and vendor panel routes in routes/vendor.php.
|
*/

// Public
Route::get('/', [HomeController::class, 'index'])->name('home');

// Category browsing (placeholder pages; products come later). Inactive levels are a 404.
Route::controller(ShopController::class)->group(function () {
    Route::get('shop', 'index')->name('shop.index');
    Route::get('category/{categorySlug}', 'category')->name('category.show');
    Route::get('category/{categorySlug}/{subSlug}', 'subCategory')->name('category.sub.show');
    Route::get('category/{categorySlug}/{subSlug}/{childSlug}', 'childCategory')->name('category.child.show');
});

// Guests only. The role of a new account is decided by which route is used here.
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'storeCustomer'])->name('register.store');
    Route::get('register/vendor', [RegisterController::class, 'createVendor'])->name('register.vendor');
    Route::post('register/vendor', [RegisterController::class, 'storeVendor'])->name('register.vendor.store');

    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'store'])->name('password.store');
});

// Any signed-in web user (customer or vendor) can log out.
Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth:web')->name('logout');

// Customer account area
Route::middleware(['auth:web', 'active', 'role:customer'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::controller(ProfileController::class)->prefix('profile')->name('profile.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
        Route::delete('avatar', 'destroyAvatar')->name('avatar.destroy');
        Route::put('password', 'updatePassword')->name('password');
    });

    // TODO: replace with real controllers once orders and wishlists exist.
    Route::get('orders', [PlaceholderController::class, 'orders'])->name('orders');
    Route::get('wishlist', [PlaceholderController::class, 'wishlist'])->name('wishlist');
});

// Unmatched URLs: running through the web group gives the 404 page its session and shared $errors.
Route::fallback(fn () => abort(404));
