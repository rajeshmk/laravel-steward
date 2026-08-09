<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

use Hatchyu\Steward\Data\AbstractData;

final class AbstractNullableBoolData extends AbstractData
{
    public function __construct(
        public ?bool $isActive,
    ) {}
}
