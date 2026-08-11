<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Actions;

use Illuminate\Support\Facades\DB;

abstract class AbstractAction
{
    protected function transaction(callable $callback): mixed
    {
        if (DB::transactionLevel() > 0) {
            return $callback();
        }

        return DB::transaction($callback);
    }
}
