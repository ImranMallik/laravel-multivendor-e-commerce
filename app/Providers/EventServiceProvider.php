<?php

namespace App\Providers;

use App\Events\ProductApproved;
use App\Events\ProductRejected;
use App\Events\VendorApplicationSubmitted;
use App\Events\VendorApproved;
use App\Events\VendorCreatedByAdmin;
use App\Events\VendorReactivated;
use App\Events\VendorRejected;
use App\Events\VendorSuspended;
use App\Listeners\NotifyAboutVendorApplication;
use App\Listeners\SendProductApprovedNotification;
use App\Listeners\SendProductRejectedNotification;
use App\Listeners\SendVendorApprovedNotification;
use App\Listeners\SendVendorReactivatedNotification;
use App\Listeners\SendVendorRejectedNotification;
use App\Listeners\SendVendorSuspendedNotification;
use App\Listeners\SendVendorWelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        VendorCreatedByAdmin::class => [
            SendVendorWelcomeNotification::class,
        ],
        VendorApplicationSubmitted::class => [
            NotifyAboutVendorApplication::class,
        ],
        VendorApproved::class => [
            SendVendorApprovedNotification::class,
        ],
        VendorRejected::class => [
            SendVendorRejectedNotification::class,
        ],
        VendorSuspended::class => [
            SendVendorSuspendedNotification::class,
        ],
        VendorReactivated::class => [
            SendVendorReactivatedNotification::class,
        ],
        ProductApproved::class => [
            SendProductApprovedNotification::class,
        ],
        ProductRejected::class => [
            SendProductRejectedNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
