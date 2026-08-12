<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Testing;

class StewardArch
{
    /**
     * Common architectural expectations for Steward Actions.
     */
    /** @return array{namespace: string} */
    public static function actionRules(string $namespace): array
    {
        return [
            'namespace' => $namespace,
        ];
    }
}
