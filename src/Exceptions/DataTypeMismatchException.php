<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Exceptions;

use Hatchyu\ApiExceptions\Http\InternalServerErrorException;
use Hatchyu\Steward\Data\AbstractData;

class DataTypeMismatchException extends InternalServerErrorException
{
    /**
     * @param class-string<AbstractData> $expected
     */
    public static function forExpected(string $expected, AbstractData $actual): self
    {
        return new self(sprintf(
            'Expected %s, received %s.',
            $expected,
            get_debug_type($actual)
        ));
    }

    /**
     * @param non-empty-list<class-string<AbstractData>> $expected
     */
    public static function forExpectedList(array $expected, AbstractData $actual): self
    {
        return new self(sprintf(
            'Expected one of [%s], received %s.',
            implode(', ', $expected),
            get_debug_type($actual)
        ));
    }
}
