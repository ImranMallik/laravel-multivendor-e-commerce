<?php

namespace Tests\Unit;

use App\Enums\ProductSort;
use App\Support\SearchTerm;
use PHPUnit\Framework\TestCase;

class SearchTermTest extends TestCase
{
    public function test_input_is_trimmed_collapsed_and_limited(): void
    {
        $this->assertSame('red linen shirt', SearchTerm::from("  red \t linen\n\n shirt  ")->value);
        $this->assertSame(100, SearchTerm::from(str_repeat('a', 150))->length());
        $this->assertSame(str_repeat('a', 99), SearchTerm::from(str_repeat('a', 99).' tail')->value);
        $this->assertSame('Lamp', (string) SearchTerm::from('Lamp'));
    }

    public function test_empty_input_means_no_search(): void
    {
        $this->assertTrue(SearchTerm::from(null)->isEmpty());
        $this->assertTrue(SearchTerm::from("   \n ")->isEmpty());
        $this->assertFalse(SearchTerm::from('0')->isEmpty());
    }

    public function test_like_wildcards_and_the_escape_character_are_escaped(): void
    {
        $term = SearchTerm::from('50%_off! \\');

        $this->assertSame('50!%!_off!! \\', $term->escaped());
        $this->assertSame('%50!%!_off!! \\%', $term->contains());
        $this->assertSame('50!%!_off!! \\%', $term->startsWith());
    }

    public function test_normalized_form_is_lowercase(): void
    {
        $this->assertSame('smart phone', SearchTerm::from('  Smart   PHONE ')->normalized());
    }

    public function test_sort_defaults_and_options_depend_on_searching(): void
    {
        $this->assertSame(ProductSort::Relevance, ProductSort::default(true));
        $this->assertSame(ProductSort::Newest, ProductSort::default(false));
        $this->assertNotContains(ProductSort::Relevance, ProductSort::options(false));
        $this->assertCount(4, ProductSort::options(true));
    }
}
