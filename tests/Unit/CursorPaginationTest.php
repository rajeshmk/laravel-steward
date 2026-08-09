<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

use Hatchyu\Steward\Queries\Parsers\DefaultQueryParamsParser;
use Hatchyu\Steward\Queries\Parsers\JsonApiQueryParamsParser;
use Illuminate\Http\Request;
use Hatchyu\Steward\Tests\TestCase;


test('DefaultQueryParamsParser extracts cursor parameter', function (): void {
    $parser = new DefaultQueryParamsParser();
    $request = Request::create('/test?cursor=eyJpZCI6MTB9');

    $params = $parser->parseRequest($request);

    expect($params->cursor)->toBe('eyJpZCI6MTB9');
});

test('JsonApiQueryParamsParser extracts page[cursor] parameter', function (): void {
    $parser = new JsonApiQueryParamsParser();
    $request = Request::create('/test?page[cursor]=eyJpZCI6MTB9&page[size]=20');

    $params = $parser->parseRequest($request);

    expect($params->cursor)->toBe('eyJpZCI6MTB9');
    expect($params->size)->toBe(20);
});
