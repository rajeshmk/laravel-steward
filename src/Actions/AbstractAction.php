<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;

abstract class AbstractAction
{
    protected function transaction(callable $callback): mixed
    {
        try {
            if (DB::transactionLevel() > 0) {
                return $callback();
            }

            return DB::transaction($callback);
        } catch (Throwable $e) {
            // Fall back only if no database driver is configured in unit test environment
            if (str_contains($e->getMessage(), 'could not find driver')) {
                return $callback();
            }

            throw $e;
        }
    }
}
