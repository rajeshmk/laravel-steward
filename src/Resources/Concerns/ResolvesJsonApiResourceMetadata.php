<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Resources\Concerns;

use Hatchyu\Steward\Resources\JsonApiResource;

trait ResolvesJsonApiResourceMetadata
{
    /**
     * Hook to obtain the primary JsonApiResource class string.
     * Subclasses or requests may override this method.
     *
     * @return class-string<JsonApiResource>|null
     */
    protected function primaryJsonApiResource(): ?string
    {
        return null;
    }

    /**
     * Hook for explicit allowed includes.
     *
     * @return list<string>
     */
    protected function allowedIncludes(): array
    {
        return [];
    }

    /**
     * Hook for explicit allowed fieldsets.
     *
     * @return array<string, list<string>>
     */
    protected function allowedFields(): array
    {
        return [];
    }

    /**
     * Resolve allowed includes from explicit array or JsonApiResource metadata.
     *
     * @return list<string>
     */
    protected function resolvedAllowedIncludes(): array
    {
        $explicitIncludes = $this->allowedIncludes();

        if ($explicitIncludes !== []) {
            return array_values(array_unique($explicitIncludes));
        }

        $resourceClass = $this->primaryJsonApiResource();

        if (! is_string($resourceClass) || ! is_subclass_of($resourceClass, JsonApiResource::class)) {
            return [];
        }

        return $resourceClass::jsonApiAllowedIncludes();
    }

    /**
     * Resolve allowed fields from explicit array or JsonApiResource metadata.
     *
     * @return array<string, list<string>>
     */
    protected function resolvedAllowedFields(): array
    {
        $explicitFields = $this->allowedFields();

        if ($explicitFields !== []) {
            return $explicitFields;
        }

        $resourceClass = $this->primaryJsonApiResource();

        if (! is_string($resourceClass) || ! is_subclass_of($resourceClass, JsonApiResource::class)) {
            return [];
        }

        return $resourceClass::jsonApiAllowedFieldsets();
    }
}
