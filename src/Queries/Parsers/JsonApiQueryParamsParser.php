<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Parsers;

use Hatchyu\Steward\Queries\Contracts\QueryParamsParserContract;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Hatchyu\Steward\Queries\Params\SortField;
use Hatchyu\Steward\Queries\Parsers\Concerns\NormalizesQueryParams;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final readonly class JsonApiQueryParamsParser implements QueryParamsParserContract
{
    use NormalizesQueryParams;

    public function __construct(
        private int $defaultSize = 15,
        private int $maxSize = 100,
    ) {}

    public function parseRequest(Request $request): QueryParams
    {
        /** @var array<string, mixed> $params */
        $params = $this->extractParamsFromRequest($request);

        return $this->parse($params);
    }

    public function parse(array $params): QueryParams
    {
        if (array_key_exists('size', $params)) {
            throw ValidationException::withMessages([
                'size' => 'Use page[size] for JSON:API pagination.',
            ]);
        }

        if (array_key_exists('cursor', $params)) {
            throw ValidationException::withMessages([
                'cursor' => 'Use page[cursor] for JSON:API cursor pagination.',
            ]);
        }

        $pageParam = $this->decodeJsonIfString($params['page'] ?? null, 'page');
        $filters = $this->normalizeFilters($params['filter'] ?? null, array_key_exists('filter', $params));
        $search = $this->normalizeSearch($params['search'] ?? null, $filters);
        $sort = $this->normalizeSort($params['sort'] ?? null, array_key_exists('sort', $params));
        $includes = $this->normalizeIncludes($params['include'] ?? null, array_key_exists('include', $params));
        $fields = $this->normalizeFields($params['fields'] ?? null, array_key_exists('fields', $params));

        $cursor = $this->normalizeCursor(is_array($pageParam) ? ($pageParam['cursor'] ?? null) : null);
        [$page, $size] = $this->normalizePagination($pageParam, array_key_exists('page', $params));

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

    /**
     * @param array<string, mixed> $filters
     */
    private function normalizeSearch(mixed $search, array &$filters): ?string
    {
        if (is_string($search)) {
            $search = trim($search);

            return $search === '' ? null : $search;
        }

        $filterSearch = $filters['search'] ?? null;
        if (! is_string($filterSearch)) {
            return null;
        }

        unset($filters['search']);
        $filterSearch = trim($filterSearch);

        return $filterSearch === '' ? null : $filterSearch;
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
                'filter' => 'The filter must be an object or a JSON object string.',
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
        if ($sort === null || $sort === '') {
            return [];
        }

        if (! is_string($sort)) {
            if ($provided) {
                throw ValidationException::withMessages([
                    'sort' => 'JSON:API sort must be a comma-separated string.',
                ]);
            }

            return [];
        }

        return $this->parseSortString($sort);
    }

    /**
     * @return list<string>
     */
    private function normalizeIncludes(mixed $include, bool $provided): array
    {
        if ($include === null || $include === '') {
            return [];
        }

        if (! is_string($include)) {
            if ($provided) {
                throw ValidationException::withMessages([
                    'include' => 'JSON:API include must be a comma-separated string.',
                ]);
            }

            return [];
        }

        $includes = array_values(array_filter(array_map(trim(...), explode(',', $include))));

        foreach ($includes as $path) {
            if (! preg_match('/^[A-Za-z_][A-Za-z0-9_\.]*$/', $path)) {
                throw ValidationException::withMessages([
                    'include' => sprintf('Invalid include path: %s.', $path),
                ]);
            }
        }

        return array_values(array_unique($includes));
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
                    'fields' => 'JSON:API fields must be an object keyed by resource type.',
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
    private function normalizePagination(mixed $page, bool $provided): array
    {
        $page = $this->decodeJsonIfString($page, 'page');

        if (! $provided && $page === null) {
            return [1, $this->defaultSize];
        }

        if (! is_array($page)) {
            throw ValidationException::withMessages([
                'page' => 'JSON:API pagination must use page[number] and page[size].',
            ]);
        }

        $pageNumber = $this->normalizePositiveInt($page['number'] ?? 1, 1);
        $size = $this->normalizePositiveInt($page['size'] ?? $this->defaultSize, $this->defaultSize);
        $size = min($size, $this->maxSize);

        return [$pageNumber, $size];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractParamsFromRequest(Request $request): array
    {
        /** @var array<string, mixed> $queryParams */
        $queryParams = $request->query();

        /** @var mixed $rawJsonBody */
        $rawJsonBody = $request->json()->all();
        if (! is_array($rawJsonBody) || $rawJsonBody === []) {
            return $queryParams;
        }

        /** @var array<string, mixed> $jsonBody */
        $jsonBody = $rawJsonBody;

        $bodyParams = $this->extractJsonApiBodyParams($jsonBody);
        if ($bodyParams === []) {
            return $queryParams;
        }

        // Query string should win over body for the same keys.
        return array_replace_recursive($bodyParams, $queryParams);
    }

    /**
     * @param array<string, mixed> $jsonBody
     *
     * @return array<string, mixed>
     */
    private function extractJsonApiBodyParams(array $jsonBody): array
    {
        $data = $jsonBody['data'] ?? null;
        if (! is_array($data)) {
            return $this->extractTopLevelBodyParams($jsonBody);
        }

        $attributes = $data['attributes'] ?? null;
        if (! is_array($attributes)) {
            return [];
        }

        return $this->extractTopLevelBodyParams($attributes);
    }

    /**
     * @param array<string, mixed> $source
     *
     * @return array<string, mixed>
     */
    private function extractTopLevelBodyParams(array $source): array
    {
        $allowed = ['search', 'filter', 'sort', 'include', 'fields', 'page', 'size'];
        $result = [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $source)) {
                $result[$key] = $source[$key];
            }
        }

        return $result;
    }
}
