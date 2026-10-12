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
     * The status workflow, in one place:
     * Pending -> Approved | Rejected, Rejected -> Pending (re-apply),
     * Approved -> Suspended, Suspended -> Approved (reactivate).
     *
     * @return array<int, VendorStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Approved, self::Rejected],
            self::Rejected => [self::Pending],
            self::Approved => [self::Suspended],
            self::Suspended => [self::Approved],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * Admin buttons for a vendor in this status: the actions that start from this status and
     * whose target status is an allowed transition. (Rejected -> Pending is the vendor's own "re-apply", so it has no admin button.)
     *
     * @return array<int, VendorAction>
     */
    public function availableActions(): array
    {
        return array_values(array_filter(
            VendorAction::cases(),
            fn (VendorAction $action) => $action->fromStatus() === $this && $this->canTransitionTo($action->targetStatus()),
        ));
    }
}
