<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Criteria\Searches;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

final class PartialSearch extends AbstractStringSearch
{
    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    #[Override]
    protected function applyStringSearch(Builder $query, string $value): Builder
    {
        $escapedValue = $this->escapeLike($value);

        return $query->orWhere(function (Builder $nested) use ($value, $escapedValue): void {
            $nested->where($this->field, '=', $value)
                ->orWhere($this->field, 'like', '%' . $escapedValue . '%')
            ;
        });
    }
}
