<?php

namespace Database\Factories;

use App\Models\Slider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slider>
 */
class SliderFactory extends Factory
{
    protected $model = Slider::class;

    public function definition(): array
    {
        return [
            'top_text' => 'new arrivals',
            'title' => fake()->unique()->words(3, true),
            'offer_text' => 'start at $'.fake()->numberBetween(10, 99).'.00',
            'button_text' => 'shop now',
            'button_url' => 'https://example.com/shop',
            'image' => 'sliders/'.fake()->uuid().'.jpg',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withoutButton(): static
    {
        return $this->state(fn () => ['button_text' => null, 'button_url' => null]);
    }
}
