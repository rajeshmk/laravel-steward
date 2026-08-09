<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Criteria\Searches;

use Illuminate\Database\Eloquent\Builder;
use Override;

final class StartsWithSearch extends AbstractStringSearch
{
    #[Override]
    protected function applyStringSearch(Builder $query, string $value): Builder
    {
        $escapedValue = $this->escapeLike($value);

        return $query->orWhere($this->field, 'like', $escapedValue . '%');
    }
}
