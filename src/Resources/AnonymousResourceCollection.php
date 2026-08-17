<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Resources;

use Hatchyu\Steward\Http\ApiResponseFormat;
use Hatchyu\Steward\Http\ApiResponseFormatResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\JsonApi\AnonymousResourceCollection as BaseAnonymousResourceCollection;

class AnonymousResourceCollection extends BaseAnonymousResourceCollection
{
    public function withResponse($request, $response): void
    {
        if ($response instanceof JsonResponse) {
            if (ApiResponseFormatResolver::resolve($request) === ApiResponseFormat::JSON_API) {
                $response->header('Content-Type', 'application/vnd.api+json');
            } else {
                $response->header('Content-Type', 'application/json');
            }
        }
    }
}
