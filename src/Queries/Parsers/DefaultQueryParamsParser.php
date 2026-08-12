<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Parsers;

use Hatchyu\Steward\Queries\Contracts\QueryParamsParserContract;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Hatchyu\Steward\Queries\Params\SortField;
use Hatchyu\Steward\Queries\Parsers\Concerns\NormalizesQueryParams;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final readonly class DefaultQueryParamsParser implements QueryParamsParserContract
{
    use NormalizesQueryParams;

    public function __construct(
        private int $defaultSize = 15,
        private int $maxSize = 100,
    ) {}

    public function parseRequest(Request $request): QueryParams
    {
        /** @var array<string, mixed> $params */
        $params = $request->query();

        return $this->parse($params);
    }

    public function parse(array $params): QueryParams
    {
        $hasFilter = array_key_exists('filter', $params);
        $hasSort = array_key_exists('sort', $params);
        $hasInclude = array_key_exists('include', $params);
        $hasFields = array_key_exists('fields', $params);

        $search = $this->normalizeSearch($params['search'] ?? null);
        $filters = $this->normalizeFilters($params['filter'] ?? null, $hasFilter);
        $sort = $this->normalizeSort($params['sort'] ?? null, $hasSort);
        $includes = $this->normalizeIncludes($params['include'] ?? null, $hasInclude);
        $fields = $this->normalizeFields($params['fields'] ?? null, $hasFields);

        $pageParam = $this->decodeJsonIfString($params['page'] ?? null, 'page');
        $cursor = $this->normalizeCursor($params['cursor'] ?? (is_array($pageParam) ? ($pageParam['cursor'] ?? null) : null));

        [$page, $size] = $this->normalizePagination(
            $pageParam,
            $params['size'] ?? null
        );

        return new QueryParams(
            search: $search,
            filters: $filters,
            sort: $sort,
            includes: $includes,
            fields: $fields,
            page: $page,
            size: $size,
            cursor: $cursor,
        );
    }

    private function normalizeCursor(mixed $cursor): ?string
    {
        if (! is_string($cursor)) {
            return null;
        }

        $cursor = trim($cursor);

        return $cursor === '' ? null : $cursor;
    }

    private function normalizeSearch(mixed $search): ?string
    {
        if (! is_string($search)) {
            return null;
        }

        $search = trim($search);

        return $search === '' ? null : $search;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeFilters(mixed $filters, bool $provided): array
    {
        $filters = $this->decodeJsonIfString($filters, 'filter');
        $normalized = [];

        if (is_array($filters)) {
            $normalized = $filters;
        } elseif ($provided) {
            throw ValidationException::withMessages([
                'filter' => 'The filter must be an array or a JSON object string.',
            ]);
        }

        $normalized = $this->normalizeFilterValues($normalized);

        foreach ($normalized as $key => $filterValue) {
            if (! is_string($key) || ! preg_match('/^[A-Za-z_][A-Za-z0-9_\.]*$/', $key)) {
                throw ValidationException::withMessages([
                    'filter' => sprintf('Invalid filter key: %s.', (string) $key),
                ]);
            }

            if ($this->isScalarOrNull($filterValue)) {
                continue;
            }

            if (is_array($filterValue) && $this->isScalarList($filterValue)) {
                continue;
            }

            throw ValidationException::withMessages([
                'filter' => sprintf(
                    'Invalid filter value for %s. Expected scalar, null, or list of scalars.',
                    $key
                ),
            ]);
        }

        return $normalized;
    }

    /**
     * @return list<SortField>
     */
    private function normalizeSort(mixed $sort, bool $provided): array
    {
        $sort = $this->decodeJsonIfString($sort, 'sort');

        if (is_string($sort)) {
            return $this->parseSortString($sort);
        }

        if (! is_array($sort)) {
            if ($provided) {
                throw ValidationException::withMessages([
                    'sort' => 'The sort must be a string or array.',
                ]);
            }

            return [];
        }

        $result = [];

        foreach ($sort as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $result[] = $this->parseSortToken($value);

                continue;
            }

            if (is_string($key) && is_string($value)) {
                $direction = strtolower(trim($value));
                if (! in_array($direction, ['asc', 'desc'], true)) {
                    throw ValidationException::withMessages([
                        'sort' => sprintf('Unsupported sort direction for %s: %s.', $key, $value),
                    ]);
                }

                $result[] = new SortField(
                    field: trim($key),
                    direction: $direction
                );

                continue;
            }

            throw ValidationException::withMessages([
                'sort' => 'Invalid sort array format.',
            ]);
        }

        return array_values(array_filter(
            $result,
            static fn (SortField $sortField): bool => $sortField->field !== ''
        ));
    }

    /**
     * @return list<SortField>
     */
    private function parseSortString(string $sort): array
    {
        if (trim($sort) === '') {
            return [];
        }

        return array_values(array_map(
            $this->parseSortToken(...),
            array_filter(array_map(trim(...), explode(',', $sort)))
        ));
    }

    private function parseSortToken(string $token): SortField
    {
        $token = trim($token);
        $isDesc = str_starts_with($token, '-');
        $field = $isDesc ? substr($token, 1) : $token;

        if (! is_string($field) || trim($field) === '' || ! preg_match('/^[A-Za-z_][A-Za-z0-9_\.]*$/', trim($field))) {
            throw ValidationException::withMessages([
                'sort' => sprintf('Invalid sort field: %s.', $field),
            ]);
        }

        return new SortField(
            field: trim($field),
            direction: $isDesc ? 'desc' : 'asc'
        );
    }

    /**
     * @return list<string>
     */
    private function normalizeIncludes(mixed $include, bool $provided): array
    {
        if ($include === null || $include === '') {
            return [];
        }

        $includes = $this->normalizeStringList($include, 'include');

        if ($includes === null) {
            if ($provided) {
                throw ValidationException::withMessages([
                    'include' => 'The include must be a comma-separated string or array.',
                ]);
            }

            return [];
        }

        foreach ($includes as $path) {
            if (! preg_match('/^[A-Za-z_][A-Za-z0-9_\.]*$/', $path)) {
                throw ValidationException::withMessages([
                    'include' => sprintf('Invalid include path: %s.', $path),
                ]);
            }
        }

        return $includes;
    }

    /**
     * @return array<string, list<string>>
     */
    private function normalizeFields(mixed $fields, bool $provided): array
    {
        $fields = $this->decodeJsonIfString($fields, 'fields');

        if ($fields === null) {
            return [];
        }

        if (! is_array($fields)) {
            if ($provided) {
                throw ValidationException::withMessages([
                    'fields' => 'The fields parameter must be an object keyed by resource type.',
                ]);
            }

            return [];
        }

        $normalized = [];

        foreach ($fields as $type => $fieldSet) {
            if (! is_string($type) || ! preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $type)) {
                throw ValidationException::withMessages([
                    'fields' => sprintf('Invalid resource type for fields: %s.', (string) $type),
                ]);
            }

            $fieldNames = $this->normalizeStringList($fieldSet, 'fields');

            if ($fieldNames === null) {
                throw ValidationException::withMessages([
                    'fields' => sprintf('The fields set for %s must be a string or list of strings.', (string) $type),
                ]);
            }

            foreach ($fieldNames as $fieldName) {
                if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $fieldName)) {
                    throw ValidationException::withMessages([
                        'fields' => sprintf('Invalid field name for %s: %s.', (string) $type, $fieldName),
                    ]);
                }
            }

            $normalized[(string) $type] = $fieldNames;
        }

        return $normalized;
    }

    /**
     * @return array{int, int}
     */
    private function normalizePagination(mixed $page, mixed $size): array
    {
        $page = $this->decodeJsonIfString($page, 'page');

        if (is_array($page)) {
            $size = $page['size'] ?? $size;
            $page = $page['number'] ?? 1;
        }

        $page = $this->normalizePositiveInt($page, 1);
        $size = $this->normalizePositiveInt($size, $this->defaultSize);
        $size = min($size, $this->maxSize);

        return [$page, $size];
    }
}
