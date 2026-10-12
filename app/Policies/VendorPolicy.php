<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

/**
 * Applies to the web guard only: a user may see and change just their own shop, and only while it
 * is approved. Controllers always pass auth()->user()->vendor, never an id from the request.
 * Admins act through the admin guard and the admin Actions, not through this policy.
 */
class VendorPolicy
{
    public function view(User $user, Vendor $vendor): bool
    {
        return $vendor->user_id === $user->id && $vendor->isApproved();
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $this->view($user, $vendor);
    }

    /**
     * The application page is for the owner of the shop, in any status.
     */
    public function viewApplication(User $user, Vendor $vendor): bool
    {
        return $vendor->user_id === $user->id;
    }

    public function reapply(User $user, Vendor $vendor): bool
    {
        return $this->viewApplication($user, $vendor);
    }
}
