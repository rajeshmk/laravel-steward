<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

use Hatchyu\Steward\Queries\Parsers\DefaultQueryParamsParser;
use Hatchyu\Steward\Queries\Support\QueryParamLimits;
use Hatchyu\Steward\Queries\Parsers\JsonApiQueryParamsParser;
use Hatchyu\Steward\Requests\ListQueryRequest;
use Illuminate\Validation\ValidationException;
use Hatchyu\Steward\Tests\TestCase;

test('it allows jsonapi include and fields parameters derived from the resource', function (): void {
    $request = new class() extends ListQueryRequest
    {
        protected function primaryJsonApiResource(): string
        {
            return ListQueryRequestValidationTestCustomerResource::class;
        }
    };

    $request->initialize([
        'include' => 'addresses',
        'fields' => [
            'customers' => 'name,email',
            'customer-addresses' => 'city',
        ],
    ]);

    expect($request->validateQuery())->toBeArray();
});

test('it rejects unsupported include paths', function (): void {
    $request = new class() extends ListQueryRequest
    {
        protected function primaryJsonApiResource(): string
        {
            return ListQueryRequestValidationTestCustomerResource::class;
        }
    };

    $request->initialize([
        'include' => 'orders',
    ]);

    expect(fn (): array => $request->validateQuery())
        ->toThrow(ValidationException::class);
});

test('it rejects unsupported sparse fieldsets', function (): void {
    $request = new class() extends ListQueryRequest
    {
        protected function primaryJsonApiResource(): string
        {
            return ListQueryRequestValidationTestCustomerResource::class;
        }
    };

    $request->initialize([
        'fields' => [
            'customers' => 'name,password',
        ],
    ]);

    expect(fn (): array => $request->validateQuery())
        ->toThrow(ValidationException::class);
});

test('it allows filter as json object string', function (): void {
    $request = new class() extends ListQueryRequest
    {
        protected function primaryJsonApiResource(): string
        {
            return ListQueryRequestValidationTestCustomerResource::class;
        }
    };

    $request->initialize([
        'filter' => '{"is_active":1,"gender":"female"}',
    ]);

    expect($request->validateQuery())->toBeArray();
});

test('it rejects invalid json in filter string', function (): void {
    $request = new class() extends ListQueryRequest
    {
        protected function primaryJsonApiResource(): string
        {
            return ListQueryRequestValidationTestCustomerResource::class;
        }
    };

    $request->initialize([
        'filter' => '{"is_active":1',
    ]);

    expect(fn (): array => $request->validateQuery())
        ->toThrow(ValidationException::class);
});

test('it validates and parses JSON sort array via ListQueryRequest toQueryParams', function (): void {
    $request = new class() extends ListQueryRequest
    {
        protected function primaryJsonApiResource(): string
        {
            return ListQueryRequestValidationTestCustomerResource::class;
        }
    };

    $request->initialize([
        'sort' => '["-created_at","name"]',
    ]);

    $queryParams = $request->toQueryParams();
    expect($queryParams->sort)->toHaveCount(2);
    expect($queryParams->sort[0]->field)->toBe('created_at');
    expect($queryParams->sort[0]->direction)->toBe('desc');
    expect($queryParams->sort[1]->field)->toBe('name');
    expect($queryParams->sort[1]->direction)->toBe('asc');
});

test('it rejects filter JSON array when object is required', function (): void {
    $request = new class() extends ListQueryRequest
    {
        protected function primaryJsonApiResource(): string
        {
            return ListQueryRequestValidationTestCustomerResource::class;
        }
    };

    $request->initialize([
        'filter' => '[1,2]',
    ]);

    expect(fn (): array => $request->validateQuery())
        ->toThrow(ValidationException::class);
});

test('it rejects include payload containing invalid element types', function (): void {
    $request = new class() extends ListQueryRequest
    {
        protected function primaryJsonApiResource(): string
        {
            return ListQueryRequestValidationTestCustomerResource::class;
        }
    };

    $request->initialize([
        'include' => '["addresses", {"invalid": true}]',
    ]);

    expect(fn (): array => $request->validateQuery())
        ->toThrow(ValidationException::class);
});

test('it rejects object provided as a list for include', function (): void {
    $request = new class() extends ListQueryRequest
    {
        protected function primaryJsonApiResource(): string
        {
            return ListQueryRequestValidationTestCustomerResource::class;
        }
    };

    $request->initialize([
        'include' => '{"anything":"addresses"}',
    ]);

    expect(fn (): array => $request->validateQuery())
        ->toThrow(ValidationException::class);
});

test('it rejects excessive page sizes', function (): void {
    $parser = new DefaultQueryParamsParser(new QueryParamLimits(defaultPageSize: 15, maxPageSize: 100));

    expect(fn (): mixed => $parser->parse([
        'page' => '{"number":1,"size":1000000}',
    ]))->toThrow(ValidationException::class);
});

test('it extracts search term from JSON encoded filter string', function (): void {
    $parser = new DefaultQueryParamsParser();
    $queryParams = $parser->parse([
        'filter' => '{"search":"john","is_active":true}',
    ]);

    expect($queryParams->search)->toBe('john');
    expect($queryParams->filters)->toBe(['is_active' => true]);
});

test('it rejects non-positive page integers in PageQueryRule', function (): void {
    $request = new class() extends ListQueryRequest {};
    $request->initialize(['page' => 0]);

    expect(fn (): array => $request->validateQuery())
        ->toThrow(ValidationException::class);
});

test('it parses JSON string query params in JsonApiQueryParamsParser with parity', function (): void {
    $parser = new JsonApiQueryParamsParser();
    $request = new \Illuminate\Http\Request();
    $request->query->replace([
        'sort' => '["-created_at","name"]',
        'include' => '["addresses"]',
        'fields' => '{"customers":["name","email"]}',
        'page' => '{"number":2,"size":25}',
    ]);

    $queryParams = $parser->parseRequest($request);

    expect($queryParams->sort)->toHaveCount(2);
    expect($queryParams->includes)->toBe(['addresses']);
    expect($queryParams->fields)->toBe(['customers' => ['name', 'email']]);
    expect($queryParams->page)->toBe(2);
    expect($queryParams->size)->toBe(25);
});
