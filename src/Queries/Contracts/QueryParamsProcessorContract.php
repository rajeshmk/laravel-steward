<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Contracts;

use Hatchyu\Steward\Queries\Params\QueryDefinition;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface QueryParamsProcessorContract
{
    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    public function apply(Builder $query, QueryParams $params, QueryDefinition $definition): Builder;

    /**
     * @param Builder<Model> $query
     * @param array<int, string> $columns
     * @return LengthAwarePaginator<int, Model>
     */
    public function paginate(
        Builder $query,
        QueryParams $params,
        array $columns = ['*'],
        string $pageName = 'page',
    ): LengthAwarePaginator;

    /**
     * @param Builder<Model> $query
     * @param array<int, string> $columns
     * @return CursorPaginator<int, Model>
     */
    public function cursorPaginate(
        Builder $query,
        QueryParams $params,
        array $columns = ['*'],
        string $cursorName = 'cursor',
    ): CursorPaginator;
}
