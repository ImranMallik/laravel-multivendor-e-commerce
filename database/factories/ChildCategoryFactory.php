<?php

namespace Database\Factories;

use App\Models\ChildCategory;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * category_id is filled in by the model from the sub category.
 *
 * @extends Factory<ChildCategory>
 */
class ChildCategoryFactory extends Factory
{
    protected $model = ChildCategory::class;

    public function definition(): array
    {
        return [
            'sub_category_id' => SubCategory::factory(),
            'name' => fake()->unique()->words(2, true),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
