<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'icon' => 'fas fa-tag',
            'image' => null,
            'sort_order' => 0,
            'is_active' => true,
            'show_in_menu' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function hiddenFromMenu(): static
    {
        return $this->state(fn () => ['show_in_menu' => false]);
    }
}
