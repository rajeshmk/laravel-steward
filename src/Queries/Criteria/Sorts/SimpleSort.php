<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Criteria\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class SimpleSort extends AbstractSort
{
    /** @param Builder<Model> $query @return Builder<Model> */
    public function apply(Builder $query, mixed $value): Builder
    {
        $direction = $value === 'desc' ? 'desc' : 'asc';

        $query->orderBy($this->field, $direction);

        return $query;
    }
}
