<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface FindModelQueryContract
{
    /**
     * @return class-string<Model>
     */
    public function modelClass(): string;

    /**
     * @param array<int, string>|string $columns
     */
    public function byId(int|string $id, array|string $columns = ['*']): ?Model;

    /**
     * @param array<int, string>|string $columns
     */
    public function byIdOrFail(int|string $id, array|string $columns = ['*']): Model;

    /**
     * @param list<int|string>          $ids
     * @param array<int, string>|string $columns
     *
     * @return Collection<int, Model>
     */
    public function byIds(array $ids, array|string $columns = ['*']): Collection;

    /**
     * @param list<int|string>          $ids
     * @param array<int, string>|string $columns
     *
     * @return Collection<int, Model>
     */
    public function byIdsOrFail(array $ids, array|string $columns = ['*']): Collection;

    /**
     * Alias for byId.
     *
     * @param array<int, string>|string $columns
     */
    public function find(int|string $id, array|string $columns = ['*']): ?Model;

    /**
     * Alias for byIdOrFail.
     *
     * @param array<int, string>|string $columns
     */
    public function findOrFail(int|string $id, array|string $columns = ['*']): Model;

    /**
     * Alias for byIds.
     *
     * @param list<int|string>          $ids
     * @param array<int, string>|string $columns
     *
     * @return Collection<int, Model>
     */
    public function findMany(array $ids, array|string $columns = ['*']): Collection;

    /**
     * Alias for byIdsOrFail.
     *
     * @param list<int|string>          $ids
     * @param array<int, string>|string $columns
     *
     * @return Collection<int, Model>
     */
    public function findManyOrFail(array $ids, array|string $columns = ['*']): Collection;
}
