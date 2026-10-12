<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Vendor panel (web guard): a seller with an approved shop manages only the products of that shop.
 * Admins work through the admin guard and never pass through this policy.
 */
class ProductPolicy
{
    public function create(User $user): bool
    {
        return $user->isApprovedVendor();
    }

    public function view(User $user, Product $product): bool
    {
        return $this->owns($user, $product);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->owns($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->owns($user, $product);
    }

    /**
     * The Active/Inactive switch works only once the product is approved.
     */
    public function toggleStatus(User $user, Product $product): bool
    {
        return $this->owns($user, $product) && $product->isApproved();
    }

    private function owns(User $user, Product $product): bool
    {
        $vendor = $user->vendor;

        return $vendor !== null
            && $vendor->isApproved()
            && $product->vendor_id !== null
            && $product->vendor_id === $vendor->id;
    }
}
