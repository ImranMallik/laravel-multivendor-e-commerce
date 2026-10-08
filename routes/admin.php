<?php

use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CategorySubCategoryController;
use App\Http\Controllers\Admin\ChildCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\SubCategoryController;
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

    // Catalogue structure: Category -> Sub Category -> Child Category.
    Route::get('categories/data', [CategoryController::class, 'data'])->name('categories.data');
    Route::patch('categories/{category}/status', [CategoryController::class, 'status'])->name('categories.status');
    Route::get('categories/{category}/sub-categories', CategorySubCategoryController::class)->name('categories.sub-categories');
    Route::resource('categories', CategoryController::class)->except('show');

    Route::get('sub-categories/data', [SubCategoryController::class, 'data'])->name('sub-categories.data');
    Route::patch('sub-categories/{sub_category}/status', [SubCategoryController::class, 'status'])->name('sub-categories.status');
    Route::resource('sub-categories', SubCategoryController::class)->except('show');

    Route::get('child-categories/data', [ChildCategoryController::class, 'data'])->name('child-categories.data');
    Route::patch('child-categories/{child_category}/status', [ChildCategoryController::class, 'status'])->name('child-categories.status');
    Route::resource('child-categories', ChildCategoryController::class)->except('show');

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
