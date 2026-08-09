<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Criteria\Searches;

use BackedEnum;
use Hatchyu\Steward\Queries\Criteria\AbstractCriterion;
use Stringable;
use UnexpectedValueException;

abstract class AbstractSearch extends AbstractCriterion
{
    protected function normalizeSearchValue(mixed $value): string|int|float|bool|null
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if (is_scalar($value)) {
            return $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        throw new UnexpectedValueException('Search value must be scalar, enum-backed, stringable, or null.');
    }
}
