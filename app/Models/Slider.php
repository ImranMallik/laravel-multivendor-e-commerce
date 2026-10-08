<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Slider extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The template's slides are 1300 x 500 px and shown with background-size: cover.
     */
    public const IMAGE_HINT = 'Recommended size: 1300 x 500 px (13:5). JPG, PNG or WEBP, up to 2 MB.';

    protected $fillable = [
        'top_text',
        'title',
        'offer_text',
        'button_text',
        'button_url',
        'image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->latest('id');
    }

    public function hasImage(): bool
    {
        return $this->image && Storage::disk('public')->exists($this->image);
    }

    public function hasButton(): bool
    {
        return filled($this->button_text) && filled($this->button_url);
    }

    /**
     * Uploaded image, or the template's first slide as a placeholder when the file is missing.
     * asset() follows the host being browsed, so it does not depend on APP_URL.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->hasImage()
            ? asset('storage/'.$this->image)
            : asset('frontend-assets/images/slider_1.jpg'));
    }
}
