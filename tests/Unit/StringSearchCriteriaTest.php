<?php

declare(strict_types=1);

use Hatchyu\Steward\Tests\Fixtures\Customer;
use Hatchyu\Steward\Queries\Criteria\Searches\EndsWithSearch;
use Hatchyu\Steward\Queries\Criteria\Searches\PartialSearch;
use Hatchyu\Steward\Queries\Criteria\Searches\SimpleSearch;
use Hatchyu\Steward\Queries\Criteria\Searches\StartsWithSearch;
use Hatchyu\Steward\Tests\TestCase;


test('partial search escapes like wildcard characters', function (): void {
    $query = Customer::query();

    new PartialSearch('name')->apply($query, 'al%_ice');

    expect($query->toSql())->toContain('like')
        ->and($query->getBindings())->toContain('%al\\%\\_ice%')
    ;
});

test('starts with search builds escaped prefix like query', function (): void {
    $query = Customer::query();

    new StartsWithSearch('name')->apply($query, 'ali');

    expect($query->toSql())->toContain('like')
        ->and($query->getBindings())->toContain('ali%')
    ;
});

test('ends with search builds escaped suffix like query', function (): void {
    $query = Customer::query();

    new EndsWithSearch('name')->apply($query, 'ice');

    expect($query->toSql())->toContain('like')
        ->and($query->getBindings())->toContain('%ice')
    ;
});

test('simple search supports exact scalar matching and null matching', function (): void {
    $query = Customer::query();
    new SimpleSearch('name')->apply($query, 'Alice');

    expect($query->toSql())->toContain('= ?')
        ->and($query->getBindings())->toBe(['Alice'])
    ;

    $nullQuery = Customer::query();
    new SimpleSearch('name')->apply($nullQuery, null);

    expect($nullQuery->toSql())->toContain('is null');
});
