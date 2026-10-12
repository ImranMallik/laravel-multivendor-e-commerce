<?php

namespace App\Models;

use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry of a vendor's status history. Written only by VendorStatusService.
 */
class VendorStatusLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'vendor_id',
        'from_status',
        'to_status',
        'reason',
        'admin_id',
        'note',
    ];

    protected $casts = [
        'from_status' => VendorStatus::class,
        'to_status' => VendorStatus::class,
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
