<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
use Hatchyu\Steward\Queries\Support\QueryParamLimits;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Contracts\Validation\ValidationRule;

final class PageQueryRule implements ValidationRule
{
    private QueryParamLimits $limits;

    public function __construct(?QueryParamLimits $limits = null)
    {
        $this->limits = $limits ?? QueryParamLimits::fromConfig();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value)) {
            if (mb_strlen($value) > $this->limits->maxValueLength) {
                $fail(sprintf('The page parameter value exceeds the maximum allowed length of %d characters.', $this->limits->maxValueLength));

                return;
            }

            if (QuerySyntax::isJsonPayloadString($value)) {
                $trimmed = trim($value);
                if (! json_validate($trimmed)) {
                    $fail('The page parameter contains an invalid JSON string.');

                    return;
                }

                $value = json_decode($trimmed, true);
            }
        }

        if (is_int($value)) {
            if ($value < 1) {
                $fail('The page must be a positive integer.');
            } elseif ($value > $this->limits->maxPageNumber) {
                $fail(sprintf('The page may not be greater than %d.', $this->limits->maxPageNumber));
            }

            return;
        }

        if (is_string($value) && ctype_digit(trim($value))) {
            $num = (int) trim($value);
            if ($num < 1) {
                $fail('The page must be a positive integer.');
            } elseif ($num > $this->limits->maxPageNumber) {
                $fail(sprintf('The page may not be greater than %d.', $this->limits->maxPageNumber));
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

            if (array_key_exists('cursor', $value)) {
                $cursor = $value['cursor'];
                if (! is_string($cursor) || trim($cursor) === '') {
                    $fail('The page.cursor must be a non-empty string.');
                } elseif (mb_strlen($cursor) > $this->limits->maxValueLength) {
                    $fail(sprintf('The page.cursor exceeds the maximum allowed length of %d characters.', $this->limits->maxValueLength));
                }
            }

            return;
        }

        $fail('The page must be a positive integer, an object with number/size, or a JSON object string.');
    }
}
