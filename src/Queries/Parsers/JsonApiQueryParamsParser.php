<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Parsers;

use Hatchyu\Steward\Queries\Params\QueryParams;
use Hatchyu\Steward\Queries\Support\QueryParamLimits;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class JsonApiQueryParamsParser extends AbstractQueryParamsParser
{
    public function __construct(QueryParamLimits $limits = new QueryParamLimits())
    {
        parent::__construct($limits);
    }

    public function parseRequest(Request $request): QueryParams
    {
        return $this->parse($this->extractInputPayload($request));
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function extractPageNumber(array $params): int
    {
        if (array_key_exists('cursor', $params) || array_key_exists('size', $params)) {
            throw ValidationException::withMessages(['page' => 'JSON:API pagination requires page[number], page[size], and page[cursor].']);
        }

        $page = $this->decodeJsonIfString($params['page'] ?? null, 'page');
        if ($page === null) {
            return 1;
        }
        if (! is_array($page) || ! QuerySyntax::isAssociativeArray($page)) {
            throw ValidationException::withMessages(['page' => 'JSON:API pagination must use a page object.']);
        }
        $this->assertPageObject($page);

        return $this->normalizeBoundedPositiveInt($page['number'] ?? null, 1, $this->limits->maxPageNumber, 'page.number');
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function extractPageSize(array $params): int
    {
        $page = $this->decodeJsonIfString($params['page'] ?? null, 'page');
        if (! is_array($page) || ! array_key_exists('size', $page)) {
            return $this->limits->defaultPageSize;
        }

        return $this->normalizeBoundedPositiveInt($page['size'], $this->limits->defaultPageSize, $this->limits->maxPageSize, 'page.size');
    }

    /**
     * @return array<string, mixed>
     */
    private function extractInputPayload(Request $request): array
    {
        $query = $request->query->all();
        $input = $request->input();
        if (! is_array($input)) {
            return $query;
        }

        $data = $input['data'] ?? null;
        $attributes = is_array($data) && is_array($data['attributes'] ?? null) ? $data['attributes'] : $input;
        foreach (['filter', 'sort', 'include', 'fields', 'page', 'search'] as $key) {
            if (! array_key_exists($key, $query) && array_key_exists($key, $attributes)) {
                $query[$key] = $attributes[$key];
            }
        }

        return $query;
    }

    /**
     * @param array<mixed> $page
     */
    private function assertPageObject(array $page): void
    {
        foreach (array_keys($page) as $key) {
            if (! is_string($key) || ! in_array($key, ['number', 'size', 'cursor'], true)) {
                throw ValidationException::withMessages(['page' => sprintf('Unsupported JSON:API page parameter key: %s.', (string) $key)]);
            }
        }
    }
}
