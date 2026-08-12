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
        if (is_string($value) && QuerySyntax::isJsonPayloadString($value)) {
            $trimmed = trim($value);
            if (! json_validate($trimmed)) {
                $fail('The page parameter contains an invalid JSON string.');

                return;
            }

            $value = json_decode($trimmed, true);
        }

        if (is_int($value) || (is_string($value) && ctype_digit(trim($value)) && (int) trim($value) > 0)) {
            return;
        }

        if (is_array($value)) {
            if (! QuerySyntax::isAssociativeArray($value)) {
                $fail('The page parameter must be an object or positive integer.');

                return;
            }

            $allowedKeys = ['number', 'size', 'cursor', 'offset'];
            foreach (array_keys($value) as $key) {
                if (! is_string($key) || ! in_array($key, $allowedKeys, true)) {
                    $fail(sprintf('Unsupported page parameter key: %s.', (string) $key));

                    return;
                }
            }

            if (array_key_exists('number', $value)) {
                $num = $value['number'];
                if (! is_int($num) && (! is_string($num) || ! ctype_digit(trim((string) $num)) || (int) trim((string) $num) < 1)) {
                    $fail('The page.number must be a positive integer.');
                }
            }

            if (array_key_exists('size', $value)) {
                $size = $value['size'];
                if (! is_int($size) && (! is_string($size) || ! ctype_digit(trim((string) $size)) || (int) trim((string) $size) < 1)) {
                    $fail('The page.size must be a positive integer.');
                }
            }

            return;
        }

        $fail('The page must be a positive integer, an object with number/size, or a JSON object string.');
    }
}
