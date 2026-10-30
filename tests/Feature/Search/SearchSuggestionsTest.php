<?php

namespace Tests\Feature\Search;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_suggestion_json_shape(): void
    {
        $category = Category::factory()->create(['name' => 'Lighting', 'slug' => 'lighting']);
        Brand::factory()->create(['name' => 'Lumen Co', 'slug' => 'lumen-co']);
        Product::factory()->create(['name' => 'Lumen Desk Lamp', 'slug' => 'lumen-desk-lamp', 'category_id' => $category->id]);

        $this->getJson(route('frontend.search.suggestions', ['q' => 'lumen']))
            ->assertOk()
            ->assertJsonStructure([
                'term',
                'products' => [['name', 'name_parts' => [['text', 'match']], 'url', 'image', 'price', 'old_price']],
                'categories',
                'brands' => [['name', 'name_parts', 'url']],
                'popular',
                'more_url',
            ])
            ->assertJsonPath('term', 'lumen')
            ->assertJsonPath('products.0.url', route('product.show', 'lumen-desk-lamp'))
            ->assertJsonPath('products.0.name_parts.0', ['text' => 'Lumen', 'match' => true])
            ->assertJsonPath('brands.0.url', route('shop.index', ['brand' => 'lumen-co']))
            ->assertJsonPath('more_url', route('shop.index', ['search' => 'lumen']));
    }

    public function test_short_or_empty_terms_return_nothing(): void
    {
        Product::factory()->create(['name' => 'A Product']);

        $this->getJson(route('frontend.search.suggestions', ['q' => 'a']))->assertOk()->assertJsonPath('products', []);
        $this->getJson(route('frontend.search.suggestions'))->assertOk()->assertJsonPath('products', [])->assertJsonPath('term', '');
    }

    public function test_category_parameter_restricts_products(): void
    {
        $fashion = Category::factory()->create(['name' => 'Fashion']);
        $this->assertSame('fashion', $fashion->slug);
        Product::factory()->create(['name' => 'Wool Coat', 'category_id' => $fashion->id]);
        Product::factory()->create(['name' => 'Wool Rug']);

        $this->getJson(route('frontend.search.suggestions', ['q' => 'wool', 'category' => 'fashion']))
            ->assertJsonCount(1, 'products')->assertJsonPath('products.0.name', 'Wool Coat');
        $this->getJson(route('frontend.search.suggestions', ['q' => 'wool', 'category' => 'unknown']))->assertJsonCount(2, 'products');
    }

    public function test_names_are_sent_as_plain_text_never_as_markup(): void
    {
        Product::factory()->create(['name' => 'Bold <b>Lamp</b> & Co']);

        $response = $this->getJson(route('frontend.search.suggestions', ['q' => 'lamp']))->assertOk();

        $this->assertStringStartsWith('application/json', $response->headers->get('Content-Type'));
        $this->assertSame('Bold <b>Lamp</b> & Co', $response->json('products.0.name'));
        $this->assertSame(['Bold <b>', 'Lamp', '</b> & Co'], array_column($response->json('products.0.name_parts'), 'text'));
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->getJson(route('frontend.search.suggestions').'?q[]=x')->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->getJson(route('frontend.search.suggestions', ['q' => str_repeat('x', 501)]))->assertUnprocessable();
        $this->getJson(route('frontend.search.suggestions', ['q' => str_repeat('x', 150)]))->assertOk()->assertJsonPath('term', str_repeat('x', 100));
    }

    public function test_is_rate_limited_to_60_requests_a_minute_per_ip(): void
    {
        for ($i = 1; $i <= 60; $i++) {
            $this->getJson(route('frontend.search.suggestions', ['q' => 'lamp']))->assertOk();
        }

        $this->getJson(route('frontend.search.suggestions', ['q' => 'lamp']))->assertStatus(429);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])->getJson(route('frontend.search.suggestions', ['q' => 'lamp']))->assertOk();
    }
}
