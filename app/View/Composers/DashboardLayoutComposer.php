<?php

namespace App\View\Composers;

use Illuminate\View\View;

/**
 * Chooses the sidebar of the dashboard layout: the vendor panel has its own, everything else
 * (customer account, Become a Seller) uses the customer one.
 */
class DashboardLayoutComposer
{
    public function compose(View $view): void
    {
        $view->with('sidebar', request()->routeIs('vendor.*') ? 'vendor' : 'customer');
    }
}
