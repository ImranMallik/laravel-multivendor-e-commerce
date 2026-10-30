<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One submitted storefront search (SearchLogger). term is normalized (lowercase, single spaces).
 */
class SearchLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['term', 'results_count', 'user_id'];

    protected $casts = [
        'results_count' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
