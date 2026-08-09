<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Params;

use InvalidArgumentException;

final readonly class SortField
{
    public function __construct(
        public string $field,
        public string $direction = 'asc',
    ) {
        if ($this->field === '') {
            throw new InvalidArgumentException('Sort field cannot be empty.');
        }

        if (! in_array($this->direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException(sprintf(
                'Sort direction must be asc or desc, got %s.',
                $this->direction
            ));
        }
    }
}
