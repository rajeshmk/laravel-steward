<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Parsers\Concerns;

use Illuminate\Validation\ValidationException;

trait NormalizesQueryParams
{
    private function decodeJsonIfString(mixed $value, string $paramName): mixed
    {
        if (is_string($value)) {
            $trimmed = trim($value);
            if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
                if (! json_validate($trimmed)) {
                    throw ValidationException::withMessages([
                        $paramName => sprintf('The %s parameter contains an invalid JSON string.', $paramName),
                    ]);
                }

                return json_decode($trimmed, true);
            }
        }

        return $value;
    }

    /**
     * Normalize a string, comma-separated string, or array into a list of trimmed non-empty strings.
     *
     * @return list<string>|null
     */
    private function normalizeStringList(mixed $value, string $paramName): ?array
    {
        $value = $this->decodeJsonIfString($value, $paramName);

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return null;
        }

        $result = [];

        foreach ($value as $item) {
            if (! is_scalar($item) && $item !== null) {
                return null;
            }

            $str = trim((string) $item);
            if ($str !== '') {
                $result[] = $str;
            }
        }

        return array_values(array_unique($result));
    }

    private function normalizePositiveInt(mixed $value, int $default): int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            $int = (int) $value;

            return $int > 0 ? $int : $default;
        }

        return $default;
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
