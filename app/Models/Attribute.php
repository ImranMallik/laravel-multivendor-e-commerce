<?php

namespace App\Models;

use App\Enums\AttributeType;
use App\Models\Concerns\GeneratesSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A product option such as Size or Color (the "Attributes" admin section). Vendors choose from the
 * active ones when building variants.
 */
class Attribute extends Model
{
    use GeneratesSlug, HasFactory;

    protected $fillable = [
        'name',
        'type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'type' => AttributeType::class,
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected function slugScope(): ?array
    {
        return null;
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeValues(): HasMany
    {
        return $this->values()->where('is_active', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function isColor(): bool
    {
        return $this->type === AttributeType::Color;
    }

    /**
     * Whether any variant (soft-deleted ones included, they may still be referenced) uses a value of this attribute.
     */
    public function isInUse(): bool
    {
        return ProductVariantValue::query()
            ->whereIn('attribute_value_id', $this->values()->select('attribute_values.id'))
            ->exists();
    }
}
