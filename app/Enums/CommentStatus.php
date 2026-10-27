<?php

namespace App\Enums;

/**
 * Moderation state of a blog comment. New comments start Pending; only Approved ones are shown.
 */
enum CommentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Spam = 'spam';

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
            self::Spam => 'danger',
        };
    }
}
