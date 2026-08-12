<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries;

use Hatchyu\Steward\Queries\Contracts\GetModelQueryContract;
use Hatchyu\Steward\Queries\Contracts\QueryParamsProcessorContract;
use Hatchyu\Steward\Queries\Criteria\Filters\AbstractFilter;
use Hatchyu\Steward\Queries\Criteria\Sorts\AbstractSort;
use Hatchyu\Steward\Queries\Params\QueryDefinition;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Hatchyu\Steward\Resources\Concerns\ResolvesJsonApiResourceMetadata;
use Hatchyu\Steward\Resources\JsonApiResource;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Cursor;
use Illuminate\Validation\ValidationException;

class GetModelQuery extends ModelQuery implements GetModelQueryContract
{
    use ResolvesJsonApiResourceMetadata;
    public function __construct(
        Model $model,
        protected readonly QueryParamsProcessorContract $queryParamsProcessor,
    ) {
        parent::__construct($model);
    }

    /**
     * @param array<int, string> $columns
     */
    public function first(array $columns = ['*']): ?Model
    {
        return $this->query()->first($columns);
    }

    /**
     * @param array<int, string> $columns
     */
    public function firstOrFail(array $columns = ['*']): Model
    {
        return $this->query()->firstOrFail($columns);
    }

    /**
     * @param array<int, string> $columns
     *
     * @return Collection<int, Model>
     */
    public function get(array $columns = ['*']): Collection
    {
        return $this->query()->get($columns);
    }

    /**
     * @param array<int, string> $columns
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null,
    ): LengthAwarePaginator {
        return $this->query()->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * @param array<int, string> $columns
     */
    public function cursorPaginate(
        int $perPage = 15,
        array $columns = ['*'],
        string $cursorName = 'cursor',
        int|string|Cursor|null $cursor = null,
    ): CursorPaginator {
        return $this->query()->cursorPaginate($perPage, $columns, $cursorName, $cursor);
    }

    protected function definition(): QueryDefinition
    {
        return new QueryDefinition();
    }

    protected function applyQueryParams(Builder $query, QueryParams $params): Builder
    {
        $definition = $this->definition();

        $this->ensureAllowedFilters($params, $definition);
        $this->ensureAllowedSort($params, $definition);
        $this->ensureAllowedSearch($params, $definition);
        $this->ensureAllowedIncludes($params);
        $this->ensureAllowedFieldsets($params);

        $query = $this->applyFieldProjection($query, $params);
        $query = $this->queryParamsProcessor->apply($query, $params, $definition);
        $query = $this->applyIncludes($query, $params);

        if ($params->sort === []) {
            $column = config('steward.default_sort_column');

            if (! is_string($column) || trim($column) === '') {
                $column = $this->model->getKeyName();
            }

            $direction = strtolower(trim((string) config('steward.default_sort_direction', 'desc')));

            if (! in_array($direction, ['asc', 'desc'], true)) {
                $direction = 'desc';
            }

            $query->orderBy($this->model->qualifyColumn($column), $direction);
        }

        return $query;
    }

    /**
     * @param array<int, string> $columns
     */
    protected function paginateByQueryParams(
        Builder $query,
        QueryParams $params,
        array $columns = ['*'],
        string $pageName = 'page',
    ): LengthAwarePaginator {
        return $this->queryParamsProcessor->paginate($query, $params, $columns, $pageName);
    }

    /**
     * @param array<int, string> $columns
     */
    protected function cursorPaginateByQueryParams(
        Builder $query,
        QueryParams $params,
        array $columns = ['*'],
        string $cursorName = 'cursor',
    ): CursorPaginator {
        $this->ensureCursorOrderIsUnique($query);

        return $this->queryParamsProcessor->cursorPaginate($query, $params, $columns, $cursorName);
    }

    /**
     * @return class-string<JsonApiResource>|null
     */
    protected function jsonApiResource(): ?string
    {
        return null;
    }

    /**
     * Explicit include allowlist for queries without JsonApiResource metadata.
     * An empty list means that includes are not supported.
     *
     * @return list<string>
     */
    protected function allowedIncludes(): array
    {
        return [];
    }

    private function ensureAllowedFilters(QueryParams $params, QueryDefinition $definition): void
    {
        if ($params->filters === []) {
            return;
        }

        $allowed = array_map(
            static fn (AbstractFilter $filter): string => $filter->name(),
            $definition->filterable
        );

        $unknown = array_diff(array_keys($params->filters), $allowed);
        if ($unknown === []) {
            return;
        }

        throw ValidationException::withMessages([
            'filter' => sprintf('Unsupported filter keys: %s.', implode(', ', $unknown)),
        ]);
    }

    private function ensureAllowedSort(QueryParams $params, QueryDefinition $definition): void
    {
        if ($params->sort === []) {
            return;
        }

        $unknown = [];
        $allowed = array_map(
            static fn (AbstractSort $sort): string => $sort->name(),
            $definition->sortable
        );

        foreach ($params->sort as $sortField) {
            if (! in_array($sortField->field, $allowed, true)) {
                $unknown[] = $sortField->field;
            }
        }

        if ($unknown === []) {
            return;
        }

        throw ValidationException::withMessages([
            'sort' => sprintf('Unsupported sort fields: %s.', implode(', ', array_unique($unknown))),
        ]);
    }

    private function ensureAllowedSearch(QueryParams $params, QueryDefinition $definition): void
    {
        if ($params->search === null) {
            return;
        }

        if ($definition->searchable !== []) {
            return;
        }

        throw ValidationException::withMessages([
            'search' => 'Search is not supported for this query.',
        ]);
    }

    private function ensureAllowedIncludes(QueryParams $params): void
    {
        if ($params->includes === []) {
            return;
        }

        $allowed = $this->resolvedAllowedIncludes();
        $unknown = array_values(array_diff($params->includes, $allowed));

        if ($unknown === []) {
            return;
        }

        throw ValidationException::withMessages([
            'include' => sprintf('Unsupported include paths: %s.', implode(', ', $unknown)),
        ]);
    }

    private function applyIncludes(Builder $query, QueryParams $params): Builder
    {
        if ($params->includes === []) {
            return $query;
        }

        $resource = $this->jsonApiResource();

        if (! is_string($resource) || ! is_subclass_of($resource, JsonApiResource::class)) {
            // Includes were checked against allowedIncludes() before reaching
            // this point.
            return $query->with($params->includes);
        }

        $eagerLoads = [];

        foreach ($params->includes as $path) {
            $resourceClass = $resource::jsonApiResourceForIncludePath($path);
            $relation = $this->relationForPath($path);
            $nestedIncludes = $this->nestedIncludeSuffixes($path, $params->includes);

            if (! is_string($resourceClass) || ! $relation instanceof Relation) {
                $eagerLoads[$path] = static function (): void {};

                continue;
            }

            $eagerLoads[$path] = function (Relation|Builder $builder) use ($params, $resourceClass, $relation, $nestedIncludes): void {
                $query = $builder instanceof Relation ? $builder->getQuery() : $builder;

                $this->applyRelatedFieldProjection(
                    builder: $query,
                    resource: $resourceClass,
                    relation: $relation,
                    nestedIncludes: $nestedIncludes,
                    params: $params,
                );
            };
        }

        return $query->with($eagerLoads);
    }

    private function ensureAllowedFieldsets(QueryParams $params): void
    {
        if ($params->fields === []) {
            return;
        }

        $resource = $this->jsonApiResource();

        if (! is_string($resource) || ! is_subclass_of($resource, JsonApiResource::class)) {
            throw ValidationException::withMessages([
                'fields' => 'Sparse fieldsets are not supported for this query.',
            ]);
        }

        $allowedFieldsets = $resource::jsonApiAllowedFieldsets();

        foreach ($params->fields as $type => $fields) {
            if (! array_key_exists($type, $allowedFieldsets)) {
                throw ValidationException::withMessages([
                    'fields' => sprintf('Unsupported fields type: %s.', $type),
                ]);
            }

            $unknown = array_values(array_diff($fields, $allowedFieldsets[$type]));

            if ($unknown !== []) {
                throw ValidationException::withMessages([
                    'fields' => sprintf('Unsupported field for %s: %s.', $type, implode(', ', $unknown)),
                ]);
            }
        }
    }

    private function applyFieldProjection(Builder $query, QueryParams $params): Builder
    {
        $resource = $this->jsonApiResource();

        if ($params->fields === [] || ! is_string($resource) || ! is_subclass_of($resource, JsonApiResource::class)) {
            return $query;
        }

        $type = $resource::jsonApiResourceType();
        $requestedFields = $params->fields[$type] ?? null;

        if (! is_array($requestedFields) || $requestedFields === []) {
            return $query;
        }

        $columnMap = $resource::jsonApiFieldColumnMap();
        $columns = [$this->model->qualifyColumn($this->model->getKeyName())];

        foreach ($requestedFields as $field) {
            foreach ($columnMap[$field] ?? [] as $column) {
                $columns[] = $this->qualifyProjectionColumn($column);
            }
        }

        $columns = [
            ...$columns,
            ...$this->relationSupportColumns($params->includes),
        ];

        $query->select(array_values(array_unique($columns)));

        return $query;
    }

    private function qualifyProjectionColumn(string $column): string
    {
        return str_contains($column, '.') ? $column : $this->model->qualifyColumn($column);
    }

    /**
     * @param list<string> $includes
     *
     * @return list<string>
     */
    private function relationSupportColumns(array $includes): array
    {
        $columns = [];

        foreach (array_unique(array_map(
            static fn (string $path): string => explode('.', $path)[0],
            $includes
        )) as $relationName) {
            if ($relationName === '' || ! method_exists($this->model, $relationName)) {
                continue;
            }

            $relation = $this->model->{$relationName}();

            if ($relation instanceof MorphTo) {
                $columns[] = $this->model->qualifyColumn($relation->getForeignKeyName());
                $columns[] = $this->model->qualifyColumn($relation->getMorphType());

                continue;
            }

            if ($relation instanceof BelongsTo) {
                $columns[] = $this->model->qualifyColumn($relation->getForeignKeyName());

                continue;
            }

            if ($relation instanceof MorphOneOrMany) {
                $columns[] = $this->model->qualifyColumn($relation->getLocalKeyName());

                continue;
            }

            if ($relation instanceof HasOneOrMany) {
                $columns[] = $this->model->qualifyColumn($relation->getLocalKeyName());

                continue;
            }

            if ($relation instanceof BelongsToMany) {
                $columns[] = $this->model->qualifyColumn($relation->getParentKeyName());
            }
        }

        return array_values(array_unique($columns));
    }

    /**
     * @param class-string<JsonApiResource> $resource
     * @param list<string>                  $nestedIncludes
     */
    private function applyRelatedFieldProjection(
        Builder $builder,
        string $resource,
        Relation $relation,
        array $nestedIncludes,
        QueryParams $params,
    ): void {
        if ($relation instanceof BelongsToMany) {
            return;
        }

        $requestedFields = $params->fields[$resource::jsonApiResourceType()] ?? null;

        if (! is_array($requestedFields) || $requestedFields === []) {
            return;
        }

        $relatedModel = $builder->getModel();
        $columns = [$relatedModel->qualifyColumn($relatedModel->getKeyName())];

        foreach ($requestedFields as $field) {
            foreach ($resource::jsonApiFieldColumnMap()[$field] ?? [] as $column) {
                $columns[] = str_contains($column, '.') ? $column : $relatedModel->qualifyColumn($column);
            }
        }

        $columns = [
            ...$columns,
            ...$this->incomingRelationColumns($relation),
            ...$this->relationSupportColumnsForModel(
                model: $relatedModel,
                includes: $nestedIncludes,
                qualifier: $relatedModel->qualifyColumn(...)
            ),
        ];

        $builder->select(array_values(array_unique($columns)));
    }

    /**
     * @param list<string>             $includes
     * @param callable(string): string $qualifier
     *
     * @return list<string>
     */
    private function relationSupportColumnsForModel(Model $model, array $includes, callable $qualifier): array
    {
        $columns = [];

        foreach (array_unique(array_map(
            static fn (string $path): string => explode('.', $path)[0],
            $includes
        )) as $relationName) {
            if ($relationName === '' || ! method_exists($model, $relationName)) {
                continue;
            }

            $relation = $model->{$relationName}();
            $columns = [...$columns, ...$this->incomingRelationColumns($relation, $qualifier)];
        }

        return array_values(array_unique($columns));
    }

    /**
     * @return list<string>
     */
    private function incomingRelationColumns(Relation $relation, ?callable $qualifier = null): array
    {
        $qualifier ??= $relation->getRelated()->qualifyColumn(...);
        $columns = [];

        if ($relation instanceof MorphTo) {
            $columns[] = $qualifier($relation->getOwnerKeyName());

            return $columns;
        }

        if ($relation instanceof BelongsTo) {
            $columns[] = $qualifier($relation->getOwnerKeyName());

            return $columns;
        }

        if ($relation instanceof MorphOneOrMany) {
            $columns[] = $qualifier($relation->getForeignKeyName());
            $columns[] = $qualifier($relation->getMorphType());

            return $columns;
        }

        if ($relation instanceof HasOneOrMany) {
            $columns[] = $qualifier($relation->getForeignKeyName());

            return $columns;
        }

        if ($relation instanceof BelongsToMany) {
            $columns[] = $qualifier($relation->getRelatedKeyName());
        }

        return $columns;
    }

    private function relationForPath(string $path): ?Relation
    {
        $model = $this->model;
        $relation = null;

        foreach (array_filter(explode('.', $path)) as $segment) {
            if (! method_exists($model, $segment)) {
                return null;
            }

            $relation = $model->{$segment}();
            if (! $relation instanceof Relation) {
                return null;
            }

            $model = $relation->getRelated();
        }

        return $relation;
    }

    /**
     * @param list<string> $allIncludes
     *
     * @return list<string>
     */
    private function nestedIncludeSuffixes(string $path, array $allIncludes): array
    {
        $prefix = $path . '.';
        $nested = [];

        foreach ($allIncludes as $include) {
            if (str_starts_with($include, $prefix)) {
                $nested[] = substr($include, strlen($prefix));
            }
        }

        return array_values(array_filter($nested));
    }

    private function ensureCursorOrderIsUnique(Builder $query): void
    {
        $keyName = $this->model->getKeyName();
        $keyColumn = $this->model->qualifyColumn($keyName);
        $orders = $query->getQuery()->orders ?? [];

        foreach ($orders as $order) {
            if (($order['column'] ?? null) === $keyColumn || ($order['column'] ?? null) === $keyName) {
                return;
            }
        }

        $lastOrder = $orders === [] ? null : $orders[array_key_last($orders)];
        $direction = is_array($lastOrder) && in_array($lastOrder['direction'] ?? null, ['asc', 'desc'], true)
            ? $lastOrder['direction']
            : 'asc';

        $query->orderBy($keyColumn, $direction);
    }
}
