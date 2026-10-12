<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Brand extends Model
{
    use GeneratesSlug, HasFactory, SoftDeletes;

    public const LOGO_HINT = 'Recommended size: 300 x 150 px (2:1), transparent or white background. JPG, PNG, WEBP or SVG, up to 1 MB.';

    protected $fillable = [
        'name',
        'logo',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected function slugScope(): ?array
    {
        return null;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->latest('id');
    }

    public function hasLogo(): bool
    {
        return $this->logo && Storage::disk('public')->exists($this->logo);
    }

    /**
     * Uploaded logo, or the template's placeholder logo when the file is missing.
     * asset() follows the host being browsed, so it does not depend on APP_URL.
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->hasLogo()
            ? asset('storage/'.$this->logo)
            : asset('frontend-assets/images/brand_logo_1.jpg'));
    }
}
