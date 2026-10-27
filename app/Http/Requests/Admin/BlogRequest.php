<?php

namespace App\Http\Requests\Admin;

use App\Enums\BlogStatus;
use App\Services\BlogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create and update of a blog post (the image is required only on create).
 */
class BlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'show_on_home' => $this->boolean('show_on_home'),
            // The slug field is optional (filled from the title); compare it in its final form.
            'slug' => filled($this->input('slug')) ? Str::slug((string) $this->input('slug')) : null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $blog = $this->route('blog');

        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('blogs', 'slug')->ignore($blog?->id)],
            'blog_category_id' => ['required', 'integer', Rule::exists('blog_categories', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'image' => [$blog ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:200000'],
            'tags' => ['nullable', 'string', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::enum(BlogStatus::class)],
            'published_at' => ['nullable', 'date_format:Y-m-d H:i'],
            'show_on_home' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (trim(strip_tags((string) $this->input('content'), '<img>')) === '') {
                    $validator->errors()->add('content', 'The content field is required.');
                }

                $tags = array_filter(array_map('trim', explode(',', (string) $this->input('tags'))));

                if (count($tags) > BlogService::MAX_TAGS) {
                    $validator->errors()->add('tags', 'Use at most '.BlogService::MAX_TAGS.' tags.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'blog_category_id' => 'category',
            'published_at' => 'publish date',
            'seo_title' => 'SEO title',
            'seo_description' => 'SEO description',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'blog_category_id.exists' => 'Choose an active category.',
            'published_at.date_format' => 'Use the date picker (YYYY-MM-DD HH:MM).',
        ];
    }
}
