<?php

declare(strict_types=1);

use Hatchyu\Steward\Queries\Parsers\AutoQueryParamsParser;
use Hatchyu\Steward\Queries\Parsers\DefaultQueryParamsParser;
use Hatchyu\Steward\Queries\Parsers\JsonApiQueryParamsParser;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Hatchyu\Steward\Tests\TestCase;


test('it auto-detects jsonapi from accept header', function (): void {
    $parser = new AutoQueryParamsParser(
        defaultParser: new DefaultQueryParamsParser(),
        jsonApiParser: new JsonApiQueryParamsParser(),
    );

    $request = Request::create('/customers', 'GET', [
        'page' => ['number' => '3', 'size' => '12'],
    ]);
    $request->headers->set('Accept', 'application/vnd.api+json');

    $params = $parser->parseRequest($request);

    expect($params->page)->toBe(3)
        ->and($params->size)->toBe(12)
    ;
});

test('it auto-detects default format for top-level pagination', function (): void {
    $parser = new AutoQueryParamsParser(
        defaultParser: new DefaultQueryParamsParser(),
        jsonApiParser: new JsonApiQueryParamsParser(),
    );

    $params = $parser->parse([
        'page' => '2',
        'size' => '30',
    ]);

    expect($params->page)->toBe(2)
        ->and($params->size)->toBe(30)
    ;
});

test('it auto-detects jsonapi by page object shape', function (): void {
    $parser = new AutoQueryParamsParser(
        defaultParser: new DefaultQueryParamsParser(),
        jsonApiParser: new JsonApiQueryParamsParser(),
    );

    expect(fn (): mixed => $parser->parse([
        'page' => ['number' => '1'],
        'cursor' => 'opaque-cursor',
    ]))->toThrow(ValidationException::class);
});

test('it auto-detects jsonapi by data body shape and parses attributes', function (): void {
    $parser = new AutoQueryParamsParser(
        defaultParser: new DefaultQueryParamsParser(),
        jsonApiParser: new JsonApiQueryParamsParser(),
    );

    $request = Request::create(
        '/customers',
        'GET',
        [],
        [],
        [],
        ['CONTENT_TYPE' => 'application/json'],
        json_encode([
            'data' => [
                'attributes' => [
                    'filter' => ['user_id' => '50'],
                    'page' => ['number' => 2, 'size' => 5],
                ],
            ],
        ], JSON_THROW_ON_ERROR),
    );

    $params = $parser->parseRequest($request);

    expect($params->filters)->toBe(['user_id' => '50'])
        ->and($params->page)->toBe(2)
        ->and($params->size)->toBe(5)
    ;
});
