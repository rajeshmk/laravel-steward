<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PageQueryRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_array($value) || is_int($value)) {
            return;
        }

        if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
            return;
        }

        $fail('The page must be a positive integer or an object with number/size.');
    }
}
