<?php

namespace App\Models;

use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasFactory;

    /**
     * Status, rejection_reason and approved_at are changed only by the vendor Actions,
     * so they are intentionally not mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shop_name',
        'phone',
        'address',
        'logo',
        'description',
    ];

    protected $casts = [
        'status' => VendorStatus::class,
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Vendor $vendor) {
            if (blank($vendor->slug)) {
                $vendor->slug = static::uniqueSlug($vendor->shop_name);
            }
        });
    }

    public static function uniqueSlug(string $shopName): string
    {
        $base = Str::slug($shopName) ?: 'shop';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isApproved(): bool
    {
        return $this->status === VendorStatus::Approved;
    }

    public function scopeStatus(Builder $query, ?VendorStatus $status): Builder
    {
        return $status ? $query->where('status', $status->value) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('shop_name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhereHas('user', fn (Builder $u) => $u
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%"));
        });
    }

    public function scopeRegisteredBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('vendors.created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('vendors.created_at', '<=', $to));
    }

    /**
     * Vendor counts per status plus the overall total, for the admin filter tabs.
     *
     * @return array<string, int>
     */
    public static function statusCounts(): array
    {
        $counts = static::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $perStatus = collect(VendorStatus::cases())
            ->mapWithKeys(fn (VendorStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)])
            ->all();

        return ['all' => array_sum($perStatus)] + $perStatus;
    }

    public function hasLogo(): bool
    {
        return $this->logo && Storage::disk('public')->exists($this->logo);
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->hasLogo()
            ? asset('storage/'.$this->logo)
            : asset('frontend-assets/images/logo.png'));
    }
}
