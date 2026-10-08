<?php

namespace App\Providers;

use App\Models\Slider;
use App\Observers\SliderObserver;
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
    }
}
