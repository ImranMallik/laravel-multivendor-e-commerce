<?php

namespace App\Actions\Vendor;

use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Events\VendorRegistered;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

class RegisterVendorAction
{
    /**
     * Create the user (role Vendor) and the pending vendor profile atomically.
     *
     * @param  array{name: string, email: string, phone?: string|null, password: string, shop_name: string, shop_phone?: string|null, address: string}  $data
     */
    public function execute(array $data): Vendor
    {
        $vendor = DB::transaction(function () use ($data) {
            $user = new User([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
            ]);
            $user->role = UserRole::Vendor;
            $user->save();

            $vendor = new Vendor([
                'shop_name' => $data['shop_name'],
                'phone' => $data['shop_phone'] ?? null,
                'address' => $data['address'],
            ]);
            $vendor->user()->associate($user);
            $vendor->status = VendorStatus::Pending;
            $vendor->save();

            return $vendor;
        });

        // Dispatched only after the transaction has committed.
        event(new Registered($vendor->user));
        event(new VendorRegistered($vendor));

        return $vendor;
    }
}
