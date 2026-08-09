<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Criteria\Sorts;

use Illuminate\Database\Eloquent\Builder;

final class SimpleSort extends AbstractSort
{
    public function apply(Builder $query, mixed $value): Builder
    {
        $direction = $value === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($this->field, $direction);
    }
}
