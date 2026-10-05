<?php

declare(strict_types=1);

use Hatchyu\Steward\Queries\Parsers\DefaultQueryParamsParser;
use Hatchyu\Steward\Queries\Support\QueryParamLimits;
use Illuminate\Validation\ValidationException;

test('it parses flat filter query params', function (): void {
    $parser = new DefaultQueryParamsParser();

    $params = $parser->parse([
        'filter' => [
            'user_id' => '50',
            'is_active' => '1',
        ],
    ]);

    expect($params->filters)->toBe([
        'user_id' => '50',
        'is_active' => '1',
    ]);
});

test('it parses include query params', function (): void {
    $parser = new DefaultQueryParamsParser();

    $params = $parser->parse([
        'include' => 'addresses,addresses.customer',
    ]);

    expect($params->includes)->toBe(['addresses', 'addresses.customer']);
});

test('it parses sparse fieldsets', function (): void {
    $parser = new DefaultQueryParamsParser();

    $params = $parser->parse([
        'fields' => [
            'customers' => 'name,email',
        ],
    ]);

    expect($params->fields)->toBe([
        'customers' => ['name', 'email'],
    ]);
});

test('it parses JSON string query parameters universally for page, sort, fields, and filter', function (): void {
    $parser = new DefaultQueryParamsParser();

    $params = $parser->parse([
        'filter' => '{"is_active":true,"gender":"female"}',
        'sort' => '["-created_at","name"]',
        'fields' => '{"customers":["name","email"]}',
        'page' => '{"number":3,"size":25}',
    ]);

    expect($params->filters)->toBe([
        'is_active' => true,
        'gender' => 'female',
    ])
        ->and($params->sort[0]->field)->toBe('created_at')
        ->and($params->sort[0]->direction)->toBe('desc')
        ->and($params->sort[1]->field)->toBe('name')
        ->and($params->sort[1]->direction)->toBe('asc')
        ->and($params->fields)->toBe(['customers' => ['name', 'email']])
        ->and($params->page)->toBe(3)
        ->and($params->size)->toBe(25)
    ;
});

test('it rejects malformed sparse field names', function (): void {
    $parser = new DefaultQueryParamsParser();

    expect(fn (): mixed => $parser->parse([
        'fields' => ['customers' => 'name,invalid-field'],
    ]))->toThrow(ValidationException::class);
});

test('it throws when filter is invalid json string', function (): void {
    $parser = new DefaultQueryParamsParser();

    expect(fn (): mixed => $parser->parse([
        'filter' => '{"user_id":',
    ]))->toThrow(ValidationException::class);
});

test('it throws when sort has invalid direction in map format', function (): void {
    $parser = new DefaultQueryParamsParser();

    expect(fn (): mixed => $parser->parse([
        'sort' => ['name' => 'down'],
    ]))->toThrow(ValidationException::class);
});

test('it throws when sort array has invalid entry type', function (): void {
    $parser = new DefaultQueryParamsParser();

    expect(fn (): mixed => $parser->parse([
        'sort' => [['name' => 'asc']],
    ]))->toThrow(ValidationException::class);
});

test('it rejects unsupported pagination keys instead of silently ignoring them', function (): void {
    $parser = new DefaultQueryParamsParser();

    expect(fn (): mixed => $parser->parse([
        'page' => ['offset' => 10],
    ]))->toThrow(ValidationException::class);
});

test('it rejects invalid cursor values instead of silently discarding them', function (): void {
    $parser = new DefaultQueryParamsParser();

    expect(fn (): mixed => $parser->parse([
        'page' => ['cursor' => ['invalid']],
    ]))->toThrow(ValidationException::class);
});

test('it rejects non-string filter search values instead of silently ignoring them', function (): void {
    $parser = new DefaultQueryParamsParser();

    expect(fn (): mixed => $parser->parse([
        'filter' => ['search' => 12],
    ]))->toThrow(ValidationException::class);
});

test('it enforces configured query complexity limits', function (): void {
    $parser = new DefaultQueryParamsParser(new QueryParamLimits(
        maxFilterFields: 1,
        maxFilterValues: 2,
        maxIncludes: 1,
        maxFields: 1,
        maxSortFields: 1,
        maxValueLength: 100,
    ));

    expect(fn (): mixed => $parser->parse(['filter' => ['one' => 1, 'two' => 2]]))
        ->toThrow(ValidationException::class)
        ->and(fn (): mixed => $parser->parse(['filter' => ['one' => [1, 2, 3]]]))
        ->toThrow(ValidationException::class)
        ->and(fn (): mixed => $parser->parse(['include' => 'one,two']))
        ->toThrow(ValidationException::class)
        ->and(fn (): mixed => $parser->parse(['fields' => ['users' => 'name,email']]))
        ->toThrow(ValidationException::class)
        ->and(fn (): mixed => $parser->parse(['sort' => 'name,email']))
        ->toThrow(ValidationException::class)
        ->toThrow(ValidationException::class)
    ;
});

test('it rejects overly long query values before parsing them', function (): void {
    $parser = new DefaultQueryParamsParser(new QueryParamLimits(maxValueLength: 5));

    expect(fn (): mixed => $parser->parse(['search' => 'longer']))
        ->toThrow(ValidationException::class)
    ;
});
