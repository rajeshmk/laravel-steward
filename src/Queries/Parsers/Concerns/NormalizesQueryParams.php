<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Parsers\Concerns;

use Hatchyu\Steward\Queries\Params\SortField;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Validation\ValidationException;

trait NormalizesQueryParams
{
    protected function decodeJsonIfString(mixed $value, string $paramName): mixed
    {
        if (QuerySyntax::isJsonPayloadString($value)) {
            $trimmed = trim((string) $value);
            if (! json_validate($trimmed)) {
                throw ValidationException::withMessages([
                    $paramName => sprintf('The %s parameter contains an invalid JSON string.', $paramName),
                ]);
            }

            return json_decode($trimmed, true);
        }

        return $value;
    }

    protected function normalizeBoundedPositiveInt(mixed $value, int $default, int $max, string $attribute): int
    {
        if ($value === null) {
            return $default;
        }

        $normalized = is_int($value)
            ? $value
            : (is_string($value) && ctype_digit(trim($value)) ? (int) trim($value) : null);

        if ($normalized === null || $normalized < 1 || $normalized > $max) {
            throw ValidationException::withMessages([
                $attribute => sprintf('The %s value must be a positive integer not greater than %d.', $attribute, $max),
            ]);
        }

        return $normalized;
    }

    /**
     * Normalize a string, comma-separated string, or array into a list of trimmed non-empty strings.
     *
     * @return list<string>|null
     */
    private function normalizeStringList(mixed $value, string $paramName): ?array
    {
        $value = $this->decodeJsonIfString($value, $paramName);

        if (! is_string($value) && ! is_array($value)) {
            return null;
        }

        return QuerySyntax::parseStringList($value);
    }

    /**
     * @return list<SortField>
     */
    private function parseSortString(string $sort): array
    {
        if (trim($sort) === '') {
            return [];
        }

        return array_values(array_map(
            $this->parseSortToken(...),
            array_filter(array_map(trim(...), explode(',', $sort)))
        ));
    }

    private function parseSortToken(string $token): SortField
    {
        $token = trim($token);
        $isDesc = str_starts_with($token, '-');
        $field = $isDesc ? substr($token, 1) : $token;

        if (trim($field) === '' || ! QuerySyntax::isValidDotIdentifier(trim($field))) {
            throw ValidationException::withMessages([
                'sort' => sprintf('Invalid sort field: %s.', $field),
            ]);
        }

        return new SortField(
            field: trim($field),
            direction: $isDesc ? 'desc' : 'asc'
        );
    }

    /**
     * Normalize 'true'/'false' strings to booleans (from query string or JSON).
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function normalizeFilterValues(array $filters): array
    {
        $out = [];

        foreach ($filters as $key => $value) {
            $out[$key] = $this->normalizeFilterValue($value);
        }

        return $out;
    }

    private function normalizeFilterValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map($this->normalizeFilterValue(...), $value);
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        return $value;
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
