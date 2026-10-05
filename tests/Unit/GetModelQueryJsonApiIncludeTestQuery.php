<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

use Hatchyu\Steward\Queries\Contracts\QueryParamsProcessorContract;
use Hatchyu\Steward\Queries\GetModelQuery;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Hatchyu\Steward\Tests\Fixtures\Customer;
use Hatchyu\Steward\Tests\Unit\ListQueryRequestValidationTestCustomerResource as CustomerResource;
use Illuminate\Database\Eloquent\Builder;

final class GetModelQueryJsonApiIncludeTestQuery extends GetModelQuery
{
    public function __construct(QueryParamsProcessorContract $processor)
    {
        parent::__construct(new Customer(), $processor);
    }

    public function applyForTest(QueryParams $params): Builder
    {
        $query = $this->query();
        $this->applyQueryParams($query, $params);

        return $query;
    }

    protected function jsonApiResource(): string
    {
        return CustomerResource::class;
    }
}
