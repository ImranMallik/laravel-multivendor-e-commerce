<?php

namespace Tests\Feature\Product;

use App\Enums\AttributeType;
use App\Models\Admin;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AttributeAdminTest extends TestCase
{
    use BuildsVariants, RefreshDatabase;

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
    private function data(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Material',
            'type' => 'select',
            'sort_order' => 3,
            'is_active' => 1,
            'values' => [
                ['id' => '', 'value' => 'Cotton', 'is_active' => 1],
                ['id' => '', 'value' => 'Wool', 'is_active' => 1],
            ],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function fetch(array $extra = []): TestResponse
    {
        $this->app->forgetInstance('datatables.request');

        $columns = array_map(fn (string $name) => ['data' => $name, 'name' => $name, 'searchable' => 'true', 'orderable' => 'true'],
            ['DT_RowIndex', 'name', 'type', 'values_list', 'sort_order', 'is_active', 'action']);

        return $this->admin()->getJson(route('admin.attributes.data', array_merge([
            'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => ''], 'columns' => $columns,
        ], $extra)));
    }

    public function test_only_admins_can_manage_attributes(): void
    {
        $attribute = Attribute::factory()->create();

        $this->get(route('admin.attributes.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.attributes.store'), $this->data())->assertRedirect(route('admin.login'));
        $this->deleteJson(route('admin.attributes.destroy', $attribute))->assertUnauthorized();

        // Vendors (web guard) cannot reach the admin attribute routes.
        $this->actingAs(Vendor::factory()->approved()->create()->user, 'web');
        $this->get(route('admin.attributes.create'))->assertRedirect(route('admin.login'));
        $this->putJson(route('admin.attributes.update', $attribute), $this->data())->assertUnauthorized();
        $this->patchJson(route('admin.attributes.status', $attribute))->assertUnauthorized();

        $this->assertSame(1, Attribute::count());
        $this->assertSame(0, AttributeValue::count());
    }

    public function test_pages_render_with_the_sidebar_item(): void
    {
        $attribute = Attribute::factory()->create();
        AttributeValue::factory()->create(['attribute_id' => $attribute->id]);

        $this->admin()->get(route('admin.attributes.index'))
            ->assertOk()
            ->assertSee('id="attributes-table"', false)
            ->assertSee('Add Attribute')
            ->assertSee(route('admin.attributes.index'), false);

        $html = $this->admin()->get(route('admin.attributes.create'))
            ->assertOk()
            ->assertSee('data-attribute-form', false)
            ->assertSee('name="values[0][value]"', false)
            ->assertSee('type="color"', false)
            ->getContent();
        $this->assertStringNotContainsString('frontend-assets', $html);

        $this->admin()->get(route('admin.attributes.edit', $attribute))->assertOk()->assertSee('value="'.$attribute->name.'"', false);
    }

    public function test_admin_creates_an_attribute_with_values_in_order(): void
    {
        $this->admin()->post(route('admin.attributes.store'), $this->data())
            ->assertRedirect(route('admin.attributes.index'))
            ->assertSessionHas('success');

        $attribute = Attribute::firstOrFail();

        $this->assertSame('material', $attribute->slug);
        $this->assertSame(AttributeType::Select, $attribute->type);
        $this->assertSame(3, $attribute->sort_order);
        $this->assertSame(['Cotton', 'Wool'], $attribute->values->pluck('value')->all());
        $this->assertSame([0, 1], $attribute->values->pluck('sort_order')->all());
        $this->assertNull($attribute->values->first()->color_code);
    }

    public function test_color_attributes_need_a_hex_color_for_every_value(): void
    {
        $this->admin()->post(route('admin.attributes.store'), $this->data(['name' => 'Shade', 'type' => 'color']))
            ->assertSessionHasErrors('values.0.color_code');

        $this->admin()->post(route('admin.attributes.store'), $this->data(['name' => 'Shade', 'type' => 'color', 'values' => [
            ['id' => '', 'value' => 'Teal', 'color_code' => 'teal', 'is_active' => 1],
        ]]))->assertSessionHasErrors('values.0.color_code');

        $this->admin()->post(route('admin.attributes.store'), $this->data(['name' => 'Shade', 'type' => 'color', 'values' => [
            ['id' => '', 'value' => 'Teal', 'color_code' => '#008080', 'is_active' => 1],
        ]]))->assertSessionHasNoErrors();

        $this->assertSame('#008080', AttributeValue::firstOrFail()->color_code);
    }

    public function test_attribute_validation(): void
    {
        Attribute::factory()->create(['name' => 'Taken']);

        $this->admin()->post(route('admin.attributes.store'), $this->data(['name' => '']))->assertSessionHasErrors('name');
        $this->admin()->post(route('admin.attributes.store'), $this->data(['name' => 'Taken']))->assertSessionHasErrors('name');
        $this->admin()->post(route('admin.attributes.store'), $this->data(['type' => 'bogus']))->assertSessionHasErrors('type');
        $this->admin()->post(route('admin.attributes.store'), $this->data(['values' => []]))->assertSessionHasErrors('values');
        $this->admin()->post(route('admin.attributes.store'), $this->data(['values' => [
            ['id' => '', 'value' => 'Cotton'], ['id' => '', 'value' => 'cotton'],
        ]]))->assertSessionHasErrors('values.1.value');

        $this->assertSame(1, Attribute::count());
    }

    public function test_editing_syncs_reorders_and_removes_unused_values(): void
    {
        $this->admin()->post(route('admin.attributes.store'), $this->data());
        $attribute = Attribute::firstOrFail();
        [$cotton, $wool] = $attribute->values->all();

        $this->admin()->put(route('admin.attributes.update', $attribute), $this->data([
            'name' => 'Fabric',
            'values' => [
                ['id' => $wool->id, 'value' => 'Merino Wool', 'is_active' => 0],   // renamed, deactivated, now first
                ['id' => '', 'value' => 'Linen', 'is_active' => 1],                   // new
            ],                                                                      // cotton is removed (unused)
        ]))->assertSessionHasNoErrors();

        $attribute->refresh();
        $this->assertSame('Fabric', $attribute->name);
        $this->assertSame(['Merino Wool', 'Linen'], $attribute->values->pluck('value')->all());
        $this->assertSame($wool->id, $attribute->values->first()->id);
        $this->assertFalse($attribute->values->first()->is_active);
        $this->assertDatabaseMissing('attribute_values', ['id' => $cotton->id]);
    }

    public function test_a_value_used_by_a_variant_cannot_be_removed_or_deleted(): void
    {
        $this->attributes();
        $product = Product::factory()->create(['has_variants' => true]);
        $variant = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'sku' => 'V-1']);
        $variant->values()->sync([$this->red->id, $this->small->id]);

        // Removing "Red" (used) from the Color attribute is refused; the attribute stays untouched.
        $this->admin()->put(route('admin.attributes.update', $this->color), [
            'name' => 'Colour renamed', 'type' => 'color', 'is_active' => 1,
            'values' => [['id' => $this->blue->id, 'value' => 'Blue', 'color_code' => '#0000ff', 'is_active' => 1]],
        ])->assertSessionHas('error');

        $this->assertDatabaseHas('attribute_values', ['id' => $this->red->id]);
        $this->assertSame('Color', $this->color->fresh()->name, 'the whole save is rolled back');

        // Deleting an attribute with a used value is refused; one without is deleted.
        $this->admin()->deleteJson(route('admin.attributes.destroy', $this->color))->assertStatus(422);
        $this->assertDatabaseHas('attributes', ['id' => $this->color->id]);

        $unused = Attribute::factory()->create();
        AttributeValue::factory()->create(['attribute_id' => $unused->id]);
        $this->admin()->deleteJson(route('admin.attributes.destroy', $unused))->assertOk()->assertJson(['message' => 'Attribute deleted.']);
        $this->assertDatabaseMissing('attributes', ['id' => $unused->id]);
        $this->assertDatabaseMissing('attribute_values', ['attribute_id' => $unused->id]);

        // Soft-deleted variants still reference their values.
        $variant->delete();
        $this->assertTrue($this->red->isInUse());
        $this->admin()->deleteJson(route('admin.attributes.destroy', $this->color))->assertStatus(422);
    }

    public function test_status_toggle_returns_json(): void
    {
        $attribute = Attribute::factory()->create(['is_active' => true]);

        $this->admin()->patchJson(route('admin.attributes.status', $attribute))->assertOk()->assertJson(['is_active' => false]);
        $this->assertFalse($attribute->fresh()->is_active);
    }

    public function test_datatable_lists_search_and_values(): void
    {
        $this->attributes();
        Attribute::factory()->create(['name' => 'Material']);

        $response = $this->fetch()->assertOk()->assertJsonPath('recordsTotal', 3);
        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertSame(['Material', 'Color', 'Size'], $names, 'ordered by sort order, then name');

        $color = collect($response->json('data'))->firstWhere('name', 'Color');
        $this->assertStringContainsString('Red', $color['values_list']);
        $this->assertStringContainsString('data-swatch="#ff0000"', $color['values_list']);
        $this->assertStringContainsString('Color swatches', $color['type']);
        $this->assertStringContainsString(route('admin.attributes.status', $this->color), $color['is_active']);

        $this->assertSame(['Size'], collect($this->fetch(['search' => ['value' => 'siz']])->json('data'))->pluck('name')->all());
    }

    public function test_vendors_only_choose_active_attributes_and_values(): void
    {
        $this->attributes();
        $vendor = Vendor::factory()->approved()->create();
        $this->blue->update(['is_active' => false]);
        Attribute::factory()->inactive()->create(['name' => 'Hidden Attr'])->values()->create(['value' => 'HiddenValue', 'sort_order' => 0]);

        $html = $this->actingAs($vendor->user, 'web')->get(route('vendor.products.create'))->assertOk()->getContent();

        $this->assertStringContainsString('data-attribute-name="Color"', $html);
        $this->assertStringContainsString('data-value-label="Red"', $html);
        $this->assertStringNotContainsString('data-value-label="Blue"', $html);
        $this->assertStringNotContainsString('Hidden Attr', $html);
        $this->assertStringNotContainsString('HiddenValue', $html);

        // Vendors have no access to the attribute screens.
        $this->actingAs($vendor->user, 'web')->get('/vendor/attributes')->assertNotFound();
        $this->assertNotNull(User::find($vendor->user_id));
    }
}
