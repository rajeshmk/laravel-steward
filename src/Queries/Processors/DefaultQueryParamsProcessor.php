<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Processors;

use Hatchyu\Steward\Queries\Contracts\QueryParamsProcessorContract;
use Hatchyu\Steward\Queries\Criteria\Filters\AbstractFilter;
use Hatchyu\Steward\Queries\Criteria\Sorts\AbstractSort;
use Hatchyu\Steward\Queries\Params\QueryDefinition;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class DefaultQueryParamsProcessor implements QueryParamsProcessorContract
{
    public function apply(Builder $query, QueryParams $params, QueryDefinition $definition): Builder
    {
        $this->applySearch($query, $params, $definition);
        $this->applyFilters($query, $params, $definition);
        $this->applySort($query, $params, $definition);

        return $query;
    }

    public function paginate(
        Builder $query,
        QueryParams $params,
        array $columns = ['*'],
        string $pageName = 'page',
    ): LengthAwarePaginator {
        return $query->paginate(
            perPage: $params->size,
            columns: $columns,
            pageName: $pageName,
            page: $params->page
        );
    }

    public function cursorPaginate(
        Builder $query,
        QueryParams $params,
        array $columns = ['*'],
        string $cursorName = 'cursor',
    ): CursorPaginator {
        return $query->cursorPaginate(
            perPage: $params->size,
            columns: $columns,
            cursorName: $cursorName,
            cursor: $params->cursor
        );
    }

    private function applySearch(Builder $query, QueryParams $params, QueryDefinition $definition): void
    {
        if ($params->search === null || $definition->searchable === []) {
            return;
        }

        $query->where(function (Builder $builder) use ($params, $definition): void {
            foreach ($definition->searchable as $search) {
                $search->apply($builder, $params->search);
            }
        });
    }

    private function applyFilters(Builder $query, QueryParams $params, QueryDefinition $definition): void
    {
        foreach ($params->filters as $field => $value) {
            $filter = $this->findFilter($definition, $field);
            if (! $filter instanceof AbstractFilter) {
                continue;
            }

            $filter->apply($query, $value);
        }
    }

    private function applySort(Builder $query, QueryParams $params, QueryDefinition $definition): void
    {
        foreach ($params->sort as $sortField) {
            $sort = $this->findSort($definition, $sortField->field);
            if (! $sort instanceof AbstractSort) {
                continue;
            }

            $sort->apply($query, $sortField->direction);
        }
    }

    private function findFilter(QueryDefinition $definition, string $name): ?AbstractFilter
    {
        foreach ($definition->filterable as $filter) {
            if ($filter->matches($name)) {
                return $filter;
            }
        }

        return null;
    }

    private function findSort(QueryDefinition $definition, string $name): ?AbstractSort
    {
        foreach ($definition->sortable as $sort) {
            if ($sort->matches($name)) {
                return $sort;
            }
        }

        return null;
    }
}
