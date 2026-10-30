<?php

namespace Tests\Feature\Search;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchFormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_desktop_and_mobile_forms_submit_to_the_shop_and_keep_the_term(): void
    {
        $html = $this->get(route('shop.index', ['search' => '  desk   lamp ']))->assertOk()->getContent();

        $this->assertSame(2, preg_match_all('#<form action="'.preg_quote(route('shop.index'), '#').'" method="GET" role="search" data-search\s+data-suggest-url="'.preg_quote(route('frontend.search.suggestions'), '#').'">#', $html));
        $this->assertSame(2, substr_count($html, 'name="search"'));
        $this->assertSame(2, substr_count($html, 'value="desk lamp"'));

        // Template markup kept: the desktop box and the mobile menu form.
        $this->assertStringContainsString('<div class="wsus__search">', $html);
        $this->assertStringContainsString('placeholder="Search..."', $html);
        $this->assertStringContainsString('placeholder="Search"', $html);
        $this->assertStringContainsString('frontend-assets/js/search.js', $html);
    }

    public function test_the_term_is_escaped_and_odd_input_does_not_break_the_header(): void
    {
        $this->get(route('home').'?search="><script>x</script>')->assertOk()
            ->assertSee('value="&quot;&gt;&lt;script&gt;x&lt;/script&gt;"', false);

        $this->get(route('home').'?search[]=a')->assertOk()->assertSee('name="search" placeholder="Search..." value=""', false);
        $this->get(route('home'))->assertOk()->assertSee('name="search" placeholder="Search..." value=""', false);
    }
}
