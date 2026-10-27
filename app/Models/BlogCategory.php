<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogCategory extends Model
{
    use GeneratesSlug, HasFactory, SoftDeletes;

    /** The template's category label colours (.wsus__single_blog .red / .blue / .orange). */
    private const LABEL_COLORS = ['red', 'blue', 'orange'];

    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected function slugScope(): ?array
    {
        return null;
    }

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('blog_categories.is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Template label colour class for this category, picked from its id so it never changes.
     */
    public function labelColor(): string
    {
        return self::LABEL_COLORS[$this->id % count(self::LABEL_COLORS)];
    }
}
