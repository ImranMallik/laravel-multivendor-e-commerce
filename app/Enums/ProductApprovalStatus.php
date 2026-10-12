<?php

namespace App\Enums;

/**
 * Moderation state of a product. In-house (admin) products are always Approved; vendor products
 * start Pending when config('marketplace.vendor_product_approval') is on.
 */
enum ProductApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Admin template badge colour (badge-{color}).
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    /**
     * Pending -> Approved | Rejected, Rejected -> Approved | Pending (resubmitted), Approved -> Rejected | Pending (edited).
     *
     * @return array<int, ProductApprovalStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Approved, self::Rejected],
            self::Rejected => [self::Approved, self::Pending],
            self::Approved => [self::Rejected, self::Pending],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * @return array<string, string> value => label, for selects
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
