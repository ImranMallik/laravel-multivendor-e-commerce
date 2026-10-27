<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class BlogTag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function blogs(): BelongsToMany
    {
        return $this->belongsToMany(Blog::class);
    }

    /**
     * Finds or creates the tag for a name typed in the admin form ("Summer Style" -> slug summer-style).
     */
    public static function findOrCreateByName(string $name): self
    {
        $name = Str::limit(trim($name), 50, '');

        return static::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
    }
}
