<?php

namespace App\Enums;

/**
 * Admin actions on a vendor. The single source for labels, icons, colours,
 * confirm dialogs and routes, so views and JS never hard-code them.
 * Which actions apply to a status is defined by VendorStatus::availableActions().
 */
enum VendorAction: string
{
    case Approve = 'approve';
    case Reject = 'reject';
    case Suspend = 'suspend';
    case Reactivate = 'reactivate';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * The only status this action can be taken from.
     */
    public function fromStatus(): VendorStatus
    {
        return match ($this) {
            self::Approve, self::Reject => VendorStatus::Pending,
            self::Suspend => VendorStatus::Approved,
            self::Reactivate => VendorStatus::Suspended,
        };
    }

    /**
     * The status this action moves a vendor to.
     */
    public function targetStatus(): VendorStatus
    {
        return match ($this) {
            self::Approve, self::Reactivate => VendorStatus::Approved,
            self::Reject => VendorStatus::Rejected,
            self::Suspend => VendorStatus::Suspended,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Approve => 'fas fa-check',
            self::Reject => 'fas fa-times',
            self::Suspend => 'fas fa-ban',
            self::Reactivate => 'fas fa-undo',
        };
    }

    /**
     * Admin template button colour.
     */
    public function variant(): string
    {
        return match ($this) {
            self::Approve => 'success',
            self::Reject => 'danger',
            self::Suspend => 'warning',
            self::Reactivate => 'info',
        };
    }

    /**
     * Hex colour of the template button variant, for the SweetAlert confirm button.
     */
    public function confirmColor(): string
    {
        return match ($this->variant()) {
            'success' => '#47c363',
            'danger' => '#fc544b',
            'warning' => '#ffa426',
            default => '#3abaf4',
        };
    }

    public function requiresReason(): bool
    {
        return $this === self::Reject || $this === self::Suspend;
    }

    public function confirmIcon(): string
    {
        return $this->requiresReason() ? 'warning' : 'question';
    }

    public function confirmTitle(): string
    {
        return match ($this) {
            self::Approve => 'Approve this vendor?',
            self::Reject => 'Reject this vendor?',
            self::Suspend => 'Suspend this vendor?',
            self::Reactivate => 'Reactivate this vendor?',
        };
    }

    public function confirmText(): string
    {
        return match ($this) {
            self::Approve => 'The vendor can use the vendor panel and is notified by email.',
            self::Reject => 'The reason is emailed to the vendor and shown on their status page.',
            self::Suspend => 'The vendor loses access to the vendor panel until reactivated. The reason is emailed to the vendor.',
            self::Reactivate => 'The vendor gets access to the vendor panel again.',
        };
    }

    public function confirmButton(): string
    {
        return match ($this) {
            self::Approve => 'Yes, approve',
            self::Reject => 'Reject vendor',
            self::Suspend => 'Yes, suspend',
            self::Reactivate => 'Yes, reactivate',
        };
    }

    public function routeName(): string
    {
        return 'admin.vendors.'.$this->value;
    }
}
