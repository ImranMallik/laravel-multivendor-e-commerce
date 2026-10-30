<?php

namespace App\Enums;

/**
 * Sort options of the shop page topbar (template product_grid_view.html). Relevance only applies to a search;
 * without one the shop lists newest first. Rating sorting arrives with the reviews module.
 */
enum ProductSort: string
{
    case Relevance = 'relevance';
    case Newest = 'newest';
    case PriceAsc = 'price_asc';
    case PriceDesc = 'price_desc';

    public function label(): string
    {
        return match ($this) {
            self::Relevance => 'Best match',
            self::Newest => 'Newest first',
            self::PriceAsc => 'Price: low to high',
            self::PriceDesc => 'Price: high to low',
        };
    }

    /**
     * The sort to use when none (or an unknown one) is asked for.
     */
    public static function default(bool $searching): self
    {
        return $searching ? self::Relevance : self::Newest;
    }

    /**
     * @return array<int, self> the options for the select (no "best match" without a search)
     */
    public static function options(bool $searching): array
    {
        return $searching ? self::cases() : [self::Newest, self::PriceAsc, self::PriceDesc];
    }
}
