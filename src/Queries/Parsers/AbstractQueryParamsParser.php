<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Parsers;

use Hatchyu\Steward\Queries\Contracts\QueryParamsParserContract;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Hatchyu\Steward\Queries\Params\SortField;
use Hatchyu\Steward\Queries\Parsers\Concerns\NormalizesQueryParams;
use Hatchyu\Steward\Queries\Support\QueryParamLimits;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Validation\ValidationException;

abstract class AbstractQueryParamsParser implements QueryParamsParserContract
{
    use NormalizesQueryParams;

    public function __construct(
        protected readonly QueryParamLimits $limits = new QueryParamLimits(),
    ) {}

    /**
     * @param array<string, mixed> $params
     */
    final public function parse(array $params): QueryParams
    {
        $this->assertParameterStringLengths($params);

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
    abstract protected function extractPageNumber(array $params): int;

    /**
     * @param array<string, mixed> $params
     */
    abstract protected function extractPageSize(array $params): int;

    /**
     * @param array<string, mixed> $params
     */
    protected function extractSearch(array $params): ?string
    {
        $search = $params['search'] ?? null;
        if (! is_string($search) && $search !== null) {
            throw ValidationException::withMessages(['search' => 'The search parameter must be a string.']);
        }

        $filter = $params['filter'] ?? null;
        if (is_array($filter) && array_key_exists('search', $filter)) {
            if (! is_string($filter['search']) && $filter['search'] !== null) {
                throw ValidationException::withMessages(['filter.search' => 'The filter search value must be a string.']);
            }

            $search = $filter['search'];
        }

        return $search === null || trim($search) === '' ? null : trim($search);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    protected function extractFilters(array $params): array
    {
        $filters = $params['filter'] ?? null;
        if ($filters === null) {
            return [];
        }

        if (! is_array($filters) || ! QuerySyntax::isAssociativeArray($filters)) {
            throw ValidationException::withMessages(['filter' => 'The filter parameter must be an object or associative array.']);
        }

        unset($filters['search']);
        if (count($filters) > $this->limits->maxFilterFields) {
            throw ValidationException::withMessages(['filter' => sprintf('At most %d filter fields are allowed.', $this->limits->maxFilterFields)]);
        }

        $normalized = $this->normalizeFilterValues($filters);
        foreach ($normalized as $key => $value) {
            if (! QuerySyntax::isValidDotIdentifier($key)) {
                throw ValidationException::withMessages(['filter' => sprintf('Invalid filter key: %s.', $key)]);
            }

            if (! $this->isScalarOrNull($value) && (! is_array($value) || ! $this->isScalarList($value))) {
                throw ValidationException::withMessages(['filter' => sprintf('Invalid filter value for %s. Expected scalar, null, or list of scalars.', $key)]);
            }

            if (is_array($value) && count($value) > $this->limits->maxFilterValues) {
                throw ValidationException::withMessages(['filter' => sprintf('At most %d values are allowed for %s.', $this->limits->maxFilterValues, $key)]);
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $params
     * @return list<SortField>
     */
    protected function extractSort(array $params): array
    {
        $sort = $this->decodeJsonIfString($params['sort'] ?? null, 'sort');
        if ($sort === null) {
            return [];
        }

        if (is_string($sort)) {
            $sortFields = $this->parseSortString($sort);
            $this->assertSortFieldLimit($sortFields);

            return $sortFields;
        }

        if (! is_array($sort)) {
            throw ValidationException::withMessages(['sort' => 'The sort parameter must be a string or array.']);
        }

        $sortFields = [];
        foreach ($sort as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $sortFields[] = $this->parseSortToken($value);
                continue;
            }

            if (is_string($key) && is_string($value)) {
                $direction = strtolower(trim($value));
                if (! QuerySyntax::isValidDotIdentifier($key) || ! in_array($direction, ['asc', 'desc'], true)) {
                    throw ValidationException::withMessages(['sort' => 'Invalid sort parameter format.']);
                }
                $sortFields[] = new SortField(trim($key), $direction);
                continue;
            }

            throw ValidationException::withMessages(['sort' => 'Invalid sort parameter format.']);
        }

        $this->assertSortFieldLimit($sortFields);

        return $sortFields;
    }

    /**
     * @param array<string, mixed> $params
     * @return list<string>
     */
    protected function extractIncludes(array $params): array
    {
        $includes = $this->normalizeStringList($params['include'] ?? null, 'include');
        if ($includes === null) {
            if (array_key_exists('include', $params) && $params['include'] !== null) {
                throw ValidationException::withMessages(['include' => 'The include parameter must be a comma-separated string or array.']);
            }
            return [];
        }
        if (count($includes) > $this->limits->maxIncludes) {
            throw ValidationException::withMessages(['include' => sprintf('At most %d include paths are allowed.', $this->limits->maxIncludes)]);
        }
        foreach ($includes as $include) {
            if (! QuerySyntax::isValidDotIdentifier($include)) {
                throw ValidationException::withMessages(['include' => sprintf('Invalid include path: %s.', $include)]);
            }
        }
        return $includes;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, list<string>>
     */
    protected function extractFields(array $params): array
    {
        $fields = $this->decodeJsonIfString($params['fields'] ?? null, 'fields');
        if ($fields === null) {
            return [];
        }
        if (! is_array($fields) || ! QuerySyntax::isAssociativeArray($fields)) {
            throw ValidationException::withMessages(['fields' => 'The fields parameter must be an object keyed by resource type.']);
        }

        if (count($fields) > $this->limits->maxFieldsets) {
            throw ValidationException::withMessages(['fields' => sprintf('At most %d resource fieldsets are allowed.', $this->limits->maxFieldsets)]);
        }

        $result = [];
        foreach ($fields as $type => $fieldSet) {
            if (! is_string($type) || ! QuerySyntax::isValidResourceType($type)) {
                throw ValidationException::withMessages(['fields' => sprintf('Invalid resource type for fields: %s.', (string) $type)]);
            }
            $list = $this->normalizeStringList($fieldSet, sprintf('fields.%s', $type));
            if ($list === null) {
                throw ValidationException::withMessages(['fields' => sprintf('The fields set for %s must be a string or list of strings.', $type)]);
            }
            foreach ($list as $field) {
                if (! QuerySyntax::isValidSimpleIdentifier($field)) {
                    throw ValidationException::withMessages(['fields' => sprintf('Invalid field name for %s: %s.', $type, $field)]);
                }
            }
            $result[$type] = $list;
        }

        if (array_sum(array_map(count(...), $result)) > $this->limits->maxFields) {
            throw ValidationException::withMessages(['fields' => sprintf('At most %d fields are allowed.', $this->limits->maxFields)]);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function extractCursor(array $params): ?string
    {
        $page = $this->decodeJsonIfString($params['page'] ?? null, 'page');
        if (! is_array($page) || ! array_key_exists('cursor', $page)) {
            return null;
        }
        if (! is_string($page['cursor']) || trim($page['cursor']) === '') {
            throw ValidationException::withMessages(['page.cursor' => 'The page cursor must be a non-empty string.']);
        }
        return trim($page['cursor']);
    }

    /**
     * @param list<SortField> $sortFields
     */
    private function assertSortFieldLimit(array $sortFields): void
    {
        if (count($sortFields) > $this->limits->maxSortFields) {
            throw ValidationException::withMessages(['sort' => sprintf('At most %d sort fields are allowed.', $this->limits->maxSortFields)]);
        }
    }

    /**
     * @param array<mixed> $value
     */
    private function assertParameterStringLengths(array $value): void
    {
        foreach ($value as $item) {
            if (is_string($item)) {
                $this->limits->assertValueLength('query', $item);
            }

            if (is_array($item)) {
                $this->assertParameterStringLengths($item);
            }
        }
    }
}
