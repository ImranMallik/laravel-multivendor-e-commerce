<?php

namespace Database\Factories;

use App\Enums\CommentStatus;
use App\Models\Blog;
use App\Models\BlogComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Default: an approved top-level guest comment.
 *
 * @extends Factory<BlogComment>
 */
class BlogCommentFactory extends Factory
{
    protected $model = BlogComment::class;

    public function definition(): array
    {
        return [
            'blog_id' => Blog::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'comment' => fake()->sentence(10),
            'status' => CommentStatus::Approved,
            'ip_address' => '127.0.0.1',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => CommentStatus::Pending]);
    }

    public function spam(): static
    {
        return $this->state(fn () => ['status' => CommentStatus::Spam]);
    }
}
