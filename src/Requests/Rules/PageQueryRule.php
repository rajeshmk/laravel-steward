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

        if (is_string($value)) {
            $trimmed = trim($value);
            if (str_starts_with($trimmed, '{')) {
                if (json_validate($trimmed)) {
                    $decoded = json_decode($trimmed, true);
                    if (is_array($decoded)) {
                        return;
                    }
                }
            }

            if (ctype_digit($trimmed) && (int) $trimmed > 0) {
                return;
            }
        }

        $fail('The page must be a positive integer, an object with number/size, or a JSON object string.');
    }
}
