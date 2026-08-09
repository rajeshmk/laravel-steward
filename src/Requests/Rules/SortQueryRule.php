<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class SortQueryRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value)) {
            $this->validateSortString($value, $fail);

            return;
        }

        if (is_array($value)) {
            $this->validateSortArray($value, $fail);

            return;
        }

        $fail('The sort must be a string or array.');
    }

    private function validateSortString(string $sort, Closure $fail): void
    {
        foreach (array_filter(array_map(trim(...), explode(',', $sort))) as $token) {
            $field = ltrim($token, '-');

            if (! preg_match('/^[A-Za-z_][A-Za-z0-9_\.]*$/', $field)) {
                $fail(sprintf('Invalid sort field: %s.', $field));
            }
        }
    }

    /**
     * @param array<int|string, mixed> $sort
     */
    private function validateSortArray(array $sort, Closure $fail): void
    {
        foreach ($sort as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $field = ltrim(trim($value), '-');
                if (! preg_match('/^[A-Za-z_][A-Za-z0-9_\.]*$/', $field)) {
                    $fail(sprintf('Invalid sort field: %s.', $field));
                }

                continue;
            }

            if (is_string($key) && is_string($value)) {
                if (! preg_match('/^[A-Za-z_][A-Za-z0-9_\.]*$/', $key)) {
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
