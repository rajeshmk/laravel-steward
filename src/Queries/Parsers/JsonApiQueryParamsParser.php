<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Parsers;

use Hatchyu\Steward\Queries\Contracts\QueryParamsParserContract;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Hatchyu\Steward\Queries\Params\SortField;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final readonly class JsonApiQueryParamsParser implements QueryParamsParserContract
{
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

        $filters = $this->normalizeFilters($params['filter'] ?? null, array_key_exists('filter', $params));
        $search = $this->normalizeSearch($params['search'] ?? null, $filters);
        $sort = $this->normalizeSort($params['sort'] ?? null, array_key_exists('sort', $params));
        $includes = $this->normalizeIncludes($params['include'] ?? null, array_key_exists('include', $params));
        $fields = $this->normalizeFields($params['fields'] ?? null, array_key_exists('fields', $params));
        $cursor = $this->normalizeCursor($params['page']['cursor'] ?? null);
        [$page, $size] = $this->normalizePagination($params['page'] ?? null, array_key_exists('page', $params));

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
        $normalized = [];

        if (is_array($filters)) {
            $normalized = $filters;
        } elseif (is_string($filters)) {
            $decoded = json_decode($filters, true);
            if (is_array($decoded)) {
                $normalized = $decoded;
            } elseif ($provided) {
                throw ValidationException::withMessages([
                    'filter' => 'The filter must be an object or a JSON object string.',
                ]);
            }
        } elseif ($provided) {
            throw ValidationException::withMessages([
                'filter' => 'The filter must be an object.',
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
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function normalizeFilterValues(array $filters): array
    {
        $out = [];

        foreach ($filters as $key => $value) {
            $out[$key] = $this->normalizeFilterValue($value);
        }

        return $out;
    }

    private function normalizeFilterValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map($this->normalizeFilterValue(...), $value);
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        return $value;
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

            if (! is_string($fieldSet)) {
                throw ValidationException::withMessages([
                    'fields' => sprintf('The fields set for %s must be a comma-separated string.', $type),
                ]);
            }

            $fieldNames = array_values(array_filter(array_map(trim(...), explode(',', $fieldSet))));

            foreach ($fieldNames as $fieldName) {
                if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $fieldName)) {
                    throw ValidationException::withMessages([
                        'fields' => sprintf('Invalid field name for %s: %s.', $type, $fieldName),
                    ]);
                }
            }

            $normalized[$type] = array_values(array_unique($fieldNames));
        }

        return $normalized;
    }

    /**
     * @return array{int, int}
     */
    private function normalizePagination(mixed $page, bool $provided): array
    {
        if (! $provided) {
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

    private function normalizePositiveInt(mixed $value, int $default): int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            $int = (int) $value;

            return $int > 0 ? $int : $default;
        }

        return $default;
    }

    private function isScalarOrNull(mixed $value): bool
    {
        return is_scalar($value) || $value === null;
    }

    /**
     * @param array<mixed> $value
     */
    private function isScalarList(array $value): bool
    {
        if (! array_is_list($value)) {
            return false;
        }

        return array_all($value, fn ($item): bool => $this->isScalarOrNull($item));
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
