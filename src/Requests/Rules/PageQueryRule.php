<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
use Hatchyu\Steward\Queries\Support\QueryParamLimits;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Contracts\Validation\ValidationRule;

final class PageQueryRule implements ValidationRule
{
    public function __construct(private QueryParamLimits $limits = new QueryParamLimits()) {}

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

            $allowedKeys = ['number', 'size', 'cursor'];
            foreach (array_keys($value) as $key) {
                if (! is_string($key) || ! in_array($key, $allowedKeys, true)) {
                    $fail(sprintf('Unsupported page parameter key: %s.', (string) $key));

                    return;
                }
            }

            if (array_key_exists('number', $value)) {
                $num = $value['number'];
                $numInt = is_int($num) ? $num : ((is_string($num) && ctype_digit(trim($num))) ? (int) trim($num) : null);
                if ($numInt === null || $numInt < 1 || $numInt > $this->limits->maxPageNumber) {
                    $fail(sprintf('The page.number must be a positive integer not greater than %d.', $this->limits->maxPageNumber));
                }
            }

            if (array_key_exists('size', $value)) {
                $size = $value['size'];
                $sizeInt = is_int($size) ? $size : ((is_string($size) && ctype_digit(trim($size))) ? (int) trim($size) : null);
                if ($sizeInt === null || $sizeInt < 1) {
                    $fail('The page.size must be a positive integer.');
                } elseif ($sizeInt > $this->limits->maxPageSize) {
                    $fail(sprintf('The page.size may not be greater than %d.', $this->limits->maxPageSize));
                }
            }

            if (array_key_exists('cursor', $value) && (! is_string($value['cursor']) || trim($value['cursor']) === '')) {
                $fail('The page.cursor must be a non-empty string.');
            }

            return;
        }

        $fail('The page must be a positive integer, an object with number/size, or a JSON object string.');
    }
}
