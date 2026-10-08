<?php

namespace Tests\Feature\Frontend;

use App\Models\Slider;
use App\Repositories\SliderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeSliderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function storedSlider(array $attributes = []): Slider
    {
        $slider = Slider::factory()->make($attributes);
        Storage::disk('public')->put($slider->image, 'fake-image');
        $slider->save();

        return $slider;
    }

    public function test_active_sliders_appear_on_the_home_page_with_all_their_fields(): void
    {
        $slider = $this->storedSlider([
            'top_text' => 'kicker line',
            'title' => 'Big Sale Title',
            'offer_text' => 'save 40 percent',
            'button_text' => 'grab it',
            'button_url' => 'https://example.com/grab',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('id="wsus__banner"', false)
            ->assertSee('class="row banner_slider"', false)
            ->assertSee('<h3>kicker line</h3>', false)
            ->assertSee('<h1>Big Sale Title</h1>', false)
            ->assertSee('<h6>save 40 percent</h6>', false)
            ->assertSee('<a class="common_btn" href="https://example.com/grab">grab it</a>', false)
            ->assertSee('storage/'.$slider->image, false);
    }

    public function test_inactive_and_deleted_sliders_do_not_appear(): void
    {
        $this->storedSlider(['title' => 'Visible Slide']);
        $this->storedSlider(['title' => 'Hidden Inactive', 'is_active' => false]);
        $this->storedSlider(['title' => 'Hidden Deleted'])->delete();

        $this->get('/')
            ->assertSee('Visible Slide')
            ->assertDontSee('Hidden Inactive')
            ->assertDontSee('Hidden Deleted');
    }

    public function test_the_whole_section_is_hidden_when_there_are_no_active_sliders(): void
    {
        $this->storedSlider(['is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('id="wsus__banner"', false)
            ->assertDontSee('banner_slider');
    }

    public function test_slides_follow_the_sort_order(): void
    {
        $this->storedSlider(['title' => 'Slide C', 'sort_order' => 30]);
        $this->storedSlider(['title' => 'Slide A', 'sort_order' => 10]);
        $this->storedSlider(['title' => 'Slide B', 'sort_order' => 20]);

        $this->get('/')->assertSeeInOrder(['Slide A', 'Slide B', 'Slide C']);
    }

    public function test_the_button_shows_only_when_both_text_and_link_exist(): void
    {
        $this->storedSlider(['title' => 'Text Only', 'button_text' => 'orphan text', 'button_url' => null]);
        $this->storedSlider(['title' => 'Link Only', 'button_text' => null, 'button_url' => 'https://orphan.test/link']);
        $this->storedSlider(['title' => 'Both', 'button_text' => 'complete', 'button_url' => 'https://ok.test/go']);

        $this->get('/')
            ->assertDontSee('orphan text')
            ->assertDontSee('https://orphan.test/link', false)
            ->assertSee('<a class="common_btn" href="https://ok.test/go">complete</a>', false);
    }

    public function test_empty_optional_texts_are_not_rendered_as_empty_tags(): void
    {
        $this->storedSlider(['title' => 'Bare Title', 'top_text' => null, 'offer_text' => null, 'button_text' => null, 'button_url' => null]);

        $html = $this->get('/')->getContent();
        $slide = substr($html, strpos($html, 'wsus__single_slider_text'), 200);

        $this->assertStringContainsString('<h1>Bare Title</h1>', $slide);
        $this->assertStringNotContainsString('<h3></h3>', $html);
        $this->assertStringNotContainsString('<h6></h6>', $html);
    }

    public function test_slider_text_is_escaped(): void
    {
        $this->storedSlider([
            'title' => '<script>alert("x")</script>',
            'top_text' => '<b>bold</b>',
            'button_text' => '<i>go</i>',
        ]);

        $this->get('/')
            ->assertDontSee('<script>alert("x")</script>', false)
            ->assertSee('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<b>bold</b>', false);
    }

    public function test_a_single_slide_renders_one_slide(): void
    {
        $this->storedSlider(['title' => 'Only Slide']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'class="wsus__single_slider"'));
    }

    public function test_a_missing_image_file_falls_back_to_the_placeholder(): void
    {
        Slider::factory()->create(['title' => 'Broken Image', 'image' => 'sliders/gone.jpg']);

        $this->get('/')->assertSee('frontend-assets/images/slider_1.jpg', false);
    }

    public function test_sliders_are_cached_and_not_queried_on_every_request(): void
    {
        $this->storedSlider(['title' => 'Cached Slide']);

        $this->get('/')->assertSee('Cached Slide');
        $this->assertTrue(Cache::has(SliderRepository::CACHE_KEY));

        DB::enableQueryLog();
        $this->get('/')->assertSee('Cached Slide');
        $sliderQueries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'sliders'))->count();
        DB::disableQueryLog();

        $this->assertSame(0, $sliderQueries);
    }

    public function test_changes_show_up_immediately_because_the_cache_is_cleared(): void
    {
        $slider = $this->storedSlider(['title' => 'Before Change']);

        $this->get('/')->assertSee('Before Change');

        $slider->update(['title' => 'After Change']);
        $this->get('/')->assertSee('After Change')->assertDontSee('Before Change');

        $slider->update(['is_active' => false]);
        $this->get('/')->assertDontSee('After Change');

        $slider->update(['is_active' => true]);
        $slider->delete();
        $this->get('/')->assertDontSee('After Change');
    }

    public function test_the_banner_section_does_not_query_inside_blade(): void
    {
        $this->storedSlider();

        // The controller supplies $sliders; the include only loops over it.
        $this->assertStringNotContainsString('Slider::', file_get_contents(resource_path('views/frontend/home/section/banner-slider.blade.php')));
    }
}
