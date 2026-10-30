<?php

namespace Database\Factories;

use App\Models\SearchLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchLog>
 */
class SearchLogFactory extends Factory
{
    protected $model = SearchLog::class;

    public function definition(): array
    {
        return [
            'term' => strtolower(fake()->words(2, true)),
            'results_count' => fake()->numberBetween(0, 40),
            'user_id' => null,
        ];
    }

    public function withoutResults(): static
    {
        return $this->state(fn () => ['results_count' => 0]);
    }
}
