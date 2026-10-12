<?php

namespace App\Enums;

/**
 * How an attribute's values are shown on the product page: buttons (Size) or color swatches (Color).
 */
enum AttributeType: string
{
    case Select = 'select';
    case Color = 'color';

    public function label(): string
    {
        return match ($this) {
            self::Select => 'Buttons / list',
            self::Color => 'Color swatches',
        };
    }

    /**
     * @return array<string, string> value => label, for selects
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}
