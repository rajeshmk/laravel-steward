<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Criteria\Searches;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractStringSearch extends AbstractSearch
{
    protected bool $ignoreEmptyStrings = true;

    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    public function apply(Builder $query, mixed $value): Builder
    {
        $value = $this->normalizeSearchValue($value);

        if ($this->ignoreEmptyStrings && $value === '') {
            return $query;
        }

        if ($value === null) {
            $query->orWhereNull($this->field);

            return $query;
        }

        if (! is_string($value)) {
            $query->orWhere($this->field, '=', $value);

            return $query;
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

    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    protected function applyStringSearch(Builder $query, string $value): Builder
    {
        $escapedValue = $this->escapeLike($value);

        $query->orWhere($this->field, 'like', $escapedValue);

        return $query;
    }
}
