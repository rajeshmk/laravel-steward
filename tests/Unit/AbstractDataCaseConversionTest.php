<?php

declare(strict_types=1);

use Hatchyu\Steward\Data\AbstractData;
use Hatchyu\Steward\Tests\Unit\AbstractDataCaseConversionTestData;
use Hatchyu\Steward\Tests\Unit\AbstractNullableBoolData;
use Hatchyu\Steward\Tests\Unit\AbstractNullableEnumData;

test('it maps snake_case input keys to camelCase DTO properties', function (): void {
    $data = AbstractDataCaseConversionTestData::fromArray([
        'app_url' => 'https://example.test',
        'meta_data' => ['apiURL' => 'x'],
        'is_active' => '1',
    ]);

    expect($data->appURL)->toBe('https://example.test')
        ->and($data->metaData)->toBe(['apiURL' => 'x'])
        ->and($data->isActive)->toBeTrue()
    ;
});

test('it converts top-level camelCase DTO keys to snake_case model attributes', function (): void {
    $data = AbstractDataCaseConversionTestData::fromArray([
        'appURL' => 'https://example.test',
        'metaData' => ['apiURL' => 'x'],
        'isActive' => true,
    ]);

    expect($data->toModelAttributes())->toBe([
        'app_url' => 'https://example.test',
        'meta_data' => ['apiURL' => 'x'],
        'is_active' => true,
    ]);
});

test('it casts boolean-like string values for bool properties', function (): void {
    $trueData = AbstractDataCaseConversionTestData::fromArray([
        'appURL' => 'https://example.test',
        'is_active' => 'true',
    ]);
    $falseData = AbstractDataCaseConversionTestData::fromArray([
        'appURL' => 'https://example.test',
        'is_active' => '0',
    ]);

    expect($trueData->isActive)->toBeTrue()
        ->and($falseData->isActive)->toBeFalse()
    ;
});

test('it casts empty string to null for nullable enum properties', function (): void {
    $data = AbstractNullableEnumData::fromArray([
        'gender' => '',
    ]);

    expect($data->gender)->toBeNull();
});

test('it casts empty string to null for nullable bool properties', function (): void {
    $data = AbstractNullableBoolData::fromArray([
        'is_active' => '',
    ]);

    expect($data->isActive)->toBeNull();
});

test('it throws when null is passed to non-nullable bool property', function (): void {
    expect(fn (): AbstractDataCaseConversionTestData => AbstractDataCaseConversionTestData::fromArray([
        'app_url' => 'https://example.test',
        'is_active' => null,
    ]))->toThrow(InvalidArgumentException::class);
});

test('it safely normalizes scalar request values for concrete scalar DTO properties', function (): void {
    $dataClass = (new class(0, 0.0, '') extends AbstractData
    {
        public function __construct(
            public int $count,
            public float $amount,
            public string $reference,
        ) {}
    })::class;

    $result = $dataClass::fromArray([
        'count' => '12',
        'amount' => '19.95',
        'reference' => 42,
    ]);

    expect($result->count)->toBe(12)
        ->and($result->amount)->toBe(19.95)
        ->and($result->reference)->toBe('42')
    ;
});

test('it preserves PHP union behavior rather than coercing bool|string values to bool', function (): void {
    $dataClass = (new class('') extends AbstractData
    {
        public function __construct(public bool|string $value) {}
    })::class;

    $result = $dataClass::fromArray(['value' => 'external-reference']);

    expect($result->value)->toBe('external-reference');
});
