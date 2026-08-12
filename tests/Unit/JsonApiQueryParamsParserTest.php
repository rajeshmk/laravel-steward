<?php

declare(strict_types=1);

use Hatchyu\Steward\Queries\Parsers\JsonApiQueryParamsParser;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Hatchyu\Steward\Tests\TestCase;


test('it parses jsonapi pagination and sort', function (): void {
    $parser = new JsonApiQueryParamsParser();

    $params = $parser->parse([
        'filter' => ['is_active' => '1'],
        'sort' => '-created_at,name',
        'include' => 'addresses',
        'fields' => ['customers' => 'name,email'],
        'page' => ['number' => '2', 'size' => '25'],
    ]);

    expect($params->filters)->toBe(['is_active' => '1'])
        ->and($params->includes)->toBe(['addresses'])
        ->and($params->fields)->toBe(['customers' => ['name', 'email']])
        ->and($params->page)->toBe(2)
        ->and($params->size)->toBe(25)
        ->and(count($params->sort))->toBe(2)
        ->and($params->sort[0]->field)->toBe('created_at')
        ->and($params->sort[0]->direction)->toBe('desc')
    ;
});

test('it parses jsonapi params from request body attributes', function (): void {
    $parser = new JsonApiQueryParamsParser();

    $request = Request::create(
        '/customers',
        'GET',
        [],
        [],
        [],
        [
            'HTTP_ACCEPT' => 'application/vnd.api+json',
            'CONTENT_TYPE' => 'application/vnd.api+json',
        ],
        json_encode([
            'data' => [
                'attributes' => [
                    'filter' => ['is_active' => '1'],
                    'sort' => '-created_at',
                    'include' => 'addresses',
                    'fields' => ['customers' => 'name,email'],
                    'page' => ['number' => 2, 'size' => 10],
                ],
            ],
        ], JSON_THROW_ON_ERROR),
    );

    $params = $parser->parseRequest($request);

    expect($params->filters)->toBe(['is_active' => '1'])
        ->and($params->includes)->toBe(['addresses'])
        ->and($params->fields)->toBe(['customers' => ['name', 'email']])
        ->and($params->page)->toBe(2)
        ->and($params->size)->toBe(10)
        ->and($params->sort[0]->field)->toBe('created_at')
        ->and($params->sort[0]->direction)->toBe('desc')
    ;
});

test('it maps filter search key to search text', function (): void {
    $parser = new JsonApiQueryParamsParser();

    $params = $parser->parse([
        'filter' => ['search' => 'john', 'is_active' => '1'],
    ]);

    expect($params->search)->toBe('john')
        ->and($params->filters)->toBe(['is_active' => '1'])
    ;
});

test('it throws when jsonapi uses top level size', function (): void {
    $parser = new JsonApiQueryParamsParser();

    expect(fn (): mixed => $parser->parse([
        'size' => '20',
    ]))->toThrow(ValidationException::class);
});

test('it throws when jsonapi uses a top level cursor', function (): void {
    $parser = new JsonApiQueryParamsParser();

    expect(fn (): mixed => $parser->parse([
        'cursor' => 'opaque-cursor',
    ]))->toThrow(ValidationException::class);
});

test('it throws when jsonapi sort has invalid element shape', function (): void {
    $parser = new JsonApiQueryParamsParser();

    expect(fn (): mixed => $parser->parse([
        'sort' => ['name' => ['invalid']],
    ]))->toThrow(ValidationException::class);
});

test('it throws when jsonapi include is not a string or array list', function (): void {
    $parser = new JsonApiQueryParamsParser();

    expect(fn (): mixed => $parser->parse([
        'include' => true,
    ]))->toThrow(ValidationException::class);
});

test('it throws when jsonapi fields is not an object', function (): void {
    $parser = new JsonApiQueryParamsParser();

    expect(fn (): mixed => $parser->parse([
        'fields' => 'name,email',
    ]))->toThrow(ValidationException::class);
});

test('it rejects scalar page values in jsonapi mode', function (): void {
    $parser = new JsonApiQueryParamsParser();

    expect(fn (): mixed => $parser->parse(['page' => '2']))
        ->toThrow(ValidationException::class);
});

test('it rejects jsonapi pagination hybrids', function (): void {
    $parser = new JsonApiQueryParamsParser();

    expect(fn (): mixed => $parser->parse([
        'page' => ['number' => 2, 'size' => 25],
        'size' => 100,
    ]))->toThrow(ValidationException::class);
});
