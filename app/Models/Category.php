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

class Category extends Model
{
    use GeneratesSlug, HasFactory, SoftDeletes;

    /** Storefront menu icon for a category that has none (Font Awesome Free 5.15.1). */
    public const DEFAULT_ICON = 'fas fa-th-large';

    protected $fillable = [
        'name',
        'icon',
        'image',
        'sort_order',
        'is_active',
        'show_in_menu',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_menu' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected function slugScope(): ?array
    {
        return null;
    }

    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class);
    }

    public function childCategories(): HasMany
    {
        return $this->hasMany(ChildCategory::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInMenu(Builder $query): Builder
    {
        return $query->where('show_in_menu', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function hasImage(): bool
    {
        return $this->image && Storage::disk('public')->exists($this->image);
    }

    /**
     * Font Awesome classes for the menu icon, with a generic fallback.
     */
    protected function iconClass(): Attribute
    {
        return Attribute::get(fn () => filled($this->icon) ? $this->icon : self::DEFAULT_ICON);
    }

    /**
     * Uploaded image, or a neutral placeholder when there is none (or the file is gone).
     * asset() follows the host being browsed, so it does not depend on APP_URL.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->hasImage()
            ? asset('storage/'.$this->image)
            : asset('frontend-assets/images/favicon.png'));
    }
}
