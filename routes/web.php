<?php

use App\Http\Controllers\Frontend\Account\DashboardController;
use App\Http\Controllers\Frontend\Account\PlaceholderController;
use App\Http\Controllers\Frontend\Account\ProfileController;
use App\Http\Controllers\Frontend\Account\SellerController;
use App\Http\Controllers\Frontend\Auth\ForgotPasswordController;
use App\Http\Controllers\Frontend\Auth\LoginController;
use App\Http\Controllers\Frontend\Auth\RegisterController;
use App\Http\Controllers\Frontend\Auth\ResetPasswordController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\ShopController;
use App\Http\Controllers\Frontend\StoreController;
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

// Product listings (all, or by category level). Inactive levels are a 404.
Route::controller(ShopController::class)->group(function () {
    Route::get('shop', 'index')->name('shop.index');
    Route::get('category/{categorySlug}', 'category')->name('category.show');
    Route::get('category/{categorySlug}/{subSlug}', 'subCategory')->name('category.sub.show');
    Route::get('category/{categorySlug}/{subSlug}/{childSlug}', 'childCategory')->name('category.child.show');
});

// Product page and public vendor shop pages (404 unless visible / approved).
Route::get('product/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('store/{slug}', [StoreController::class, 'show'])->name('store.show');

// Add to cart (validates the product and its variant; the cart itself comes later).
Route::post('cart/add', [CartController::class, 'add'])->name('cart.add');

// Guests only. "I want to sell" creates a customer account plus a pending shop application.
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

// Any signed-in web user can log out.
Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth:web')->name('logout');

// Account area: every signed-in user, sellers included (one account, the shop is an extra layer).
Route::middleware(['auth:web', 'active'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::controller(ProfileController::class)->prefix('profile')->name('profile.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
        Route::delete('avatar', 'destroyAvatar')->name('avatar.destroy');
        Route::put('password', 'updatePassword')->name('password');
    });

    // Become a Seller: application form, review status and re-application.
    Route::controller(SellerController::class)->prefix('seller')->name('seller.')->group(function () {
        Route::get('/', 'show')->name('show');
        Route::post('/', 'apply')->name('apply');
        Route::put('/', 'reapply')->name('reapply');
    });

    // TODO: replace with real controllers once orders and wishlists exist.
    Route::get('orders', [PlaceholderController::class, 'orders'])->name('orders');
    Route::get('wishlist', [PlaceholderController::class, 'wishlist'])->name('wishlist');
});

// Unmatched URLs: running through the web group gives the 404 page its session and shared $errors.
Route::fallback(fn () => abort(404));
