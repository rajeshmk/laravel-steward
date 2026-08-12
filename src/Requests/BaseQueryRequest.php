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
        $instance->query->replace($instance->validationData());

        return $instance;
    }

    public function toQueryParams(?QueryParamsParserContract $parser = null): QueryParams
    {
        $this->validateQuery();
        $this->query->replace($this->validationData());

        $parser ??= resolve(QueryParamsParserContract::class);

        return $parser->parseRequest($this);
    }

    /**
     * Extracts parameters from URL query strings, JSON:API body attributes, or top-level body parameters.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        $query = $this->query->all();

        $data = $this->input('data');
        if (is_array($data) && is_array($data['attributes'] ?? null)) {
            $attributes = $data['attributes'];
            foreach (['filter', 'sort', 'include', 'fields', 'page', 'search'] as $key) {
                if (! array_key_exists($key, $query) && array_key_exists($key, $attributes)) {
                    $query[$key] = $attributes[$key];
                }
            }

            return $query;
        }

        $input = $this->input();
        if (is_array($input)) {
            foreach (['filter', 'sort', 'include', 'fields', 'page', 'search'] as $key) {
                if (! array_key_exists($key, $query) && array_key_exists($key, $input)) {
                    $query[$key] = $input[$key];
                }
            }
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    public function validateQuery(): array
    {
        return Validator::make(
            data: $this->validationData(),
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
