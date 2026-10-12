<?php

namespace App\Enums;

/**
 * How a vendor record came to exist: created directly by an admin, or applied for by the user.
 */
enum VendorSource: string
{
    case Admin = 'admin';
    case Application = 'application';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Application => 'Applied',
        };
    }

    /**
     * Admin template badge colour (badge-{color}).
     */
    public function color(): string
    {
        return match ($this) {
            self::Admin => 'info',
            self::Application => 'light',
        };
    }
}
