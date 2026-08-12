<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Contracts\Validation\ValidationRule;

final class PageQueryRule implements ValidationRule
{
    public function __construct(
        private int $maxSize = 100,
    ) {}

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

        if (is_int($value)) {
            if ($value < 1) {
                $fail('The page must be a positive integer.');
            }

            return;
        }

        if (is_string($value) && ctype_digit(trim($value))) {
            if ((int) trim($value) < 1) {
                $fail('The page must be a positive integer.');
            }

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
                $numInt = is_int($num) ? $num : ((is_string($num) && ctype_digit(trim($num))) ? (int) trim($num) : null);
                if ($numInt === null || $numInt < 1) {
                    $fail('The page.number must be a positive integer.');
                }
            }

            if (array_key_exists('size', $value)) {
                $size = $value['size'];
                $sizeInt = is_int($size) ? $size : ((is_string($size) && ctype_digit(trim($size))) ? (int) trim($size) : null);
                if ($sizeInt === null || $sizeInt < 1) {
                    $fail('The page.size must be a positive integer.');
                } elseif ($sizeInt > $this->maxSize) {
                    $fail(sprintf('The page.size may not be greater than %d.', $this->maxSize));
                }
            }

            return;
        }

        $fail('The page must be a positive integer, an object with number/size, or a JSON object string.');
    }
}
