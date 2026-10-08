<?php

namespace App\Enums;

enum VendorStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Admin template badge colour (badge-{color}). The badge component reads this.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Suspended => 'secondary',
        };
    }

    /**
     * Actions an admin may take on a vendor in this status.
     *
     * @return array<int, VendorAction>
     */
    public function availableActions(): array
    {
        return match ($this) {
            self::Pending => [VendorAction::Approve, VendorAction::Reject],
            self::Approved => [VendorAction::Reject, VendorAction::Suspend],
            self::Rejected => [VendorAction::Approve],
            self::Suspended => [VendorAction::Reactivate],
        };
    }
}
