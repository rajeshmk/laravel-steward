<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests;

use Hatchyu\Steward\StewardServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            StewardServiceProvider::class,
        ];
    }
}
