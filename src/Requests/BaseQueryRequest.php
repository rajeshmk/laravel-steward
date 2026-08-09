<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests;

use Hatchyu\Steward\Queries\Contracts\QueryParamsParserContract;
use Hatchyu\Steward\Queries\Params\QueryParams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

abstract class BaseQueryRequest extends Request
{
    public static function from(Request $request): static
    {
        /** @var static $instance */
        $instance = static::createFromBase($request);

        return $instance;
    }

    public function toQueryParams(?QueryParamsParserContract $parser = null): QueryParams
    {
        $this->validateQuery();

        $parser ??= resolve(QueryParamsParserContract::class);

        return $parser->parseRequest($this);
    }

    /**
     * @return array<string, mixed>
     */
    public function validateQuery(): array
    {
        return Validator::make(
            data: $this->all(),
            rules: $this->queryRules(),
            messages: $this->messages(),
            attributes: $this->attributes()
        )->validate();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function queryRules(): array
    {
        return [];
    }
}
