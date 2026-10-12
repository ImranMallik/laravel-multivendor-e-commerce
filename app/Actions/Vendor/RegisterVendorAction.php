<?php

namespace App\Actions\Vendor;

use App\Enums\VendorSource;
use App\Events\VendorApplicationSubmitted;
use App\Models\User;
use App\Models\Vendor;
use App\Services\VendorStatusService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

class RegisterVendorAction
{
    public function __construct(private readonly VendorStatusService $status) {}

    /**
     * Signup with "I want to sell": a normal customer account plus a pending shop application, atomically.
     *
     * @param  array{name: string, email: string, phone?: string|null, password: string, shop_name: string, shop_phone?: string|null, address: string}  $data
     */
    public function execute(array $data): Vendor
    {
        $vendor = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
            ]);

            return $this->status->open($user, $data, VendorSource::Application);
        });

        // Dispatched only after the transaction has committed.
        event(new Registered($vendor->user));
        event(new VendorApplicationSubmitted($vendor));

        return $vendor;
    }
}
