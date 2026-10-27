<?php

namespace App\Enums;

/**
 * Stored blog post status. A Published post with a future published_at is shown as "Scheduled"
 * (see Blog::displayState()) and stays hidden until that time.
 */
enum BlogStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * @return array<string, string> value => label, for selects
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
