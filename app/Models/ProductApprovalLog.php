<?php

namespace App\Models;

use App\Enums\ProductApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry of a product's approval history. Written only by ProductApprovalService.
 */
class ProductApprovalLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'from_status',
        'to_status',
        'reason',
        'admin_id',
        'note',
    ];

    protected $casts = [
        'from_status' => ProductApprovalStatus::class,
        'to_status' => ProductApprovalStatus::class,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
