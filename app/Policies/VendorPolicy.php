<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

/**
 * Applies to the web guard only: a vendor may see and change just their own shop.
 * Admins act through the admin guard and the admin Actions, not through this policy.
 */
class VendorPolicy
{
    public function view(User $user, Vendor $vendor): bool
    {
        return $user->isVendor() && $vendor->user_id === $user->id;
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $this->view($user, $vendor);
    }
}
