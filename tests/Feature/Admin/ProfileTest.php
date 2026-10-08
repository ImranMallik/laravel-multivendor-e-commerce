<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = Admin::factory()->create();
    }

    private function actAsAdmin(): static
    {
        return $this->actingAs($this->admin, 'admin');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profileData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '555-1234',
        ], $overrides);
    }

    public function test_profile_page_renders(): void
    {
        $this->actAsAdmin()->get(route('admin.profile.edit'))
            ->assertOk()->assertSee($this->admin->email);
    }

    public function test_admin_can_update_name_email_and_phone(): void
    {
        $this->actAsAdmin()->put(route('admin.profile.update'), $this->profileData())
            ->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('admins', [
            'id' => $this->admin->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '555-1234',
        ]);
    }

    public function test_email_must_be_unique_but_own_email_is_allowed(): void
    {
        $other = Admin::factory()->create();

        $this->actAsAdmin()->put(route('admin.profile.update'), $this->profileData(['email' => $other->email]))
            ->assertSessionHasErrors('email');

        $this->actAsAdmin()->put(route('admin.profile.update'), $this->profileData(['email' => $this->admin->email]))
            ->assertSessionHasNoErrors();
    }

    public function test_admin_can_upload_photo(): void
    {
        $this->actAsAdmin()->put(route('admin.profile.update'), $this->profileData([
            'photo' => UploadedFile::fake()->image('me.jpg'),
        ]))->assertSessionHasNoErrors();

        $path = $this->admin->fresh()->photo;

        $this->assertStringStartsWith('admins/photos/', $path);
        $this->assertStringNotContainsString('me', basename($path));
        Storage::disk('public')->assertExists($path);
    }

    public function test_old_photo_is_deleted_when_replaced(): void
    {
        $this->actAsAdmin()->put(route('admin.profile.update'), $this->profileData([
            'photo' => UploadedFile::fake()->image('a.jpg'),
        ]));
        $old = $this->admin->fresh()->photo;

        $this->actAsAdmin()->put(route('admin.profile.update'), $this->profileData([
            'photo' => UploadedFile::fake()->image('b.png'),
        ]));
        $new = $this->admin->fresh()->photo;

        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
    }

    public function test_non_image_and_oversized_files_are_rejected(): void
    {
        $this->actAsAdmin()->put(route('admin.profile.update'), $this->profileData([
            'photo' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasErrors('photo');

        $this->actAsAdmin()->put(route('admin.profile.update'), $this->profileData([
            'photo' => UploadedFile::fake()->image('big.jpg')->size(3000),
        ]))->assertSessionHasErrors('photo');

        $this->assertNull($this->admin->fresh()->photo);
    }

    public function test_admin_can_remove_photo(): void
    {
        $this->actAsAdmin()->put(route('admin.profile.update'), $this->profileData([
            'photo' => UploadedFile::fake()->image('a.jpg'),
        ]));
        $path = $this->admin->fresh()->photo;

        $this->actAsAdmin()->delete(route('admin.profile.photo.destroy'))->assertSessionHas('success');

        $this->assertNull($this->admin->fresh()->photo);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_photo_url_falls_back_to_default_avatar(): void
    {
        $this->assertStringContainsString('admin-assets/img/avatar/avatar-1.png', $this->admin->photo_url);
    }

    public function test_photo_url_points_to_stored_file_and_uses_request_host(): void
    {
        config(['app.url' => 'http://not-the-served-host.test']);

        $this->actAsAdmin()->put(route('admin.profile.update'), $this->profileData([
            'photo' => UploadedFile::fake()->image('me.jpg'),
        ]));

        $admin = $this->admin->fresh();

        $this->assertStringEndsWith('/storage/'.$admin->photo, $admin->photo_url);
        $this->assertStringNotContainsString('public/', $admin->photo);
    }

    public function test_photo_url_falls_back_when_file_is_missing(): void
    {
        $this->admin->update(['photo' => 'admins/photos/missing.jpg']);

        $this->assertStringContainsString('admin-assets/img/avatar/avatar-1.png', $this->admin->fresh()->photo_url);
    }

    public function test_profile_page_uses_photo_url_for_card_and_navbar(): void
    {
        $this->actAsAdmin()->put(route('admin.profile.update'), $this->profileData([
            'photo' => UploadedFile::fake()->image('me.jpg'),
        ]));

        $url = $this->admin->fresh()->photo_url;

        $this->actAsAdmin()->get(route('admin.profile.edit'))
            ->assertSee($url, false);
    }

    public function test_password_change_fails_with_wrong_current_password(): void
    {
        $this->actAsAdmin()->put(route('admin.profile.password'), [
            'current_password' => 'wrong',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertSessionHasErrors('current_password', null, 'updatePassword');

        $this->assertTrue(Hash::check('password', $this->admin->fresh()->password));
    }

    public function test_password_change_succeeds_with_correct_current_password(): void
    {
        $this->actAsAdmin()->put(route('admin.profile.password'), [
            'current_password' => 'password',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewPass123', $this->admin->fresh()->password));
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->actAsAdmin()->put(route('admin.profile.password'), [
            'current_password' => 'password',
            'password' => 'onlyletters',
            'password_confirmation' => 'onlyletters',
        ])->assertSessionHasErrors('password', null, 'updatePassword');
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.profile.edit'))->assertRedirect(route('admin.login'));
        $this->put(route('admin.profile.update'), $this->profileData())->assertRedirect(route('admin.login'));
    }

    public function test_web_user_cannot_access_admin_profile(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/profile')
            ->assertRedirect(route('admin.login'));
    }
}
