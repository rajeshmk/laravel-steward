<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Parsers;

use Hatchyu\Steward\Queries\Contracts\QueryParamsParserContract;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Http\Request;

final readonly class AutoQueryParamsParser implements QueryParamsParserContract
{
    public function __construct(
        private QueryParamsParserContract $defaultParser,
        private QueryParamsParserContract $jsonApiParser,
    ) {}

    public function parseRequest(Request $request): QueryParams
    {
        if ($this->isJsonApiRequest($request)) {
            return $this->jsonApiParser->parseRequest($request);
        }

        return $this->defaultParser->parseRequest($request);
    }

    public function parse(array $params): QueryParams
    {
        if ($this->looksLikeJsonApiParams($params)) {
            return $this->jsonApiParser->parse($params);
        }

        return $this->defaultParser->parse($params);
    }

    private function isJsonApiRequest(Request $request): bool
    {
        $accept = strtolower((string) $request->header('Accept', ''));
        $contentType = strtolower((string) $request->header('Content-Type', ''));

        if (str_contains($accept, 'application/vnd.api+json') || str_contains($contentType, 'application/vnd.api+json')) {
            return true;
        }

        $page = $request->query('page');
        if (is_string($page) && QuerySyntax::isJsonPayloadString($page)) {
            $trimmed = trim($page);
            if (json_validate($trimmed)) {
                $page = json_decode($trimmed, true);
            }
        }

        if (is_array($page) && (array_key_exists('number', $page) || array_key_exists('size', $page))) {
            return true;
        }

        /** @var mixed $jsonBody */
        $jsonBody = $request->json()->all();
        if (! is_array($jsonBody)) {
            return false;
        }

        $data = $jsonBody['data'] ?? null;

        return is_array($data);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function looksLikeJsonApiParams(array $params): bool
    {
        $page = $params['page'] ?? null;
        if (is_string($page) && QuerySyntax::isJsonPayloadString($page)) {
            $trimmed = trim($page);
            if (json_validate($trimmed)) {
                $page = json_decode($trimmed, true);
            }
        }

        if (is_array($page) && (array_key_exists('number', $page) || array_key_exists('size', $page))) {
            return true;
        }

        return false;
    }
}
