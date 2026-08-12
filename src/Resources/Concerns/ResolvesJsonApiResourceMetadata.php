<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Resources\Concerns;

use Hatchyu\Steward\Resources\JsonApiResource;

trait ResolvesJsonApiResourceMetadata
{
    /**
     * @param class-string<JsonApiResource>|null $resourceClass
     * @param list<string>                       $explicitIncludes
     *
     * @return list<string>
     */
    protected function resolveAllowedIncludes(?string $resourceClass, array $explicitIncludes = []): array
    {
        if ($explicitIncludes !== []) {
            return $explicitIncludes;
        }

        if (! is_string($resourceClass) || ! is_subclass_of($resourceClass, JsonApiResource::class)) {
            return [];
        }

        return $resourceClass::jsonApiAllowedIncludes();
    }

    /**
     * @param class-string<JsonApiResource>|null $resourceClass
     * @param array<string, list<string>>        $explicitFields
     *
     * @return array<string, list<string>>
     */
    protected function resolveAllowedFields(?string $resourceClass, array $explicitFields = []): array
    {
        if ($explicitFields !== []) {
            return $explicitFields;
        }

        if (! is_string($resourceClass) || ! is_subclass_of($resourceClass, JsonApiResource::class)) {
            return [];
        }

        return $resourceClass::jsonApiAllowedFieldsets();
    }
}
