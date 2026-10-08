<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubCategory extends Model
{
    use GeneratesSlug, HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        // Moving a sub category to another category moves its children with it,
        // so a child's category_id never disagrees with its sub category's.
        static::saved(function (SubCategory $subCategory) {
            if ($subCategory->wasChanged('category_id')) {
                ChildCategory::withTrashed()
                    ->where('sub_category_id', $subCategory->id)
                    ->update(['category_id' => $subCategory->category_id]);
            }
        });
    }

    protected function slugScope(): ?array
    {
        return ['category_id', $this->category_id];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function childCategories(): HasMany
    {
        return $this->hasMany(ChildCategory::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
