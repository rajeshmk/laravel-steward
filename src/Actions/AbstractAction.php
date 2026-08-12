<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Actions;

use Illuminate\Support\Facades\DB;

abstract class AbstractAction
{
    protected function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    /**
     * Register a callback to execute after the active database transaction successfully commits.
     */
    protected function afterCommit(callable $callback): void
    {
        DB::afterCommit($callback);
    }
}
