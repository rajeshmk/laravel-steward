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
}
