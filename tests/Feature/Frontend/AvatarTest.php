<?php

namespace Tests\Feature\Frontend;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * Customer and vendor profiles share the same form, request and actions.
     *
     * @return array<string, array{string, string, string}>
     */
    public static function profiles(): array
    {
        return [
            'customer' => ['customer', 'account.profile.update', 'account.profile.avatar.destroy'],
            'vendor' => ['vendor', 'vendor.profile.update', 'vendor.profile.avatar.destroy'],
        ];
    }

    private function makeUser(string $kind): User
    {
        return $kind === 'vendor'
            ? Vendor::factory()->approved()->create()->user
            : User::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function data(User $user, array $extra = []): array
    {
        return array_merge(['name' => $user->name, 'email' => $user->email, 'phone' => $user->phone], $extra);
    }

    /**
     * @dataProvider profiles
     */
    public function test_user_can_upload_an_avatar(string $kind, string $update): void
    {
        $user = $this->makeUser($kind);

        $this->actingAs($user, 'web')->put(route($update), $this->data($user, [
            'avatar' => UploadedFile::fake()->image('me.jpg'),
        ]))->assertSessionHasNoErrors()->assertSessionHas('success');

        $path = $user->fresh()->avatar;

        $this->assertStringStartsWith('users/avatars/', $path);
        $this->assertStringNotContainsString('me', basename($path));
        $this->assertStringNotContainsString('public/', $path);
        Storage::disk('public')->assertExists($path);
    }

    /**
     * @dataProvider profiles
     */
    public function test_replacing_the_avatar_deletes_the_old_file(string $kind, string $update): void
    {
        $user = $this->makeUser($kind);

        $this->actingAs($user, 'web')->put(route($update), $this->data($user, ['avatar' => UploadedFile::fake()->image('a.jpg')]));
        $old = $user->fresh()->avatar;

        $this->actingAs($user->fresh(), 'web')->put(route($update), $this->data($user, ['avatar' => UploadedFile::fake()->image('b.png')]));
        $new = $user->fresh()->avatar;

        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
    }

    /**
     * @dataProvider profiles
     */
    public function test_saving_without_a_file_keeps_the_current_avatar(string $kind, string $update): void
    {
        $user = $this->makeUser($kind);

        $this->actingAs($user, 'web')->put(route($update), $this->data($user, ['avatar' => UploadedFile::fake()->image('a.jpg')]));
        $path = $user->fresh()->avatar;

        $this->actingAs($user->fresh(), 'web')->put(route($update), $this->data($user, ['name' => 'Renamed']))
            ->assertSessionHasNoErrors();

        $this->assertSame($path, $user->fresh()->avatar);
        $this->assertSame('Renamed', $user->fresh()->name);
        Storage::disk('public')->assertExists($path);
    }

    /**
     * @dataProvider profiles
     */
    public function test_invalid_files_are_rejected(string $kind, string $update): void
    {
        $user = $this->makeUser($kind);

        foreach ([
            UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('script.svg', 10, 'image/svg+xml'),
            UploadedFile::fake()->image('big.jpg')->size(3000),
        ] as $file) {
            $this->actingAs($user, 'web')->put(route($update), $this->data($user, ['avatar' => $file]))
                ->assertSessionHasErrors('avatar');
        }

        $this->assertNull($user->fresh()->avatar);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /**
     * @dataProvider profiles
     */
    public function test_a_path_string_cannot_be_injected_as_the_avatar(string $kind, string $update): void
    {
        $user = $this->makeUser($kind);

        $this->actingAs($user, 'web')->put(route($update), $this->data($user, ['avatar' => 'users/avatars/someone-elses.jpg']))
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar);
    }

    /**
     * @dataProvider profiles
     */
    public function test_user_can_remove_the_avatar(string $kind, string $update, string $destroy): void
    {
        $user = $this->makeUser($kind);

        $this->actingAs($user, 'web')->put(route($update), $this->data($user, ['avatar' => UploadedFile::fake()->image('a.jpg')]));
        $path = $user->fresh()->avatar;

        $this->actingAs($user->fresh(), 'web')->delete(route($destroy))->assertSessionHas('success');

        $this->assertNull($user->fresh()->avatar);
        Storage::disk('public')->assertMissing($path);
    }

    /**
     * @dataProvider profiles
     */
    public function test_removing_only_affects_the_signed_in_user(string $kind, string $update, string $destroy): void
    {
        $me = $this->makeUser($kind);
        $other = $this->makeUser($kind);

        $this->actingAs($other, 'web')->put(route($update), $this->data($other, ['avatar' => UploadedFile::fake()->image('o.jpg')]));
        $otherPath = $other->fresh()->avatar;

        // No user id is accepted by the route, so a request can only ever touch its own record.
        $this->actingAs($me, 'web')->delete(route($destroy), ['user_id' => $other->id, 'id' => $other->id]);

        $this->assertSame($otherPath, $other->fresh()->avatar);
        Storage::disk('public')->assertExists($otherPath);
    }

    /**
     * @dataProvider profiles
     */
    public function test_profile_page_shows_the_avatar_and_a_remove_button_only_when_set(string $kind, string $update): void
    {
        $user = $this->makeUser($kind);
        $edit = $kind === 'vendor' ? 'vendor.profile.edit' : 'account.profile.edit';

        $html = $this->actingAs($user, 'web')->get(route($edit))->assertOk()->getContent();

        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringContainsString('name="avatar"', $html);
        $this->assertStringContainsString('frontend-assets/images/dashboard_user.jpg', $html);
        $this->assertStringNotContainsString('id="remove-avatar-form"', $html);

        $this->actingAs($user, 'web')->put(route($update), $this->data($user, ['avatar' => UploadedFile::fake()->image('a.jpg')]));
        $path = $user->fresh()->avatar;

        $html = $this->actingAs($user->fresh(), 'web')->get(route($edit))->assertOk()->getContent();

        $this->assertStringContainsString('storage/'.$path, $html);
        $this->assertStringContainsString('id="remove-avatar-form"', $html);
        $this->assertStringContainsString('data-confirm-form="remove-avatar-form"', $html);
    }

    public function test_topbar_shows_the_avatar_on_every_dashboard_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')->put(route('account.profile.update'), $this->data($user, ['avatar' => UploadedFile::fake()->image('a.jpg')]));
        $path = $user->fresh()->avatar;

        $this->actingAs($user->fresh(), 'web')->get(route('account.dashboard'))->assertOk()->assertSee('storage/'.$path, false);
    }

    public function test_missing_file_falls_back_to_the_default_picture(): void
    {
        $user = User::factory()->create();
        $user->avatar = 'users/avatars/gone.jpg';
        $user->save();

        $this->assertFalse($user->hasAvatar());
        $this->assertStringContainsString('dashboard_user.jpg', $user->avatar_url);
    }

    public function test_guests_and_other_roles_cannot_use_the_endpoints(): void
    {
        $vendor = Vendor::factory()->approved()->create();
        $customer = User::factory()->create();

        $this->put(route('account.profile.update'), [])->assertRedirect(route('login'));
        $this->delete(route('vendor.profile.avatar.destroy'))->assertRedirect(route('login'));

        $this->actingAs($customer, 'web')->delete(route('vendor.profile.avatar.destroy'))->assertForbidden();
        $this->actingAs($vendor->user, 'web')->delete(route('account.profile.avatar.destroy'))->assertForbidden();
    }
}
