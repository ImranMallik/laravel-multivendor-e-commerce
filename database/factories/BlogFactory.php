<?php

namespace Database\Factories;

use App\Enums\BlogStatus;
use App\Models\Admin;
use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Default: published yesterday, shown on the home page.
 *
 * @extends Factory<Blog>
 */
class BlogFactory extends Factory
{
    protected $model = Blog::class;

    public function definition(): array
    {
        $title = ucfirst(fake()->unique()->words(5, true));

        return [
            'blog_category_id' => BlogCategory::factory(),
            'admin_id' => Admin::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'image' => 'blogs/'.fake()->uuid().'.jpg',
            'excerpt' => fake()->sentence(12),
            'content' => '<p>'.fake()->paragraph(4).'</p>',
            'status' => BlogStatus::Published,
            'published_at' => now()->subDay(),
            'show_on_home' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => BlogStatus::Draft, 'published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['status' => BlogStatus::Published, 'published_at' => now()->addDays(2)]);
    }

    public function hiddenFromHome(): static
    {
        return $this->state(fn () => ['show_on_home' => false]);
    }
}
