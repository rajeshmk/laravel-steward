<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Contracts;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Cursor;

interface GetModelQueryContract
{
    /**
     * @return class-string<Model>
     */
    public function modelClass(): string;

    /**
     * @param array<int, string> $columns
     */
    public function first(array $columns = ['*']): ?Model;

    /**
     * @param array<int, string> $columns
     */
    public function firstOrFail(array $columns = ['*']): Model;

    /**
     * @param array<int, string> $columns
     *
     * @return Collection<int, Model>
     */
    public function get(array $columns = ['*']): Collection;

    /**
     * @param array<int, string> $columns
     *
     * @return LengthAwarePaginator<int, Model>
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null,
    ): LengthAwarePaginator;

    /**
     * @param array<int, string> $columns
     *
     * @return CursorPaginator<int, Model>
     */
    public function cursorPaginate(
        int $perPage = 15,
        array $columns = ['*'],
        string $cursorName = 'cursor',
        int|string|Cursor|null $cursor = null,
    ): CursorPaginator;
}
