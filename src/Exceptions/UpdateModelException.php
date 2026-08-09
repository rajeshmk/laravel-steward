<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Exceptions;

use Hatchyu\ApiExceptions\Http\InternalServerErrorException;
use Throwable;

class UpdateModelException extends InternalServerErrorException
{
    public function __construct(string $message = 'Failed to update model', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
