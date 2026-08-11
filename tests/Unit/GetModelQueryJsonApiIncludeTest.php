<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

use Hatchyu\Steward\Tests\Fixtures\Customer;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Illuminate\Validation\ValidationException;
use Hatchyu\Steward\Tests\TestCase;


test('it applies eager loads from jsonapi include params', function (): void {
    $query = resolve(GetModelQueryJsonApiIncludeTestQuery::class)
        ->applyForTest(new QueryParams(includes: ['addresses']))
    ;

    expect(array_keys($query->getEagerLoads()))->toBe(['addresses']);
});

test('it projects top level columns from sparse fieldsets while keeping the primary key', function (): void {
    $query = resolve(GetModelQueryJsonApiIncludeTestQuery::class)
        ->applyForTest(new QueryParams(fields: [
            'customers' => ['name', 'email'],
        ]))
    ;

    expect($query->getQuery()->columns)->toBe([
        'customers.id',
        'customers.name',
        'customers.email',
    ]);
});

test('it keeps relation support columns when projecting sparse fields with includes', function (): void {
    $query = resolve(GetModelQueryJsonApiIncludeTestQuery::class)
        ->applyForTest(new QueryParams(
            includes: ['addresses'],
            fields: ['customers' => ['name']]
        ))
    ;

    expect($query->getQuery()->columns)->toBe([
        'customers.id',
        'customers.name',
    ]);
});

test('it projects included relation columns from sparse fieldsets while keeping matching keys', function (): void {
    $query = resolve(GetModelQueryJsonApiIncludeTestQuery::class)
        ->applyForTest(new QueryParams(
            includes: ['addresses'],
            fields: ['customer-addresses' => ['city']]
        ))
    ;

    $eagerLoad = $query->getEagerLoads()['addresses'];
    $relationQuery = new Customer()->addresses()->getQuery();
    $eagerLoad($relationQuery);

    expect($relationQuery->getQuery()->columns)->toBe([
        'customer_addresses.id',
        'customer_addresses.city',
        'customer_addresses.customer_id',
    ]);
});

test('it rejects unsupported jsonapi include params at query level', function (): void {
    expect(fn (): mixed => resolve(GetModelQueryJsonApiIncludeTestQuery::class)
        ->applyForTest(new QueryParams(includes: ['orders'])))
        ->toThrow(ValidationException::class)
    ;
});

test('it rejects filters and sorts when the query has not declared them', function (): void {
    $query = resolve(GetModelQueryJsonApiIncludeTestQuery::class);

    expect(fn (): mixed => $query->applyForTest(new QueryParams(filters: ['status' => 'active'])))
        ->toThrow(ValidationException::class)
        ->and(fn (): mixed => $query->applyForTest(new QueryParams(sort: [new \Hatchyu\Steward\Queries\Params\SortField('name')])))
        ->toThrow(ValidationException::class)
    ;
});

test('it rejects include and fieldset requests without explicit resource metadata', function (): void {
    $query = new class(new Customer(), resolve(\Hatchyu\Steward\Queries\Contracts\QueryParamsProcessorContract::class)) extends \Hatchyu\Steward\Queries\GetModelQuery
    {
        public function applyForTest(QueryParams $params): \Illuminate\Database\Eloquent\Builder
        {
            $builder = $this->query();
            $this->applyQueryParams($builder, $params);

            return $builder;
        }
    };

    expect(fn (): mixed => $query->applyForTest(new QueryParams(includes: ['addresses'])))
        ->toThrow(ValidationException::class)
        ->and(fn (): mixed => $query->applyForTest(new QueryParams(fields: ['customers' => ['name']])))
        ->toThrow(ValidationException::class)
    ;
});
