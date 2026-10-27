<?php

namespace Tests\Feature\Blog;

use App\Enums\BlogStatus;
use App\Enums\CommentStatus;
use App\Models\Admin;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\User;
use App\Repositories\BlogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBlogTest extends TestCase
{
    use RefreshDatabase;

    private ?Admin $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function admin(): static
    {
        return $this->actingAs($this->admin ??= Admin::factory()->create(), 'admin');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function postData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Summer Style Guide',
            'slug' => '',
            'blog_category_id' => BlogCategory::factory()->create()->id,
            'image' => UploadedFile::fake()->image('cover.jpg', 1210, 637),
            'excerpt' => '',
            'content' => '<p>Light fabrics and <strong>bright</strong> colours.</p>',
            'tags' => 'Fashion, summer style, fashion',
            'seo_title' => '',
            'seo_description' => '',
            'status' => 'published',
            'published_at' => '',
            'show_on_home' => 1,
        ], $overrides);
    }

    private function table(string $route): array
    {
        $this->app->forgetInstance('datatables.request');

        return $this->admin()->getJson(route($route, ['draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => '']]))
            ->assertOk()->json('data');
    }

    /* ---------- Categories ---------- */

    public function test_admin_manages_blog_categories(): void
    {
        $this->admin()->get(route('admin.blog-categories.index'))->assertOk()->assertSee('blog-categories-table');
        $this->admin()->get(route('admin.blog-categories.create'))->assertOk();

        $this->admin()->post(route('admin.blog-categories.store'), ['name' => 'Life Style', 'sort_order' => 2, 'is_active' => 1])
            ->assertRedirect(route('admin.blog-categories.index'));

        $category = BlogCategory::firstOrFail();
        $this->assertSame('life-style', $category->slug);

        $this->admin()->put(route('admin.blog-categories.update', $category), ['name' => 'Lifestyle', 'sort_order' => 1, 'is_active' => 1])
            ->assertRedirect();
        $this->assertSame('lifestyle', $category->fresh()->slug);

        $this->admin()->post(route('admin.blog-categories.store'), ['name' => 'Lifestyle'])->assertSessionHasErrors('name');

        $this->admin()->patchJson(route('admin.blog-categories.status', $category))->assertOk()->assertJson(['is_active' => false]);

        $rows = $this->table('admin.blog-categories.data');
        $this->assertSame('Lifestyle', $rows[0]['name']);

        $this->admin()->deleteJson(route('admin.blog-categories.destroy', $category))->assertOk();
        $this->assertSoftDeleted($category);
    }

    public function test_a_category_with_posts_cannot_be_deleted(): void
    {
        $category = BlogCategory::factory()->create(['name' => 'Fashion']);
        Blog::factory()->count(2)->create(['blog_category_id' => $category->id]);

        $this->admin()->deleteJson(route('admin.blog-categories.destroy', $category))
            ->assertStatus(422)
            ->assertJsonPath('message', '“Fashion” still has 2 posts. Move or delete them first.');

        $this->assertNotSoftDeleted($category);
    }

    /* ---------- Posts ---------- */

    public function test_admin_creates_a_post_with_slug_tags_excerpt_and_author(): void
    {
        $this->admin()->get(route('admin.blogs.create'))->assertOk()->assertSee('data-upload-url', false);

        $this->admin()->post(route('admin.blogs.store'), $this->postData())->assertRedirect(route('admin.blogs.index'));

        $blog = Blog::with('tags')->firstOrFail();
        $this->assertSame('summer-style-guide', $blog->slug);
        $this->assertSame($this->admin->id, $blog->admin_id);
        $this->assertSame(BlogStatus::Published, $blog->status);
        $this->assertNotNull($blog->published_at);
        $this->assertTrue($blog->isPublished());
        $this->assertSame('Light fabrics and bright colours.', $blog->excerpt);
        $this->assertEqualsCanonicalizing(['fashion', 'summer-style'], $blog->tags->pluck('slug')->all());
        Storage::disk('public')->assertExists($blog->image);

        // Same title again: the slug gets a suffix.
        $this->admin()->post(route('admin.blogs.store'), $this->postData())->assertRedirect();
        $this->assertTrue(Blog::where('slug', 'summer-style-guide-2')->exists());
    }

    public function test_a_hand_written_slug_must_be_unique(): void
    {
        Blog::factory()->create(['slug' => 'taken-slug']);

        $this->admin()->post(route('admin.blogs.store'), $this->postData(['slug' => 'Taken Slug']))->assertSessionHasErrors('slug');
        $this->assertSame(1, Blog::count());
    }

    public function test_post_validation(): void
    {
        $inactive = BlogCategory::factory()->inactive()->create();

        $this->admin()->post(route('admin.blogs.store'), $this->postData([
            'title' => str_repeat('a', 201),
            'blog_category_id' => $inactive->id,
            'image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            'content' => '<p> </p>',
            'status' => 'archived',
            'published_at' => 'tomorrow',
        ]))->assertSessionHasErrors(['title', 'blog_category_id', 'image', 'content', 'status', 'published_at']);

        $this->admin()->post(route('admin.blogs.store'), $this->postData(['image' => null]))->assertSessionHasErrors('image');
        $this->assertSame(0, Blog::count());
    }

    public function test_script_tags_are_removed_from_the_content(): void
    {
        $this->admin()->post(route('admin.blogs.store'), $this->postData([
            'content' => '<p onclick="steal()">Hello</p><script>alert(1)</script><img src="javascript:alert(1)">',
        ]))->assertRedirect();

        $content = Blog::firstOrFail()->content;
        $this->assertStringContainsString('Hello', $content);
        $this->assertStringNotContainsString('<script', $content);
        $this->assertStringNotContainsString('onclick', $content);
        $this->assertStringNotContainsString('javascript:', $content);
    }

    public function test_the_template_quote_box_survives_but_other_classes_do_not(): void
    {
        $this->admin()->post(route('admin.blogs.store'), $this->postData([
            'content' => '<div class="wsus__blog_quot_area evil"><i class="far fa-quote-left"></i><p>Quote</p></div><p class="btn">x</p>',
        ]))->assertRedirect();

        $content = Blog::firstOrFail()->content;
        $this->assertStringContainsString('<div class="wsus__blog_quot_area"><i class="far fa-quote-left"></i>', $content);
        $this->assertStringNotContainsString('evil', $content);
        $this->assertStringNotContainsString('btn', $content);
    }

    public function test_publish_time_is_entered_in_store_time_and_can_be_scheduled(): void
    {
        config(['marketplace.timezone' => 'Asia/Kolkata']);
        $future = now('Asia/Kolkata')->addDays(3)->setTime(9, 30);

        $this->admin()->post(route('admin.blogs.store'), $this->postData(['published_at' => $future->format('Y-m-d H:i')]))->assertRedirect();

        $blog = Blog::firstOrFail();
        $this->assertTrue($blog->published_at->equalTo($future->copy()->utc()));
        $this->assertSame('Scheduled', $blog->displayState()['label']);
        $this->assertFalse($blog->isPublished());
    }

    public function test_admin_updates_and_deletes_a_post(): void
    {
        $blog = Blog::factory()->create(['title' => 'Old title', 'slug' => 'old-title']);
        $oldImage = $blog->image;
        Storage::disk('public')->put($oldImage, 'x');

        $this->admin()->get(route('admin.blogs.edit', $blog))->assertOk()->assertSee('Old title');

        $this->admin()->put(route('admin.blogs.update', $blog), $this->postData([
            'title' => 'New title',
            'slug' => 'old-title',
            'image' => UploadedFile::fake()->image('new.png', 1210, 637),
            'status' => 'draft',
        ]))->assertRedirect(route('admin.blogs.index'));

        $blog->refresh();
        $this->assertSame('New title', $blog->title);
        $this->assertSame('old-title', $blog->slug);
        $this->assertSame(BlogStatus::Draft, $blog->status);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($blog->image);

        $this->admin()->patchJson(route('admin.blogs.show-on-home', $blog))->assertOk()->assertJson(['show_on_home' => false]);

        $rows = $this->table('admin.blogs.data');
        $this->assertSame('New title', $rows[0]['title']);
        $this->assertStringContainsString('Draft', $rows[0]['status']);

        $this->admin()->deleteJson(route('admin.blogs.destroy', $blog))->assertOk();
        $this->assertSoftDeleted($blog);
    }

    public function test_editor_images_are_uploaded_through_the_image_service(): void
    {
        $url = $this->admin()->postJson(route('admin.blogs.editor-image'), ['image' => UploadedFile::fake()->image('inline.jpg')])
            ->assertOk()->json('url');

        $this->assertStringContainsString('/storage/'.Blog::CONTENT_IMAGE_FOLDER.'/', $url);
        $this->assertCount(1, Storage::disk('public')->files(Blog::CONTENT_IMAGE_FOLDER));

        $this->admin()->postJson(route('admin.blogs.editor-image'), ['image' => UploadedFile::fake()->create('x.svg', 1, 'image/svg+xml')])
            ->assertUnprocessable();
    }

    public function test_preview_redirects_to_a_signed_storefront_link(): void
    {
        $draft = Blog::factory()->draft()->create();

        $target = $this->admin()->get(route('admin.blogs.preview', $draft))->assertRedirect()->headers->get('Location');

        $this->assertStringContainsString('signature=', $target);
        $this->get($target)->assertOk()->assertSee($draft->title)->assertSee('noindex', false);
    }

    /* ---------- Comments ---------- */

    public function test_admin_moderates_comments(): void
    {
        $comment = BlogComment::factory()->pending()->create(['comment' => 'Nice read']);

        $this->admin()->get(route('admin.blog-comments.index'))->assertOk()->assertSee('blog-comments-table');
        $this->admin()->get(route('admin.dashboard'))->assertSee('Comments');

        $rows = $this->table('admin.blog-comments.data');
        $this->assertSame('Nice read', $rows[0]['comment']);
        $this->assertStringContainsString('Pending', $rows[0]['status']);

        $this->admin()->patchJson(route('admin.blog-comments.approve', $comment))->assertOk();
        $this->assertSame(CommentStatus::Approved, $comment->fresh()->status);

        $this->admin()->patchJson(route('admin.blog-comments.spam', $comment))->assertOk();
        $this->assertSame(CommentStatus::Spam, $comment->fresh()->status);

        $this->admin()->deleteJson(route('admin.blog-comments.destroy', $comment))->assertOk();
        $this->assertModelMissing($comment);
    }

    /* ---------- Access and cache ---------- */

    public function test_non_admins_are_blocked(): void
    {
        $blog = Blog::factory()->create();
        $comment = BlogComment::factory()->create(['blog_id' => $blog->id]);
        $category = $blog->category;

        $this->actingAs(User::factory()->create(), 'web');

        $this->get(route('admin.blogs.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.blog-categories.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.blog-comments.index'))->assertRedirect(route('admin.login'));
        $this->getJson(route('admin.blogs.data'))->assertUnauthorized();
        $this->postJson(route('admin.blogs.store'), [])->assertUnauthorized();
        $this->postJson(route('admin.blogs.editor-image'), [])->assertUnauthorized();
        $this->getJson(route('admin.blogs.preview', $blog))->assertUnauthorized();
        $this->deleteJson(route('admin.blogs.destroy', $blog))->assertUnauthorized();
        $this->patchJson(route('admin.blog-categories.status', $category))->assertUnauthorized();
        $this->deleteJson(route('admin.blog-categories.destroy', $category))->assertUnauthorized();
        $this->patchJson(route('admin.blog-comments.approve', $comment))->assertUnauthorized();

        $this->assertNotSoftDeleted($blog);
    }

    public function test_blog_changes_clear_the_cache(): void
    {
        $blogs = app(BlogRepository::class);
        $blog = Blog::factory()->create();

        $blogs->homePosts();
        $blogs->sidebar();
        $this->assertTrue(Cache::has(BlogRepository::HOME_CACHE_KEY));
        $this->assertTrue(Cache::has(BlogRepository::SIDEBAR_CACHE_KEY));

        $blog->update(['title' => 'Changed']);
        $this->assertFalse(Cache::has(BlogRepository::HOME_CACHE_KEY));
        $this->assertFalse(Cache::has(BlogRepository::SIDEBAR_CACHE_KEY));

        $blogs->homePosts();
        $blog->category->update(['name' => 'Renamed']);
        $this->assertFalse(Cache::has(BlogRepository::HOME_CACHE_KEY));

        $blogs->sidebar();
        BlogComment::factory()->create(['blog_id' => $blog->id]);
        $this->assertFalse(Cache::has(BlogRepository::SIDEBAR_CACHE_KEY));
    }
}
