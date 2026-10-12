<?php

namespace App\View\Composers;

use App\Enums\ProductApprovalStatus;
use App\Models\Product;
use Illuminate\View\View;

/**
 * Counts shown as badges in the admin sidebar.
 */
class AdminSidebarComposer
{
    public function compose(View $view): void
    {
        $view->with('pendingProductsCount', Product::where('approval_status', ProductApprovalStatus::Pending->value)->count());
    }
}
