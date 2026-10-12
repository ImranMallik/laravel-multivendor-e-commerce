<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\Slider;
use App\Models\SubCategory;
use App\Observers\BrandObserver;
use App\Observers\CategoryTreeObserver;
use App\Observers\SliderObserver;
use App\View\Composers\BrandSliderComposer;
use App\View\Composers\CategoryMenuComposer;
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

        // The storefront menu gets its category tree here, never from a query inside Blade.
        View::composer('frontend.layouts.menu', CategoryMenuComposer::class);
        View::composer('frontend.home.section.brand-slider', BrandSliderComposer::class);
    }
}
