<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries;

use Hatchyu\ApiExceptions\Model\ModelNotFoundException as ApiModelNotFoundException;
use Hatchyu\Steward\Exceptions\ModelIdsNotFoundException;
use Hatchyu\Steward\Queries\Contracts\FindModelQueryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException as EloquentModelNotFoundException;
use InvalidArgumentException;

class FindModelQuery extends ModelQuery implements FindModelQueryContract
{
    /**
     * @param array<int, string>|string $columns
     */
    public function byId(int|string $id, array|string $columns = ['*']): ?Model
    {
        $id = $this->normalizeId($id);

        return $this->query()->find($id, $columns);
    }

    /**
     * @param array<int, string>|string $columns
     */
    public function byIdOrFail(int|string $id, array|string $columns = ['*']): Model
    {
        $id = $this->normalizeId($id);

        try {
            return $this->query()->findOrFail($id, $columns);
        } catch (EloquentModelNotFoundException) {
            throw ApiModelNotFoundException::forModel($this->modelClass(), $id);
        }
    }

    /**
     * @param list<int|string>          $ids
     * @param array<int, string>|string $columns
     *
     * @return Collection<int, Model>
     */
    public function byIds(array $ids, array|string $columns = ['*']): Collection
    {
        $ids = $this->normalizeIds($ids);

        if ($ids === []) {
            return $this->model->newCollection();
        }

        return $this->query()->whereKey($ids)->get($columns);
    }

    /**
     * @param list<int|string>          $ids
     * @param array<int, string>|string $columns
     *
     * @return Collection<int, Model>
     */
    public function byIdsOrFail(array $ids, array|string $columns = ['*']): Collection
    {
        $normalizedIds = $this->normalizeIds($ids);
        $instances = $this->byIds($normalizedIds, $columns);

        $found = $instances->mapWithKeys(static fn (Model $model): array => [(string) $model->getKey() => true]);
        $missing = array_values(array_filter(
            $normalizedIds,
            static fn (int|string $id): bool => ! $found->has((string) $id)
        ));

        if ($missing !== []) {
            throw new ModelIdsNotFoundException($this->modelClass(), $missing);
        }

        return $instances;
    }

    /**
     * Alias for byId.
     *
     * @param array<int, string>|string $columns
     */
    public function find(int|string $id, array|string $columns = ['*']): ?Model
    {
        return $this->byId($id, $columns);
    }

    /**
     * Alias for byIdOrFail.
     *
     * @param array<int, string>|string $columns
     */
    public function findOrFail(int|string $id, array|string $columns = ['*']): Model
    {
        return $this->byIdOrFail($id, $columns);
    }

    /**
     * Alias for byIds.
     *
     * @param list<int|string>          $ids
     * @param array<int, string>|string $columns
     *
     * @return Collection<int, Model>
     */
    public function findMany(array $ids, array|string $columns = ['*']): Collection
    {
        return $this->byIds($ids, $columns);
    }

    /**
     * Alias for byIdsOrFail.
     *
     * @param list<int|string>          $ids
     * @param array<int, string>|string $columns
     *
     * @return Collection<int, Model>
     */
    public function findManyOrFail(array $ids, array|string $columns = ['*']): Collection
    {
        return $this->byIdsOrFail($ids, $columns);
    }

    public function execute(int|string $id): Model
    {
        return $this->byIdOrFail($id);
    }

    /**
     * @param array<int, mixed> $ids
     *
     * @return list<int|string>
     */
    private function normalizeIds(array $ids): array
    {
        $normalized = array_map($this->normalizeId(...), $ids);

        return array_values(array_unique($normalized, SORT_REGULAR));
    }

    private function normalizeId(mixed $id): int|string
    {
        if (is_int($id)) {
            return $id;
        }

        if (! is_string($id)) {
            throw new InvalidArgumentException('Expected model id of type `int|string`.');
        }

        if ($id === '') {
            throw new InvalidArgumentException('Expected non-empty model id of type `int|string`.');
        }

        return $id;
    }
}
