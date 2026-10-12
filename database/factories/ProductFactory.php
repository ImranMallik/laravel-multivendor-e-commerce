<?php

namespace Database\Factories;

use App\Enums\ProductApprovalStatus;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'vendor_id' => null,
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'category_id' => Category::factory(),
            'thumbnail' => 'products/thumbnails/'.fake()->uuid().'.jpg',
            'short_description' => fake()->sentence(),
            'long_description' => '<p>'.fake()->paragraph().'</p>',
            'price' => 100,
            'stock_qty' => 10,
            'status' => ProductStatus::Active,
            'approval_status' => ProductApprovalStatus::Approved,
        ];
    }

    public function forVendor(?Vendor $vendor = null): static
    {
        return $this->state(fn () => ['vendor_id' => $vendor?->id ?? Vendor::factory()->approved()]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['approval_status' => ProductApprovalStatus::Pending]);
    }

    public function rejected(string $reason = 'Photos are blurry.'): static
    {
        return $this->state(fn () => ['approval_status' => ProductApprovalStatus::Rejected, 'rejection_reason' => $reason]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Inactive]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Draft]);
    }

    /**
     * An offer that is valid today.
     */
    public function onOffer(float $offerPrice = 80, int $daysLeft = 5): static
    {
        return $this->state(fn () => [
            'offer_price' => $offerPrice,
            'offer_start_date' => today()->subDay(),
            'offer_end_date' => today()->addDays($daysLeft),
        ]);
    }

    public function flagged(string $flag): static
    {
        return $this->state(fn () => [$flag => true]);
    }
}
