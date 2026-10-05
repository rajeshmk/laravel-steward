<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

use Hatchyu\Steward\Queries\Support\QueryParamLimits;
use InvalidArgumentException;

test('it rejects unsafe query parameter limits', function (): void {
    expect(fn (): QueryParamLimits => new QueryParamLimits(defaultPageSize: 101, maxPageSize: 100))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn (): QueryParamLimits => new QueryParamLimits(maxPageSize: 0))
        ->toThrow(InvalidArgumentException::class)
    ;
});
