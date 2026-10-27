<?php

namespace Tests\Feature\Blog;

use App\Enums\CommentStatus;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogTag;
use App\Models\User;
use App\Repositories\BlogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BlogStorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /* ---------- Visibility ---------- */

    public function test_only_published_posts_are_visible(): void
    {
        $published = Blog::factory()->create(['title' => 'Published Post']);
        $draft = Blog::factory()->draft()->create(['title' => 'Draft Post']);
        $scheduled = Blog::factory()->scheduled()->create(['title' => 'Scheduled Post']);
        $deleted = Blog::factory()->create(['title' => 'Deleted Post']);
        $deleted->delete();
        $hiddenCategory = Blog::factory()->create([
            'title' => 'Inactive Category Post',
            'blog_category_id' => BlogCategory::factory()->inactive()->create()->id,
        ]);

        $this->get(route('frontend.blog.index'))
            ->assertOk()
            ->assertSee('Published Post')
            ->assertDontSee('Draft Post')
            ->assertDontSee('Scheduled Post')
            ->assertDontSee('Deleted Post')
            ->assertDontSee('Inactive Category Post');

        $this->get(route('frontend.blog.show', $published->slug))->assertOk()->assertSee('Published Post');
        foreach ([$draft, $scheduled, $deleted, $hiddenCategory] as $hidden) {
            $this->get(route('frontend.blog.show', $hidden->slug))->assertNotFound();
        }

        // The scheduled post appears once its time has come.
        $this->travel(3)->days();
        $this->get(route('frontend.blog.show', $scheduled->slug))->assertOk();
    }

    public function test_signed_preview_shows_drafts_and_unsigned_access_is_refused(): void
    {
        $draft = Blog::factory()->draft()->create(['title' => 'Secret Draft']);

        $this->get(route('frontend.blog.preview', $draft))->assertForbidden();
        $this->get(route('frontend.blog.show', $draft->slug))->assertNotFound();

        $signed = URL::temporarySignedRoute('frontend.blog.preview', now()->addMinutes(30), ['blog' => $draft->id]);
        $this->get($signed)->assertOk()->assertSee('Secret Draft')->assertSee('noindex', false);

        $this->travel(31)->minutes();
        $this->get($signed)->assertForbidden();
    }

    /* ---------- Listing, filters, pagination ---------- */

    public function test_search_category_and_tag_filters(): void
    {
        $fashion = BlogCategory::factory()->create(['name' => 'Fashion']);
        $tech = BlogCategory::factory()->create(['name' => 'Tech']);
        $tag = BlogTag::factory()->create(['name' => 'Summer', 'slug' => 'summer']);

        $dress = Blog::factory()->create(['title' => 'Linen dress ideas', 'blog_category_id' => $fashion->id]);
        $phone = Blog::factory()->create(['title' => 'Phone review', 'blog_category_id' => $tech->id, 'content' => '<p>A dress-proof case.</p>']);
        $laptop = Blog::factory()->create(['title' => 'Laptop guide', 'blog_category_id' => $tech->id]);
        $dress->tags()->attach($tag);

        $this->get(route('frontend.blog.index', ['search' => 'dress']))
            ->assertOk()->assertSee('Linen dress ideas')->assertSee('Phone review')->assertDontSee('Laptop guide');

        $this->get(route('frontend.blog.category', $tech->slug))
            ->assertOk()->assertSee('Phone review')->assertSee('Laptop guide')->assertDontSee('Linen dress ideas');

        $this->get(route('frontend.blog.tag', 'summer'))
            ->assertOk()->assertSee('Linen dress ideas')->assertDontSee('Phone review');

        $this->get(route('frontend.blog.index', ['search' => 'nothing-like-this']))
            ->assertOk()->assertSee('No blog posts match');

        $this->get(route('frontend.blog.category', 'missing'))->assertNotFound();
        $this->get(route('frontend.blog.tag', 'missing'))->assertNotFound();
        unset($phone, $laptop);
    }

    public function test_listing_paginates_twelve_per_page(): void
    {
        $category = BlogCategory::factory()->create();
        foreach (range(1, 14) as $i) {
            Blog::factory()->create(['title' => sprintf('Post number %02d', $i), 'blog_category_id' => $category->id, 'published_at' => now()->subDays(20 - $i)]);
        }

        $first = $this->get(route('frontend.blog.index'))->assertOk();
        $first->assertSee('Post number 14')->assertSee('Post number 03')->assertDontSee('Post number 02');
        $first->assertSee('page_active', false)->assertSee('id="pagination"', false);

        $this->get(route('frontend.blog.index', ['page' => 2]))
            ->assertOk()->assertSee('Post number 02')->assertSee('Post number 01')->assertDontSee('Post number 03');
    }

    /* ---------- Home ---------- */

    public function test_home_section_shows_posts_marked_for_home_and_hides_when_none(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('recent blogs');

        Blog::factory()->hiddenFromHome()->create(['title' => 'Not On Home']);
        app(BlogRepository::class)->flush();
        $this->get(route('home'))->assertOk()->assertDontSee('recent blogs');

        $post = Blog::factory()->create(['title' => 'On The Home Page']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('recent blogs')
            ->assertSee('On The Home Page')
            ->assertDontSee('Not On Home')
            ->assertSee(route('frontend.blog.index'), false)
            ->assertSee(route('frontend.blog.show', $post->slug), false);
    }

    /* ---------- Details ---------- */

    public function test_details_page_shows_content_seo_share_related_and_sidebar(): void
    {
        $category = BlogCategory::factory()->create(['name' => 'Fashion']);
        $blog = Blog::factory()->create([
            'title' => 'Fashion tells who you are',
            'blog_category_id' => $category->id,
            'content' => '<h5>Inside</h5><p>Body text</p>',
            'seo_description' => 'Search text',
        ]);
        $blog->tags()->attach(BlogTag::factory()->create(['name' => 'Style', 'slug' => 'style']));
        $related = Blog::factory()->create(['title' => 'Related Fashion Post', 'blog_category_id' => $category->id]);
        Blog::factory()->create(['title' => 'Other Category Post']);

        $this->get(route('frontend.blog.show', $blog->slug))
            ->assertOk()
            ->assertSee('<h1>Fashion tells who you are</h1>', false)
            ->assertSee('<h5>Inside</h5><p>Body text</p>', false)
            ->assertSee('by '.$blog->author->name)
            ->assertSee('<meta name="description" content="Search text">', false)
            ->assertSee('<link rel="canonical" href="'.route('frontend.blog.show', $blog->slug).'">', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('https://www.facebook.com/sharer/sharer.php?u='.urlencode(route('frontend.blog.show', $blog->slug)), false)
            ->assertSee('Related Fashion Post')
            ->assertSee(route('frontend.blog.tag', 'style'), false)
            ->assertSee(route('frontend.blog.category', $category->slug), false)
            ->assertSee('popular tags');

        unset($related);
    }

    public function test_titles_and_comments_are_escaped(): void
    {
        $blog = Blog::factory()->create(['title' => 'Tips <b>bold</b>']);
        BlogComment::factory()->create(['blog_id' => $blog->id, 'comment' => '<script>alert(1)</script>']);

        $this->get(route('frontend.blog.show', $blog->slug))
            ->assertOk()
            ->assertSee('Tips &lt;b&gt;bold&lt;/b&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_views_are_counted_once_per_session_and_not_for_bots(): void
    {
        $blog = Blog::factory()->create();

        $this->get(route('frontend.blog.show', $blog->slug))->assertOk();
        $this->get(route('frontend.blog.show', $blog->slug))->assertOk();
        $this->assertSame(1, $blog->fresh()->views);

        // A new session counts again; a crawler never does.
        $this->flushSession();
        $this->get(route('frontend.blog.show', $blog->slug));
        $this->assertSame(2, $blog->fresh()->views);

        $this->flushSession();
        $this->withHeader('User-Agent', 'Googlebot/2.1')->get(route('frontend.blog.show', $blog->slug));
        $this->assertSame(2, $blog->fresh()->views);
    }

    /* ---------- Comments ---------- */

    public function test_guest_comments_start_pending_and_only_approved_ones_show(): void
    {
        $blog = Blog::factory()->create();
        BlogComment::factory()->create(['blog_id' => $blog->id, 'comment' => 'Approved words']);
        BlogComment::factory()->pending()->create(['blog_id' => $blog->id, 'comment' => 'Pending words']);
        BlogComment::factory()->spam()->create(['blog_id' => $blog->id, 'comment' => 'Spam words']);

        $this->get(route('frontend.blog.show', $blog->slug))
            ->assertSee('Approved words')->assertDontSee('Pending words')->assertDontSee('Spam words')
            ->assertSee('comment <span>01</span>', false);

        $this->post(route('frontend.blog.comments.store', $blog->slug), [
            'name' => 'Asha', 'email' => 'asha@example.com', 'comment' => 'Great tips!',
        ])->assertRedirect(route('frontend.blog.show', $blog->slug).'#comments')->assertSessionHas('success');

        $comment = BlogComment::where('comment', 'Great tips!')->firstOrFail();
        $this->assertSame(CommentStatus::Pending, $comment->status);
        $this->assertSame('Asha', $comment->name);
        $this->get(route('frontend.blog.show', $blog->slug))->assertDontSee('Great tips!');
    }

    public function test_comment_validation_honeypot_and_rate_limit(): void
    {
        $blog = Blog::factory()->create();
        $url = route('frontend.blog.comments.store', $blog->slug);

        $this->post($url, ['name' => '', 'email' => 'nope', 'comment' => ''])->assertSessionHasErrors(['name', 'email', 'comment']);

        // Honeypot: looks like a success, nothing stored.
        $this->post($url, ['name' => 'Bot', 'email' => 'bot@example.com', 'comment' => 'Buy now', 'website' => 'http://spam.test'])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame(0, BlogComment::count());

        // 5 per minute per visitor: the two posts above count, so three more are allowed.
        foreach (range(1, 3) as $i) {
            $this->post($url, ['name' => 'Ravi', 'email' => 'ravi@example.com', 'comment' => 'Comment '.$i])->assertRedirect();
        }
        $this->post($url, ['name' => 'Ravi', 'email' => 'ravi@example.com', 'comment' => 'One too many'])->assertStatus(429);
        $this->assertSame(3, BlogComment::count());
    }

    public function test_replies_are_for_signed_in_users_on_approved_top_level_comments(): void
    {
        $blog = Blog::factory()->create();
        $approved = BlogComment::factory()->create(['blog_id' => $blog->id, 'comment' => 'Top level']);
        $pending = BlogComment::factory()->pending()->create(['blog_id' => $blog->id]);
        $url = route('frontend.blog.comments.store', $blog->slug);

        $this->post($url, ['name' => 'Guest', 'email' => 'g@example.com', 'comment' => 'Reply', 'parent_id' => $approved->id])
            ->assertSessionHasErrors('parent_id');

        $user = User::factory()->create(['name' => 'Meera']);
        $this->actingAs($user, 'web');

        $this->post($url, ['comment' => 'Reply to pending', 'parent_id' => $pending->id])->assertSessionHasErrors('parent_id');

        $this->post($url, ['comment' => 'Thanks for this', 'parent_id' => $approved->id])->assertSessionHasNoErrors()->assertRedirect();
        $reply = BlogComment::where('comment', 'Thanks for this')->firstOrFail();
        $this->assertSame($approved->id, $reply->parent_id);
        $this->assertSame('Meera', $reply->name);
        $this->assertSame($user->email, $reply->email);
        $this->assertSame(CommentStatus::Pending, $reply->status);

        $reply->update(['status' => CommentStatus::Approved]);
        $this->get(route('frontend.blog.show', $blog->slug))->assertSee('Thanks for this')->assertSee('wsus__com_replay', false);
    }

    public function test_comments_on_hidden_posts_are_refused(): void
    {
        $draft = Blog::factory()->draft()->create();

        $this->post(route('frontend.blog.comments.store', $draft->slug), ['name' => 'A', 'email' => 'a@example.com', 'comment' => 'Hi'])
            ->assertNotFound();
        $this->assertSame(0, BlogComment::count());
    }
}
