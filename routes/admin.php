<?php

use App\Enums\ProductFlag;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ChildCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductStateController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\SubCategoryController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Catalog\ChildCategoryOptionsController;
use App\Http\Controllers\Catalog\SubCategoryOptionsController;
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

    Route::get('notifications/{notification}', NotificationController::class)->name('notifications.open');

    Route::controller(VendorController::class)->prefix('vendors')->name('vendors.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('data', 'data')->name('data');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{vendor}', 'show')->name('show');
        Route::post('{vendor}/approve', 'approve')->name('approve');
        Route::post('{vendor}/reject', 'reject')->name('reject');
        Route::post('{vendor}/suspend', 'suspend')->name('suspend');
        Route::post('{vendor}/reactivate', 'reactivate')->name('reactivate');
    });

    // Catalogue structure: Category -> Sub Category -> Child Category.
    Route::get('categories/data', [CategoryController::class, 'data'])->name('categories.data');
    Route::patch('categories/{category}/status', [CategoryController::class, 'status'])->name('categories.status');
    Route::get('categories/{category}/sub-categories', SubCategoryOptionsController::class)->name('categories.sub-categories');
    Route::resource('categories', CategoryController::class)->except('show');

    Route::get('sub-categories/data', [SubCategoryController::class, 'data'])->name('sub-categories.data');
    Route::patch('sub-categories/{sub_category}/status', [SubCategoryController::class, 'status'])->name('sub-categories.status');
    Route::resource('sub-categories', SubCategoryController::class)->except('show');

    Route::get('child-categories/data', [ChildCategoryController::class, 'data'])->name('child-categories.data');
    Route::patch('child-categories/{child_category}/status', [ChildCategoryController::class, 'status'])->name('child-categories.status');
    Route::resource('child-categories', ChildCategoryController::class)->except('show');

    // Product catalog.
    Route::get('brands/data', [BrandController::class, 'data'])->name('brands.data');
    Route::patch('brands/{brand}/status', [BrandController::class, 'status'])->name('brands.status');
    Route::patch('brands/{brand}/featured', [BrandController::class, 'featured'])->name('brands.featured');
    Route::resource('brands', BrandController::class)->except('show');

    Route::get('attributes/data', [AttributeController::class, 'data'])->name('attributes.data');
    Route::patch('attributes/{attribute}/status', [AttributeController::class, 'status'])->name('attributes.status');
    Route::resource('attributes', AttributeController::class)->except('show');

    Route::get('sub-categories/{subCategory}/child-categories', ChildCategoryOptionsController::class)->name('sub-categories.child-categories');

    Route::get('products/data', [ProductController::class, 'data'])->name('products.data');
    Route::get('products/pending', [ProductController::class, 'pending'])->name('products.pending');
    Route::controller(ProductStateController::class)->prefix('products/{product}')->name('products.')->group(function () {
        Route::patch('status', 'status')->name('status');
        Route::patch('flags/{flag}', 'flag')->whereIn('flag', ProductFlag::columns())->name('flag');
        Route::post('approve', 'approve')->name('approve');
        Route::post('reject', 'reject')->name('reject');
        Route::delete('images/{image}', 'destroyImage')->scopeBindings()->name('images.destroy');
    });
    Route::resource('products', ProductController::class);

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
