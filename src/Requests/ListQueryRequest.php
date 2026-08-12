<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests;

use Hatchyu\Steward\Requests\Rules\FieldsQueryRule;
use Hatchyu\Steward\Requests\Rules\FilterQueryRule;
use Hatchyu\Steward\Requests\Rules\IncludeQueryRule;
use Hatchyu\Steward\Requests\Rules\PageQueryRule;
use Hatchyu\Steward\Requests\Rules\SortQueryRule;
use Hatchyu\Steward\Queries\Support\QueryParamLimits;
use Hatchyu\Steward\Resources\Concerns\ResolvesJsonApiResourceMetadata;
use Hatchyu\Steward\Resources\JsonApiResource;
use Override;

abstract class ListQueryRequest extends BaseQueryRequest
{
    use ResolvesJsonApiResourceMetadata;

    #[Override]
    protected function queryRules(): array
    {
        $limits = resolve(QueryParamLimits::class);

        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'filter' => ['sometimes', new FilterQueryRule($limits)],
            'sort' => ['sometimes', new SortQueryRule($limits)],
            'include' => ['sometimes', new IncludeQueryRule($this->resolvedAllowedIncludes(), $limits)],
            'fields' => ['sometimes', new FieldsQueryRule($this->resolvedAllowedFields(), $limits)],
            'fields.*' => ['sometimes'],
            'page' => ['sometimes', new PageQueryRule($limits)],
            'page.number' => ['sometimes', 'integer', 'min:1', 'max:' . $limits->maxPageNumber],
            'page.size' => ['sometimes', 'integer', 'min:1', 'max:' . $limits->maxPageSize],
            'page.cursor' => ['sometimes', 'string', 'min:1', 'max:' . $limits->maxValueLength],
            'size' => ['sometimes', 'integer', 'min:1', 'max:' . $limits->maxPageSize],
            'cursor' => ['sometimes', 'string', 'min:1', 'max:' . $limits->maxValueLength],
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
}
