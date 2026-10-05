<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Actions;

use Closure;
use Illuminate\Support\Facades\DB;

abstract class AbstractAction
{
    protected function transaction(callable $callback): mixed
    {
        $closure = $callback instanceof Closure ? $callback : Closure::fromCallable($callback);

        return DB::transaction($closure);
    }

    /**
     * Register a callback to execute after the active database transaction successfully commits.
     */
    protected function afterCommit(callable $callback): void
    {
        DB::afterCommit($callback);
    }
}
