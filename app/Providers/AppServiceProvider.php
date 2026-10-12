<?php

namespace App\Providers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Slider;
use App\Models\SubCategory;
use App\Models\Vendor;
use App\Observers\BrandObserver;
use App\Observers\CatalogCacheObserver;
use App\Observers\CategoryTreeObserver;
use App\Observers\ProductObserver;
use App\Observers\SliderObserver;
use App\Observers\VendorProductCacheObserver;
use App\View\Composers\AdminNotificationsComposer;
use App\View\Composers\AdminSidebarComposer;
use App\View\Composers\BrandSliderComposer;
use App\View\Composers\CategoryMenuComposer;
use App\View\Composers\DashboardLayoutComposer;
use App\View\Composers\ProductFormComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Clears the home banner cache whenever a slider changes.
        Slider::observe(SliderObserver::class);

        // Clears the featured brands cache (home brand slider) whenever a brand changes.
        Brand::observe(BrandObserver::class);

        // Clears the cached category menu whenever any category level changes.
        Category::observe(CategoryTreeObserver::class);
        SubCategory::observe(CategoryTreeObserver::class);
        ChildCategory::observe(CategoryTreeObserver::class);

        // Clears the cached home products when a product changes (and removes files on force delete),
        // or when a vendor status change shows or hides their products.
        Product::observe(ProductObserver::class);
        Vendor::observe(VendorProductCacheObserver::class);

        // Variants and attributes show on cards and product pages: their changes clear the cached products too.
        ProductVariant::observe(CatalogCacheObserver::class);
        Attribute::observe(CatalogCacheObserver::class);
        AttributeValue::observe(CatalogCacheObserver::class);

        // The storefront menu gets its category tree here, never from a query inside Blade.
        View::composer('frontend.layouts.menu', CategoryMenuComposer::class);
        View::composer('frontend.home.section.brand-slider', BrandSliderComposer::class);
        View::composer('dashboard.layouts.app', DashboardLayoutComposer::class);
        View::composer('admin.layouts.partials.navbar', AdminNotificationsComposer::class);
        View::composer('admin.layouts.partials.sidebar', AdminSidebarComposer::class);
        View::composer(['admin.products.index', 'admin.products.partials.form', 'vendor.products.partials.form'], ProductFormComposer::class);
    }
}
