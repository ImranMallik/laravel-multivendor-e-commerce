<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        // Deprecated: kept in the table, but vendor access is decided by the vendors record.
        'role' => UserRole::class,
        'status' => UserStatus::class,
    ];

    public function hasAvatar(): bool
    {
        return $this->avatar && Storage::disk('public')->exists($this->avatar);
    }

    /**
     * Uploaded avatar, or the template's default user picture when there is none (or the file is gone).
     * asset() follows the host being browsed, so it does not depend on APP_URL.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->hasAvatar()
            ? asset('storage/'.$this->avatar)
            : asset('frontend-assets/images/dashboard_user.jpg'));
    }

    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    /**
     * Being a seller means owning a vendors record. users.role is no longer read for access.
     */
    public function hasShop(): bool
    {
        return $this->vendor !== null;
    }

    public function isApprovedVendor(): bool
    {
        return $this->vendor?->isApproved() === true;
    }

    public function canApplyForVendor(): bool
    {
        return ! $this->hasShop();
    }

    public function isBlocked(): bool
    {
        return $this->status === UserStatus::Blocked;
    }

    /**
     * Where this user lands after login: sellers with an approved shop go to the vendor panel, everyone else to the account.
     */
    public function homeRoute(): string
    {
        return $this->isApprovedVendor() ? route('vendor.dashboard') : route('account.dashboard');
    }
}
