<?php

namespace App\Actions\Customer;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

class RegisterCustomerAction
{
    /**
     * Create a customer account. The role is fixed here (and `role` is not mass assignable),
     * so it can never come from the request.
     *
     * @param  array{name: string, email: string, phone?: string|null, password: string}  $data
     */
    public function execute(array $data): User
    {
        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
        ]);
        $user->role = UserRole::Customer;
        $user->save();

        event(new Registered($user));

        return $user;
    }
}
