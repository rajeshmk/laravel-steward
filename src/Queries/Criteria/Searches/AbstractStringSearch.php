<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Criteria\Searches;

use Illuminate\Database\Eloquent\Builder;

abstract class AbstractStringSearch extends AbstractSearch
{
    protected bool $ignoreEmptyStrings = true;

    public function apply(Builder $query, mixed $value): Builder
    {
        $value = $this->normalizeSearchValue($value);

        if ($this->ignoreEmptyStrings && $value === '') {
            return $query;
        }

        if ($value === null) {
            return $query->orWhereNull($this->field);
        }

        if (! is_string($value)) {
            return $query->orWhere($this->field, '=', $value);
        }

        return $this->applyStringSearch($query, $value);
    }

    protected function escapeLike(string $value, string $escapeChar = '\\'): string
    {
        return str_replace(
            [$escapeChar, '%', '_'],
            [$escapeChar . $escapeChar, $escapeChar . '%', $escapeChar . '_'],
            $value
        );
    }

    protected function applyStringSearch(Builder $query, string $value): Builder
    {
        $escapedValue = $this->escapeLike($value);

        return $query->orWhere($this->field, 'like', $escapedValue);
    }
}
