<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Parsers;

use Hatchyu\Steward\Queries\Contracts\QueryParamsParserContract;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Hatchyu\Steward\Queries\Params\SortField;
use Hatchyu\Steward\Queries\Parsers\Concerns\NormalizesQueryParams;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class DefaultQueryParamsParser implements QueryParamsParserContract
{
    use NormalizesQueryParams;

    public function __construct(
        private readonly int $defaultSize = 15,
        private readonly int $maxSize = 100,
    ) {}

    public function parseRequest(Request $request): QueryParams
    {
        return $this->parse($request->query->all());
    }

    public function parse(array $params): QueryParams
    {
        $decodedFilter = $this->decodeJsonIfString($params['filter'] ?? null, 'filter');
        if ($decodedFilter !== null && is_array($decodedFilter)) {
            $params['filter'] = $decodedFilter;
        }

        return new QueryParams(
            search: $this->extractSearch($params),
            filters: $this->extractFilters($params),
            sort: $this->extractSort($params),
            includes: $this->extractIncludes($params),
            fields: $this->extractFields($params),
            page: $this->extractPageNumber($params),
            size: $this->extractPageSize($params),
            cursor: $this->extractCursor($params),
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    private function extractSearch(array $params): ?string
    {
        $search = $params['search'] ?? null;
        if (! is_string($search) && $search !== null) {
            throw ValidationException::withMessages([
                'search' => 'The search parameter must be a string.',
            ]);
        }

        $filter = $params['filter'] ?? null;
        if (is_array($filter) && array_key_exists('search', $filter) && is_string($filter['search'])) {
            $search = $filter['search'];
        }

        if ($search === null) {
            return null;
        }

        $trimmed = trim($search);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function extractFilters(array $params): array
    {
        $filters = $params['filter'] ?? null;
        if ($filters === null) {
            return [];
        }

        if (! is_array($filters) || ! QuerySyntax::isAssociativeArray($filters)) {
            throw ValidationException::withMessages([
                'filter' => 'The filter parameter must be an object or associative array.',
            ]);
        }

        unset($filters['search']);

        $normalized = $this->normalizeFilterValues($filters);

        foreach ($normalized as $key => $value) {
            if (! is_string($key) || ! QuerySyntax::isValidDotIdentifier((string) $key)) {
                throw ValidationException::withMessages([
                    'filter' => sprintf('Invalid filter key: %s.', (string) $key),
                ]);
            }

            if (! $this->isScalarOrNull($value) && (! is_array($value) || ! $this->isScalarList($value))) {
                throw ValidationException::withMessages([
                    'filter' => sprintf(
                        'Invalid filter value for %s. Expected scalar, null, or list of scalars.',
                        (string) $key
                    ),
                ]);
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return list<SortField>
     */
    private function extractSort(array $params): array
    {
        $sort = $this->decodeJsonIfString($params['sort'] ?? null, 'sort');

        if ($sort === null) {
            return [];
        }

        if (is_string($sort)) {
            return $this->parseSortString($sort);
        }

        if (is_array($sort)) {
            $sortFields = [];

            foreach ($sort as $key => $value) {
                if (is_int($key) && is_string($value)) {
                    $token = trim($value);
                    $isDesc = str_starts_with($token, '-');
                    $field = $isDesc ? substr($token, 1) : $token;

                    if ($field === '' || ! QuerySyntax::isValidDotIdentifier($field)) {
                        throw ValidationException::withMessages([
                            'sort' => sprintf('Invalid sort field: %s.', $field),
                        ]);
                    }

                    $sortFields[] = new SortField(
                        field: $field,
                        direction: $isDesc ? 'desc' : 'asc'
                    );

                    continue;
                }

                if (is_string($key) && is_string($value)) {
                    $field = trim($key);
                    $direction = strtolower(trim($value));

                    if ($field === '' || ! QuerySyntax::isValidDotIdentifier($field)) {
                        throw ValidationException::withMessages([
                            'sort' => sprintf('Invalid sort field: %s.', $field),
                        ]);
                    }

                    if (! in_array($direction, ['asc', 'desc'], true)) {
                        throw ValidationException::withMessages([
                            'sort' => sprintf('Unsupported sort direction for %s: %s.', $field, $value),
                        ]);
                    }

                    $sortFields[] = new SortField(
                        field: $field,
                        direction: $direction
                    );

                    continue;
                }

                throw ValidationException::withMessages([
                    'sort' => 'Invalid sort parameter format.',
                ]);
            }

            return $sortFields;
        }

        throw ValidationException::withMessages([
            'sort' => 'The sort parameter must be a string or array.',
        ]);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return list<string>
     */
    private function extractIncludes(array $params): array
    {
        $includes = $this->normalizeStringList($params['include'] ?? null, 'include');

        if ($includes === null) {
            if (array_key_exists('include', $params) && $params['include'] !== null) {
                throw ValidationException::withMessages([
                    'include' => 'The include parameter must be a comma-separated string or array.',
                ]);
            }

            return [];
        }

        foreach ($includes as $include) {
            if (! QuerySyntax::isValidDotIdentifier($include)) {
                throw ValidationException::withMessages([
                    'include' => sprintf('Invalid include path: %s.', $include),
                ]);
            }
        }

        return $includes;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, list<string>>
     */
    private function extractFields(array $params): array
    {
        $fields = $this->decodeJsonIfString($params['fields'] ?? null, 'fields');

        if ($fields === null) {
            return [];
        }

        if (! is_array($fields) || ! QuerySyntax::isAssociativeArray($fields)) {
            throw ValidationException::withMessages([
                'fields' => 'The fields parameter must be an object keyed by resource type.',
            ]);
        }

        $result = [];

        foreach ($fields as $type => $fieldSet) {
            if (! is_string($type) || ! QuerySyntax::isValidResourceType((string) $type)) {
                throw ValidationException::withMessages([
                    'fields' => sprintf('Invalid resource type for fields: %s.', (string) $type),
                ]);
            }

            $list = $this->normalizeStringList($fieldSet, sprintf('fields.%s', $type));

            if ($list === null) {
                throw ValidationException::withMessages([
                    'fields' => sprintf('The fields set for %s must be a string or list of strings.', (string) $type),
                ]);
            }

            foreach ($list as $field) {
                if (! QuerySyntax::isValidSimpleIdentifier($field)) {
                    throw ValidationException::withMessages([
                        'fields' => sprintf('Invalid field name for %s: %s.', (string) $type, $field),
                    ]);
                }
            }

            $result[(string) $type] = $list;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function extractPageNumber(array $params): int
    {
        $page = $this->decodeJsonIfString($params['page'] ?? null, 'page');

        if (is_array($page)) {
            if (array_key_exists('number', $page)) {
                return $this->normalizePositiveInt($page['number'], 1);
            }

            return 1;
        }

        return $this->normalizePositiveInt($page, 1);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function extractPageSize(array $params): int
    {
        if (array_key_exists('size', $params)) {
            return $this->normalizeBoundedPageSize($params['size'], $this->defaultSize, $this->maxSize);
        }

        $page = $this->decodeJsonIfString($params['page'] ?? null, 'page');

        if (is_array($page) && array_key_exists('size', $page)) {
            return $this->normalizeBoundedPageSize($page['size'], $this->defaultSize, $this->maxSize);
        }

        return $this->defaultSize;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function extractCursor(array $params): ?string
    {
        if (array_key_exists('cursor', $params) && is_scalar($params['cursor']) && trim((string) $params['cursor']) !== '') {
            return trim((string) $params['cursor']);
        }

        $page = $this->decodeJsonIfString($params['page'] ?? null, 'page');

        if (is_array($page) && array_key_exists('cursor', $page)) {
            $cursor = $page['cursor'];

            return is_scalar($cursor) && trim((string) $cursor) !== '' ? trim((string) $cursor) : null;
        }

        return null;
    }
}
