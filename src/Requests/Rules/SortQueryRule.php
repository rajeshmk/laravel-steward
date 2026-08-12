<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
use Hatchyu\Steward\Queries\Support\QueryParamLimits;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Contracts\Validation\ValidationRule;

final class SortQueryRule implements ValidationRule
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
                $fail(sprintf('The sort parameter value exceeds the maximum allowed length of %d characters.', $this->limits->maxValueLength));

                return;
            }

            if (QuerySyntax::isJsonPayloadString($value)) {
                $trimmed = trim($value);
                if (! json_validate($trimmed)) {
                    $fail('The sort parameter contains an invalid JSON string.');

                    return;
                }

                $value = json_decode($trimmed, true);
            }
        }

        if (is_string($value)) {
            $this->validateSortString($value, $fail);

            return;
        }

        if (is_array($value)) {
            $this->validateSortArray($value, $fail);

            return;
        }

        $fail('The sort must be a string, array, or JSON string.');
    }

    private function validateSortString(string $sort, Closure $fail): void
    {
        $tokens = array_filter(array_map(trim(...), explode(',', $sort)));
        if ($tokens === [] && trim($sort) !== '') {
            $fail('Invalid sort string.');

            return;
        }

        if (count($tokens) > $this->limits->maxSortFields) {
            $fail(sprintf('At most %d sort fields are allowed.', $this->limits->maxSortFields));

            return;
        }

        foreach ($tokens as $token) {
            $field = ltrim($token, '-');

            if (! QuerySyntax::isValidDotIdentifier($field)) {
                $fail(sprintf('Invalid sort field: %s.', $field));
            }
        }
    }

    /**
     * @param array<int|string, mixed> $sort
     */
    private function validateSortArray(array $sort, Closure $fail): void
    {
        if (count($sort) > $this->limits->maxSortFields) {
            $fail(sprintf('At most %d sort fields are allowed.', $this->limits->maxSortFields));

            return;
        }

        foreach ($sort as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $field = ltrim(trim($value), '-');
                if (! QuerySyntax::isValidDotIdentifier($field)) {
                    $fail(sprintf('Invalid sort field: %s.', $field));
                }

                continue;
            }

            if (is_string($key) && is_string($value)) {
                if (! QuerySyntax::isValidDotIdentifier($key)) {
                    $fail(sprintf('Invalid sort field: %s.', $key));
                }

                $direction = strtolower(trim($value));
                if (! in_array($direction, ['asc', 'desc'], true)) {
                    $fail(sprintf('Unsupported sort direction for %s: %s.', $key, $value));
                }

                continue;
            }

            $fail('Invalid sort array format.');
        }
    }
}
