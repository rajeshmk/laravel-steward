<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Testing;

use Pest\Arch\Expectations\Targeted\ToBeFinal;

class StewardArch
{
    /**
     * Common architectural expectations for Steward Actions.
     */
    public static function actionRules(string $namespace): array
    {
        return [
            'namespace' => $namespace,
        ];
    }
}
