<?php

namespace Tests\Feature\Frontend;

use App\Models\Admin;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  string|array<int, string>  $html
     */
    private function assertAssetsExist(string $html, string $prefix): void
    {
        preg_match_all('#'.$prefix.'/[^"\'\s)?]+#', $html, $matches);

        $this->assertNotEmpty($matches[0], "Page references no {$prefix}.");

        foreach (array_unique($matches[0]) as $asset) {
            $this->assertFileExists(public_path($asset), "Missing asset: {$asset}");
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function guestUrls(): array
    {
        return [
            'home' => ['/'],
            'login' => ['/login'],
            'register' => ['/register'],
            'register vendor' => ['/register/vendor'],
            'forgot password' => ['/forgot-password'],
        ];
    }

    /**
     * @dataProvider guestUrls
     */
    public function test_guest_pages_load_without_asset_404s_or_admin_assets(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $this->assertAssetsExist($html, 'frontend-assets');
        $this->assertStringNotContainsString('admin-assets', $html);
        $this->assertSame(1, substr_count($html, 'sweetalert2.all.min.js'));
        $this->assertSame(1, substr_count($html, 'frontend-confirm.js'));
    }

    public function test_dashboard_pages_load_without_asset_404s_or_admin_assets(): void
    {
        $customer = User::factory()->create();
        $vendor = Vendor::factory()->approved()->create();

        $pages = [
            [$customer, ['account.dashboard', 'account.orders', 'account.wishlist', 'account.profile.edit']],
            [$vendor->user, ['vendor.dashboard', 'vendor.shop.edit', 'vendor.profile.edit', 'vendor.products', 'vendor.orders', 'vendor.earnings', 'vendor.withdrawals']],
        ];

        foreach ($pages as [$user, $routes]) {
            foreach ($routes as $route) {
                $html = $this->actingAs($user)->get(route($route))->assertOk()->getContent();

                $this->assertAssetsExist($html, 'frontend-assets');
                $this->assertStringNotContainsString('admin-assets', $html);
                $this->assertSame(1, substr_count($html, 'sweetalert2.all.min.js'), $route);
                $this->assertStringContainsString('data-confirm-form="logout-form"', $html);
            }
        }
    }

    public function test_pending_vendor_status_page_loads_assets(): void
    {
        $vendor = Vendor::factory()->create();

        $html = $this->actingAs($vendor->user)->get(route('vendor.status'))->assertOk()->getContent();

        $this->assertAssetsExist($html, 'frontend-assets');
    }

    public function test_header_menu_shows_login_and_register_to_guests_and_logout_to_users(): void
    {
        $this->get('/')->assertSee(route('login'), false)->assertSee(route('register'), false)->assertDontSee('site-logout-form');

        $this->actingAs(User::factory()->create(['name' => 'Casey']))->get('/')
            ->assertSee(route('account.dashboard'), false)
            ->assertSee('id="site-logout-form"', false)
            ->assertSee('data-confirm-form="site-logout-form"', false);
    }

    public function test_unknown_storefront_url_shows_branded_404(): void
    {
        $this->get('/no/such/page')->assertNotFound()->assertSee('Something Went Wrong Here');
    }

    public function test_unknown_admin_url_does_not_use_storefront_404(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->get('/admin/no-such-page')->assertNotFound()->assertDontSee('Something Went Wrong Here');
    }

    public function test_admin_pages_do_not_load_frontend_assets(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        foreach (['admin.dashboard', 'admin.vendors.index'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('frontend-assets');
        }
    }
}
