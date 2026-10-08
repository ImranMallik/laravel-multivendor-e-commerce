<?php

use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\VendorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Loaded by RouteServiceProvider with the "admin" prefix, "admin." name
| prefix and the "web" middleware group.
|
*/

Route::middleware('guest:admin')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');

    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'store'])->name('password.store');
});

// auth.session must follow auth:admin so it validates the admin guard's password hash.
Route::middleware(['auth:admin', 'auth.session'])->group(function () {
    Route::redirect('/', '/admin/dashboard');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::controller(VendorController::class)->prefix('vendors')->name('vendors.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('data', 'data')->name('data');
        Route::get('{vendor}', 'show')->name('show');
        Route::post('{vendor}/approve', 'approve')->name('approve');
        Route::post('{vendor}/reject', 'reject')->name('reject');
        Route::post('{vendor}/suspend', 'suspend')->name('suspend');
        Route::post('{vendor}/reactivate', 'reactivate')->name('reactivate');
    });

    // Website content: home banner sliders.
    Route::get('sliders/data', [SliderController::class, 'data'])->name('sliders.data');
    Route::patch('sliders/{slider}/status', [SliderController::class, 'status'])->name('sliders.status');
    Route::resource('sliders', SliderController::class)->except('show');

    Route::controller(ProfileController::class)->prefix('profile')->name('profile.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
        Route::put('password', 'updatePassword')->name('password');
        Route::delete('photo', 'destroyPhoto')->name('photo.destroy');
    });
});
