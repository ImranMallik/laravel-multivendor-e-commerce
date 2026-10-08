<?php

namespace App\Rules;

use App\Services\FontAwesomeIcons;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The value must be exactly one class string from the icon picker's list (for example "fas fa-tv").
 * Style matters: "far fa-tv" is rejected because Free has no regular TV icon.
 */
class FontAwesomeIcon implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! app(FontAwesomeIcons::class)->exists($value)) {
            $fail('The selected icon is not available. Please choose one from the icon picker.');
        }
    }
}
