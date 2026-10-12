<?php

namespace App\Enums;

/**
 * Home page section flags. Only admins can set them.
 */
enum ProductFlag: string
{
    case Featured = 'is_featured';
    case Top = 'is_top';
    case Best = 'is_best';

    public function label(): string
    {
        return match ($this) {
            self::Featured => 'Featured',
            self::Top => 'Top',
            self::Best => 'Best',
        };
    }

    /**
     * Where the flag puts the product on the home page.
     */
    public function help(): string
    {
        return match ($this) {
            self::Featured => 'Hot Deals of the Day',
            self::Top => 'Top Categories of the Month',
            self::Best => 'Weekly Best Sale',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function columns(): array
    {
        return array_map(fn (self $flag) => $flag->value, self::cases());
    }
}
