<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Contracts;

use Hatchyu\Steward\Queries\Params\QueryDefinition;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface QueryParamsProcessorContract
{
    public function apply(Builder $query, QueryParams $params, QueryDefinition $definition): Builder;

    /**
     * @param array<int, string> $columns
     */
    public function paginate(
        Builder $query,
        QueryParams $params,
        array $columns = ['*'],
        string $pageName = 'page',
    ): LengthAwarePaginator;

    /**
     * @param array<int, string> $columns
     */
    public function cursorPaginate(
        Builder $query,
        QueryParams $params,
        array $columns = ['*'],
        string $cursorName = 'cursor',
    ): CursorPaginator;
}
