<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::Vendor]),
            'shop_name' => fake()->unique()->company(),
            'slug' => fn (array $attributes) => Vendor::uniqueSlug($attributes['shop_name']),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'status' => VendorStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => VendorStatus::Approved, 'approved_at' => now()]);
    }

    public function rejected(string $reason = 'Incomplete documents.'): static
    {
        return $this->state(fn () => ['status' => VendorStatus::Rejected, 'rejection_reason' => $reason]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => VendorStatus::Suspended, 'approved_at' => now()]);
    }
}
