<?php

declare(strict_types=1);

use Hatchyu\Steward\Queries\Criteria\Filters\AbstractFilter;
use Hatchyu\Steward\Queries\Criteria\Filters\SimpleFilter;
use Hatchyu\Steward\Queries\Criteria\Searches\PartialSearch;
use Hatchyu\Steward\Queries\Criteria\Sorts\SimpleSort;
use Hatchyu\Steward\Queries\Params\QueryDefinition;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Hatchyu\Steward\Queries\Params\SortField;
use Hatchyu\Steward\Queries\Processors\DefaultQueryParamsProcessor;
use Hatchyu\Steward\Tests\Fixtures\Customer;
use Illuminate\Database\Eloquent\Builder;

test('it supports mapped criteria definitions inspired by http steward', function (): void {
    $processor = new DefaultQueryParamsProcessor();
    $query = Customer::query();

    $processor->apply(
        $query,
        new QueryParams(
            search: 'alice',
            filters: ['status' => true],
            sort: [new SortField('latest', 'desc')],
        ),
        new QueryDefinition(
            searchable: [new PartialSearch('term', 'email')],
            filterable: [new SimpleFilter('status', 'is_active')],
            sortable: [new SimpleSort('latest', 'created_at')],
        ),
    );

    expect($query->toSql())->toContain('email')
        ->and($query->toSql())->toContain('is_active')
        ->and($query->toSql())->toContain('order by')
        ->and($query->toSql())->toContain('created_at')
        ->and($query->getBindings())->toBe(['alice', '%alice%', true])
    ;
});

test('it normalizes string definitions into reusable criteria', function (): void {
    $definition = new QueryDefinition(
        searchable: ['email'],
        filterable: ['is_active'],
        sortable: ['created_at'],
    );

    expect($definition->searchable[0])->toBeInstanceOf(PartialSearch::class)
        ->and($definition->filterable[0])->toBeInstanceOf(SimpleFilter::class)
        ->and($definition->sortable[0])->toBeInstanceOf(SimpleSort::class)
    ;
});

test('it applies custom filter criteria from the definition', function (): void {
    $processor = new DefaultQueryParamsProcessor();
    $query = Customer::query();

    $processor->apply(
        $query,
        new QueryParams(filters: ['q' => 'doe']),
        new QueryDefinition(filterable: [
            new class('q') extends AbstractFilter
            {
                public function apply(Builder $query, mixed $value): Builder
                {
                    return $query->where('name', 'like', '%' . $value . '%');
                }
            },
        ]),
    );

    expect($query->toSql())->toContain('name')
        ->and($query->getBindings())->toBe(['%doe%'])
    ;
});

test('it parses comma-separated string filter values into SQL whereIn clause', function (): void {
    $processor = new DefaultQueryParamsProcessor();
    $query = Customer::query();

    $processor->apply(
        $query,
        new QueryParams(filters: ['id' => '1,2,3,4,5']),
        new QueryDefinition(filterable: [new SimpleFilter('id', 'id')]),
    );

    expect($query->toSql())->toContain('id')
        ->and($query->toSql())->toContain('in')
        ->and($query->getBindings())->toBe(['1', '2', '3', '4', '5'])
    ;
});
