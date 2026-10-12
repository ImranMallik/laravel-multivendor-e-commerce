<?php

namespace App\Enums;

/**
 * Whether the owner wants the product listed. Storefront visibility also needs ProductApprovalStatus::Approved.
 */
enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';

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
            self::Draft => 'light',
            self::Active => 'success',
            self::Inactive => 'secondary',
        };
    }

    /**
     * @return array<string, string> value => label, for selects
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
