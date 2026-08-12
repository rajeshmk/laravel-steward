<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Support;

final class QuerySyntax
{
    public const PATTERN_DOT_IDENTIFIER = '/^[A-Za-z_][A-Za-z0-9_\.]*$/';

    public const PATTERN_SIMPLE_IDENTIFIER = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    public const PATTERN_RESOURCE_TYPE = '/^[A-Za-z_][A-Za-z0-9_-]*$/';

    public static function isValidDotIdentifier(mixed $value): bool
    {
        return is_string($value) && preg_match(self::PATTERN_DOT_IDENTIFIER, $value) === 1;
    }

    public static function isValidSimpleIdentifier(mixed $value): bool
    {
        return is_string($value) && preg_match(self::PATTERN_SIMPLE_IDENTIFIER, $value) === 1;
    }

    public static function isValidResourceType(mixed $value): bool
    {
        return is_string($value) && preg_match(self::PATTERN_RESOURCE_TYPE, $value) === 1;
    }

    public static function isJsonPayloadString(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $trimmed = trim($value);

        return str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[');
    }

    public static function tryDecodeJson(mixed $value): mixed
    {
        if (self::isJsonPayloadString($value)) {
            $trimmed = trim((string) $value);
            if (json_validate($trimmed)) {
                return json_decode($trimmed, true);
            }
        }

        return $value;
    }

    /**
     * Normalizes a comma-separated string, JSON array string, or PHP array into a list of trimmed non-empty strings.
     *
     * @return list<string>
     */
    public static function parseStringList(mixed $value): array
    {
        $value = self::tryDecodeJson($value);

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $item) {
            if (is_scalar($item) && $item !== null) {
                $str = trim((string) $item);
                if ($str !== '') {
                    $result[] = $str;
                }
            }
        }

        return array_values(array_unique($result));
    }
}
