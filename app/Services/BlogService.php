<?php

namespace App\Services;

use App\Enums\BlogStatus;
use App\Models\Admin;
use App\Models\Blog;
use App\Models\BlogTag;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Admin writes for blog posts: image upload, purified content, auto excerpt, unique slug, tags and the
 * publish time (entered in the store time zone, stored in UTC).
 */
class BlogService
{
    public const MAX_TAGS = 10;

    public function __construct(
        private readonly ImageUploadService $images,
        private readonly HtmlSanitizer $sanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated fields without the image
     */
    public function create(array $data, UploadedFile $image, ?Admin $author): Blog
    {
        $path = $this->images->upload($image, Blog::IMAGE_FOLDER);

        try {
            return DB::transaction(function () use ($data, $path, $author) {
                $blog = new Blog($this->attributes($data));
                $blog->image = $path;
                $blog->admin_id = $author?->id;
                $blog->slug = $this->uniqueSlug($data['slug'] ?? null, $data['title']);
                $blog->save();

                $this->syncTags($blog, $data['tags'] ?? null);

                return $blog;
            });
        } catch (\Throwable $e) {
            $this->images->delete($path);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data  validated fields without the image
     */
    public function update(Blog $blog, array $data, ?UploadedFile $image): Blog
    {
        $newPath = $image ? $this->images->upload($image, Blog::IMAGE_FOLDER) : null;
        $oldPath = $blog->image;

        try {
            DB::transaction(function () use ($blog, $data, $newPath) {
                $blog->fill($this->attributes($data));
                $blog->slug = $this->uniqueSlug($data['slug'] ?? null, $data['title'], $blog->id);

                if ($newPath) {
                    $blog->image = $newPath;
                }

                $blog->save();
                $this->syncTags($blog, $data['tags'] ?? null);
            });
        } catch (\Throwable $e) {
            $this->images->delete($newPath);

            throw $e;
        }

        if ($newPath) {
            $this->images->delete($oldPath);
        }

        return $blog;
    }

    public function toggleShowOnHome(Blog $blog): Blog
    {
        $blog->update(['show_on_home' => ! $blog->show_on_home]);

        return $blog;
    }

    /**
     * Soft delete: the post disappears from the storefront; its image stays so it can be restored.
     */
    public function delete(Blog $blog): void
    {
        $blog->delete();
    }

    /**
     * An image inserted in the editor; returns its public URL (the purifier keeps <img src>).
     */
    public function uploadContentImage(UploadedFile $image): string
    {
        return asset('storage/'.$this->images->upload($image, Blog::CONTENT_IMAGE_FOLDER));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $content = (string) $this->sanitizer->clean($data['content'] ?? '');
        $status = BlogStatus::from($data['status']);

        $publishedAt = filled($data['published_at'] ?? null)
            ? CarbonImmutable::parse($data['published_at'], config('marketplace.timezone'))->utc()
            : null;

        // Publishing without a date publishes now; a draft keeps the planned date (if any).
        if ($status === BlogStatus::Published && $publishedAt === null) {
            $publishedAt = now();
        }

        return [
            'blog_category_id' => $data['blog_category_id'],
            'title' => $data['title'],
            'content' => $content,
            'excerpt' => filled($data['excerpt'] ?? null) ? trim($data['excerpt']) : $this->excerptFrom($content),
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'status' => $status,
            'published_at' => $publishedAt,
            'show_on_home' => (bool) ($data['show_on_home'] ?? false),
        ];
    }

    /**
     * About 160 characters of plain text from the content (for the meta description).
     */
    private function excerptFrom(string $content): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace('<', ' <', $content)), ENT_QUOTES | ENT_HTML5)));

        return Str::limit($text, 160);
    }

    private function uniqueSlug(?string $wanted, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug(filled($wanted) ? $wanted : $title) ?: 'post';
        $base = Str::limit($base, 200, '');
        $slug = $base;
        $suffix = 2;

        while (Blog::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * Tags arrive as "fashion, summer style, men"; empty names are skipped, at most MAX_TAGS kept.
     */
    private function syncTags(Blog $blog, ?string $tags): void
    {
        $names = collect(explode(',', (string) $tags))
            ->map(fn (string $name) => trim($name))
            ->filter(fn (string $name) => $name !== '' && Str::slug($name) !== '')
            ->unique(fn (string $name) => Str::slug($name))
            ->take(self::MAX_TAGS);

        $blog->tags()->sync($names->map(fn (string $name) => BlogTag::findOrCreateByName($name)->id)->all());
    }
}
