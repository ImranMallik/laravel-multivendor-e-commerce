<?php

namespace App\Http\Requests\Frontend;

use App\Enums\CommentStatus;
use App\Models\Blog;
use App\Repositories\BlogRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A comment (or a reply) on a published post. Guests give a name and email; signed-in users comment under
 * their account. Replies (parent_id) are for signed-in users and must answer an approved top-level comment
 * of the same post. The hidden "website" field is the honeypot (checked in the controller).
 */
class BlogCommentRequest extends FormRequest
{
    public const HONEYPOT = 'website';

    private ?Blog $blog = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * The post being commented on (404 unless it is published).
     */
    public function blog(): Blog
    {
        return $this->blog ??= app(BlogRepository::class)->findVisibleBySlug((string) $this->route('slug'));
    }

    public function isSpamBot(): bool
    {
        return filled($this->input(self::HONEYPOT));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $guest = $this->user('web') === null;

        return [
            'name' => [$guest ? 'required' : 'nullable', 'string', 'max:100'],
            'email' => [$guest ? 'required' : 'nullable', 'string', 'email', 'max:255'],
            'comment' => ['required', 'string', 'min:2', 'max:2000'],
            'parent_id' => [
                $guest ? 'prohibited' : 'nullable',
                'integer',
                Rule::exists('blog_comments', 'id')
                    ->where('blog_id', $this->blog()->id)
                    ->whereNull('parent_id')
                    ->where('status', CommentStatus::Approved->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parent_id.prohibited' => 'Please log in to reply to a comment.',
            'parent_id.exists' => 'That comment can no longer be replied to.',
        ];
    }
}
