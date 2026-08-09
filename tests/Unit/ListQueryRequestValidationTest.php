<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

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
        ->toThrow(ValidationException::class)
    ;
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
        ->toThrow(ValidationException::class)
    ;
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
        ->toThrow(ValidationException::class)
    ;
});
