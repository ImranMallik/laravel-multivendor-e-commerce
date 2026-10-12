<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pivot row: one attribute value of one variant (composite primary key, no timestamps).
 */
class ProductVariantValue extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $guarded = [];
}
