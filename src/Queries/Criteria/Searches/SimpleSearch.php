<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Criteria\Searches;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class SimpleSearch extends AbstractSearch
{
    /** @param Builder<Model> $query @return Builder<Model> */
    public function apply(Builder $query, mixed $value): Builder
    {
        $value = $this->normalizeSearchValue($value);

        if ($value === null) {
            $query->orWhereNull($this->field);

            return $query;
        }

        $query->orWhere($this->field, '=', $value);

        return $query;
    }
}
