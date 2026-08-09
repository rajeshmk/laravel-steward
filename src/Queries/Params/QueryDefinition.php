<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Params;

use Hatchyu\Steward\Queries\Criteria\Filters\AbstractFilter;
use Hatchyu\Steward\Queries\Criteria\Filters\SimpleFilter;
use Hatchyu\Steward\Queries\Criteria\Searches\AbstractSearch;
use Hatchyu\Steward\Queries\Criteria\Searches\PartialSearch;
use Hatchyu\Steward\Queries\Criteria\Sorts\AbstractSort;
use Hatchyu\Steward\Queries\Criteria\Sorts\SimpleSort;

final readonly class QueryDefinition
{
    /**
     * @var list<AbstractSearch>
     */
    public array $searchable;

    /**
     * @var list<AbstractFilter>
     */
    public array $filterable;

    /**
     * @var list<AbstractSort>
     */
    public array $sortable;

    /**
     * @param list<string|AbstractSearch> $searchable
     * @param list<string|AbstractFilter> $filterable
     * @param list<string|AbstractSort>   $sortable
     */
    public function __construct(
        array $searchable = [],
        array $filterable = [],
        array $sortable = [],
    ) {
        $this->searchable = $this->normalizeSearches($searchable);
        $this->filterable = $this->normalizeFilters($filterable);
        $this->sortable = $this->normalizeSorts($sortable);
    }

    /**
     * @param list<string|AbstractSearch> $searches
     *
     * @return list<AbstractSearch>
     */
    private function normalizeSearches(array $searches): array
    {
        return array_map(function (string|AbstractSearch $search): AbstractSearch {
            if (is_string($search)) {
                return new PartialSearch($search);
            }

            return $search;
        }, $searches);
    }

    /**
     * @param list<string|AbstractFilter> $filters
     *
     * @return list<AbstractFilter>
     */
    private function normalizeFilters(array $filters): array
    {
        return array_map(function (string|AbstractFilter $filter): AbstractFilter {
            if (is_string($filter)) {
                return new SimpleFilter($filter);
            }

            return $filter;
        }, $filters);
    }

    /**
     * @param list<string|AbstractSort> $sorts
     *
     * @return list<AbstractSort>
     */
    private function normalizeSorts(array $sorts): array
    {
        return array_map(function (string|AbstractSort $sort): AbstractSort {
            if (is_string($sort)) {
                return new SimpleSort($sort);
            }

            return $sort;
        }, $sorts);
    }
}
