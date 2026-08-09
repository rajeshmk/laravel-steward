<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

use App\Admin\Domain\Enums\CustomerGender;
use Hatchyu\Steward\Data\AbstractData;

final class AbstractNullableEnumData extends AbstractData
{
    public function __construct(
        public ?CustomerGender $gender,
    ) {}
}
