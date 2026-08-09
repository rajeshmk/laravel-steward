<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

use Hatchyu\Steward\Data\AbstractData;

final class AbstractDataCaseConversionTestData extends AbstractData
{
    public function __construct(
        public string $appURL,
        public array $metaData = [],
        public bool $isActive = false,
    ) {}
}
