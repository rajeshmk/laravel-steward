<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class FilterQueryRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $filters = $this->normalizeFilters($value, $fail);
        if (! is_array($filters)) {
            return;
        }

        foreach ($filters as $key => $filterValue) {
            if (! is_string($key) || ! preg_match('/^[A-Za-z_][A-Za-z0-9_\.]*$/', $key)) {
                $fail(sprintf('Invalid filter key: %s.', (string) $key));

                continue;
            }

            if ($this->isScalarOrNull($filterValue)) {
                continue;
            }

            if (is_array($filterValue) && $this->isScalarList($filterValue)) {
                continue;
            }

            $fail(sprintf(
                'Invalid filter value for %s. Expected scalar, null, or list of scalars.',
                $key
            ));
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeFilters(mixed $value, Closure $fail): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            $fail('The filter must be an array or a JSON object string.');

            return null;
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded)) {
            $fail('The filter must be an array or a JSON object string.');

            return null;
        }

        return $decoded;
    }

    private function isScalarOrNull(mixed $value): bool
    {
        return is_scalar($value) || $value === null;
    }

    /**
     * @param array<mixed> $value
     */
    private function isScalarList(array $value): bool
    {
        if (! array_is_list($value)) {
            return false;
        }

        return array_all($value, fn ($item): bool => $this->isScalarOrNull($item));
    }
}
