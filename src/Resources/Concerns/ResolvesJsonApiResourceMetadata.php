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
    protected function resolveAllowedIncludes(?string $resourceClass = null, array $explicitIncludes = []): array
    {
        $explicitIncludes = $explicitIncludes !== []
            ? $explicitIncludes
            : (method_exists($this, 'allowedIncludes') ? $this->allowedIncludes() : []);

        if ($explicitIncludes !== []) {
            return array_values(array_unique($explicitIncludes));
        }

        $resourceClass ??= method_exists($this, 'primaryJsonApiResource')
            ? $this->primaryJsonApiResource()
            : (method_exists($this, 'jsonApiResource') ? $this->jsonApiResource() : null);

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
    protected function resolveAllowedFields(?string $resourceClass = null, array $explicitFields = []): array
    {
        $explicitFields = $explicitFields !== []
            ? $explicitFields
            : (method_exists($this, 'allowedFields') ? $this->allowedFields() : []);

        if ($explicitFields !== []) {
            return $explicitFields;
        }

        $resourceClass ??= method_exists($this, 'primaryJsonApiResource')
            ? $this->primaryJsonApiResource()
            : (method_exists($this, 'jsonApiResource') ? $this->jsonApiResource() : null);

        if (! is_string($resourceClass) || ! is_subclass_of($resourceClass, JsonApiResource::class)) {
            return [];
        }

        return $resourceClass::jsonApiAllowedFieldsets();
    }

    /**
     * @return list<string>
     */
    protected function resolvedAllowedIncludes(): array
    {
        return $this->resolveAllowedIncludes();
    }

    /**
     * @return array<string, list<string>>
     */
    protected function resolvedAllowedFields(): array
    {
        return $this->resolveAllowedFields();
    }
}
