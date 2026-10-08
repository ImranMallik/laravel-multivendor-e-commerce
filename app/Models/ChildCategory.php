<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChildCategory extends Model
{
    use GeneratesSlug, HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'sub_category_id',
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
        // category_id is always taken from the sub category, whatever was passed in.
        static::saving(function (ChildCategory $child) {
            if ($child->sub_category_id && ($child->isDirty('sub_category_id') || blank($child->category_id))) {
                $child->category_id = SubCategory::withTrashed()->whereKey($child->sub_category_id)->value('category_id');
            }
        });
    }

    protected function slugScope(): ?array
    {
        return ['sub_category_id', $this->sub_category_id];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
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
