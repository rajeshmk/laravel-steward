<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Parsers;

use Hatchyu\Steward\Queries\Params\QueryParams;
use Hatchyu\Steward\Queries\Support\QueryParamLimits;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class DefaultQueryParamsParser extends AbstractQueryParamsParser
{
    public function __construct(QueryParamLimits $limits = new QueryParamLimits())
    {
        parent::__construct($limits);
    }

    public function parseRequest(Request $request): QueryParams
    {
        return $this->parse($request->query->all());
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function extractPageNumber(array $params): int
    {
        $page = $this->decodeJsonIfString($params['page'] ?? null, 'page');
        if (is_array($page)) {
            $this->assertPageObject($page);
            $page = $page['number'] ?? null;
        }

        return $this->normalizeBoundedPositiveInt($page, 1, $this->limits->maxPageNumber, 'page');
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function extractPageSize(array $params): int
    {
        if (array_key_exists('size', $params)) {
            return $this->normalizeBoundedPositiveInt($params['size'], $this->limits->defaultPageSize, $this->limits->maxPageSize, 'size');
        }

        $page = $this->decodeJsonIfString($params['page'] ?? null, 'page');
        if (is_array($page)) {
            $this->assertPageObject($page);
            if (array_key_exists('size', $page)) {
                return $this->normalizeBoundedPositiveInt($page['size'], $this->limits->defaultPageSize, $this->limits->maxPageSize, 'page.size');
            }
        }

        return $this->limits->defaultPageSize;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function extractCursor(array $params): ?string
    {
        if (array_key_exists('cursor', $params)) {
            if (! is_string($params['cursor']) || trim($params['cursor']) === '') {
                throw ValidationException::withMessages(['cursor' => 'The cursor must be a non-empty string.']);
            }

            return trim($params['cursor']);
        }

        return parent::extractCursor($params);
    }

    /**
     * @param array<mixed> $page
     */
    private function assertPageObject(array $page): void
    {
        if (! QuerySyntax::isAssociativeArray($page)) {
            throw ValidationException::withMessages(['page' => 'The page parameter must be a positive integer or object.']);
        }
        foreach (array_keys($page) as $key) {
            if (! is_string($key) || ! in_array($key, ['number', 'size', 'cursor'], true)) {
                throw ValidationException::withMessages(['page' => sprintf('Unsupported page parameter key: %s.', (string) $key)]);
            }
        }
    }
}
