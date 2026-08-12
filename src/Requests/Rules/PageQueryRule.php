<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Contracts\Validation\ValidationRule;

final class PageQueryRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = QuerySyntax::tryDecodeJson($value);

        if (is_array($value) || is_int($value)) {
            return;
        }

        if (is_string($value) && ctype_digit(trim($value)) && (int) trim($value) > 0) {
            return;
        }

        $fail('The page must be a positive integer, an object with number/size, or a JSON object string.');
    }
}
