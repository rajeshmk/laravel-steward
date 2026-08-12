<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests;

use Hatchyu\Steward\Requests\Rules\FieldsQueryRule;
use Hatchyu\Steward\Requests\Rules\FilterQueryRule;
use Hatchyu\Steward\Requests\Rules\IncludeQueryRule;
use Hatchyu\Steward\Requests\Rules\PageQueryRule;
use Hatchyu\Steward\Requests\Rules\SortQueryRule;
use Hatchyu\Steward\Resources\JsonApiResource;
use Override;

abstract class ListQueryRequest extends BaseQueryRequest
{
    #[Override]
    protected function queryRules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'filter' => ['sometimes', new FilterQueryRule()],
            'sort' => ['sometimes', new SortQueryRule()],
            'include' => ['sometimes', new IncludeQueryRule($this->resolvedAllowedIncludes())],
            'fields' => ['sometimes', new FieldsQueryRule($this->resolvedAllowedFields())],
            'fields.*' => ['sometimes'],
            'page' => ['sometimes', new PageQueryRule()],
            'page.number' => ['sometimes', 'integer', 'min:1'],
            'page.size' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'size' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return list<string>
     */
    protected function allowedIncludes(): array
    {
        return [];
    }

    /**
     * @return array<string, list<string>>
     */
    protected function allowedFields(): array
    {
        return [];
    }

    /**
     * @return class-string<JsonApiResource>|null
     */
    protected function primaryJsonApiResource(): ?string
    {
        return null;
    }

    /**
     * @return list<string>
     */
    private function resolvedAllowedIncludes(): array
    {
        $allowedIncludes = $this->allowedIncludes();

        if ($allowedIncludes !== []) {
            return $allowedIncludes;
        }

        $resource = $this->primaryJsonApiResource();

        if (! is_string($resource) || ! is_subclass_of($resource, JsonApiResource::class)) {
            return [];
        }

        return $resource::jsonApiAllowedIncludes();
    }

    /**
     * @return array<string, list<string>>
     */
    private function resolvedAllowedFields(): array
    {
        $allowedFields = $this->allowedFields();

        if ($allowedFields !== []) {
            return $allowedFields;
        }

        $resource = $this->primaryJsonApiResource();

        if (! is_string($resource) || ! is_subclass_of($resource, JsonApiResource::class)) {
            return [];
        }

        return $resource::jsonApiAllowedFieldsets();
    }
}
