<?php

namespace App\Models;

use App\Notifications\Admin\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'photo',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'status' => 'boolean',
        'password' => 'hashed',
    ];

    protected function photoUrl(): Attribute
    {
        // asset() follows the host the admin is browsing with. Storage::url() would bake in
        // APP_URL, which breaks images when APP_URL differs from the served host/port.
        return Attribute::get(fn () => $this->photo && Storage::disk('public')->exists($this->photo)
            ? asset('storage/'.$this->photo)
            : asset('admin-assets/img/avatar/avatar-1.png'));
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
