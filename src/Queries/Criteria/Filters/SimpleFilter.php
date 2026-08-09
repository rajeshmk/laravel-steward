<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Criteria\Filters;

use Illuminate\Database\Eloquent\Builder;

final class SimpleFilter extends AbstractFilter
{
    public function apply(Builder $query, mixed $value): Builder
    {
        $values = is_array($value) ? $value : [$value];
        $count = count($values);

        if ($count === 0) {
            return $query;
        }

        if ($count === 1) {
            $value = $values[0];

            if ($value === null) {
                return $query->whereNull($this->field);
            }

            return $query->where($this->field, $value);
        }

        return $query->whereIn($this->field, $values);
    }
}
