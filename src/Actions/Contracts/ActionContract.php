<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Actions\Contracts;

/**
 * @template TData
 * @template TResult
 */
interface ActionContract
{
    /**
     * Execute the action.
     *
     * @param TData $data
     * @return TResult
     */
    public function execute(mixed $data = null): mixed;
}
