<?php

declare(strict_types=1);

use Hatchyu\Steward\Queries\Parsers\DefaultQueryParamsParser;
use Illuminate\Validation\ValidationException;
use Hatchyu\Steward\Tests\TestCase;


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
