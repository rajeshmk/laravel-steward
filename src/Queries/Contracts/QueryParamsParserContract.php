<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Contracts;

use Hatchyu\Steward\Queries\Params\QueryParams;
use Illuminate\Http\Request;

interface QueryParamsParserContract
{
    /**
     * @param array<string, mixed> $params
     */
    public function parse(array $params): QueryParams;

    public function parseRequest(Request $request): QueryParams;
}
