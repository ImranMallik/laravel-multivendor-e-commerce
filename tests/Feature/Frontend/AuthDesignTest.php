<?php

namespace Tests\Feature\Frontend;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Structure checks for the auth UI. Visual checks (spacing, widths at 375/768/desktop)
 * are covered by the rules in public/frontend-assets/css/auth.css.
 */
class AuthDesignTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function authUrls(): array
    {
        return [
            'login' => ['/login'],
            'register' => ['/register'],
            'register vendor' => ['/register/vendor'],
            'forgot' => ['/forgot-password'],
        ];
    }

    /**
     * @dataProvider authUrls
     */
    public function test_every_auth_page_uses_the_same_card_container(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $this->assertStringContainsString('col-xl-5 col-lg-6 col-md-8 m-auto', $html);
        $this->assertSame(1, substr_count($html, 'class="wsus__login_reg_area"'));
        $this->assertStringContainsString('frontend-assets/css/auth.css', $html);
        $this->assertStringContainsString('frontend-assets/js/auth.js', $html);
    }

    /**
     * @dataProvider authUrls
     */
    public function test_auth_pages_have_no_inline_styles(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $section = substr($html, (int) strpos($html, 'id="wsus__login_register"'));
        $section = substr($section, 0, (int) strpos($section, '<footer'));

        $this->assertStringNotContainsString('style="', $section);
    }

    /**
     * @dataProvider authUrls
     */
    public function test_inputs_have_labels_and_autocomplete(string $url): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        preg_match_all('#<input[^>]*name="(?!_token|remember|token)([^"]+)"[^>]*>#', $html, $inputs);

        foreach ($inputs[0] as $index => $tag) {
            $name = $inputs[1][$index];

            $this->assertStringContainsString('id="auth-'.$name.'"', $tag, $name);
            $this->assertStringContainsString('for="auth-'.$name.'"', $html, "label for {$name}");
            $this->assertStringContainsString('autocomplete=', $tag, "autocomplete for {$name}");
        }
    }

    public function test_autocomplete_values(): void
    {
        $login = $this->get('/login')->getContent();
        $this->assertStringContainsString('autocomplete="email"', $login);
        $this->assertStringContainsString('autocomplete="current-password"', $login);

        $register = $this->get('/register')->getContent();
        $this->assertStringContainsString('autocomplete="new-password"', $register);
        $this->assertStringContainsString('autocomplete="tel"', $register);
    }

    public function test_password_fields_get_a_show_hide_toggle(): void
    {
        $this->assertSame(1, substr_count($this->get('/login')->getContent(), 'class="auth-eye"'));
        $this->assertSame(2, substr_count($this->get('/register')->getContent(), 'class="auth-eye"'));
    }

    public function test_forgot_password_is_spelled_correctly_and_links_to_the_reset_request_page(): void
    {
        $this->get('/login')
            ->assertSee('Forgot password?')
            ->assertDontSee('Forget Password')
            ->assertSee('href="'.route('password.request').'"', false);
    }

    public function test_signup_is_one_form_and_vendor_fields_are_disabled_for_customers(): void
    {
        $customer = $this->get('/register')->getContent();

        $this->assertSame(1, substr_count($customer, 'data-auth-form'));
        $this->assertStringContainsString('action="'.route('register.store').'"', $customer);
        $this->assertStringContainsString('d-none', $customer);
        $this->assertMatchesRegularExpression('#name="shop_name"[^>]*disabled#', $customer);
        $this->assertStringContainsString('Create Customer Account', $customer);
    }

    public function test_vendor_tab_shows_shop_fields_and_seller_button(): void
    {
        $vendor = $this->get('/register/vendor')->getContent();

        $this->assertStringContainsString('action="'.route('register.vendor.store').'"', $vendor);
        $this->assertDoesNotMatchRegularExpression('#name="shop_name"[^>]*disabled#', $vendor);
        $this->assertStringContainsString('name="shop_phone"', $vendor);
        $this->assertStringContainsString('name="address"', $vendor);
        $this->assertStringContainsString('Create Seller Account', $vendor);
        $this->assertMatchesRegularExpression('#data-auth-mode="vendor"[^>]*class="active"|class="active"[^>]*data-auth-mode="vendor"#', $vendor);
    }

    public function test_validation_errors_show_under_the_field_and_reopen_the_vendor_tab(): void
    {
        $response = $this->from('/register/vendor')->post(route('register.vendor.store'), [
            'name' => 'Vic',
            'email' => 'not-an-email',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'shop_name' => 'Vic Shop',
            'address' => '1 Street',
        ]);

        // A failed validation goes back to the vendor tab URL, so the same sub-tab reopens.
        $response->assertRedirect('/register/vendor')->assertSessionHasErrors('email');

        $html = $this->followingRedirects()->from('/register/vendor')->post(route('register.vendor.store'), [
            'name' => 'Vic', 'email' => 'not-an-email', 'password' => 'Secret123', 'password_confirmation' => 'Secret123',
            'shop_name' => 'Vic Shop', 'address' => '1 Street',
        ])->getContent();

        $this->assertStringContainsString('class="auth-error"', $html);
        $this->assertStringContainsString('class="is-invalid"', $html);
        // Typed values survive the round trip.
        $this->assertStringContainsString('value="Vic Shop"', $html);
        $this->assertStringContainsString('value="1 Street"', $html);
        // The inline messages replace the global popup for validation errors.
        $this->assertStringNotContainsString('Swal.fire({', $html);
    }

    public function test_login_error_shows_under_the_email_field(): void
    {
        $user = User::factory()->create();

        $html = $this->followingRedirects()->from('/login')
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])
            ->getContent();

        $this->assertStringContainsString('class="auth-error"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('value="'.$user->email.'"', $html);
    }

    public function test_status_flash_still_uses_the_single_sweetalert(): void
    {
        $html = $this->withSession(['status' => 'We have emailed your password reset link.'])
            ->get('/forgot-password')->getContent();

        $this->assertSame(1, substr_count($html, 'Swal.fire({'));
    }
}
