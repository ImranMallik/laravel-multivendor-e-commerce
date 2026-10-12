<?php

namespace App\Actions\Brand;

use App\Models\Brand;
use InvalidArgumentException;

class ToggleBrandFlagAction
{
    public const FLAGS = ['is_active', 'is_featured'];

    public function execute(Brand $brand, string $flag): Brand
    {
        if (! in_array($flag, self::FLAGS, true)) {
            throw new InvalidArgumentException("Brand has no toggle named {$flag}.");
        }

        $brand->{$flag} = ! $brand->{$flag};
        $brand->save();

        return $brand;
    }
}
