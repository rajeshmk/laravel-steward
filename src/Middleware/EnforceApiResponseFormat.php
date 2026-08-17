<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Middleware;

use Closure;
use Hatchyu\Steward\Http\ApiResponseFormat;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnforceApiResponseFormat
{
    public function handle(Request $request, Closure $next, string $format = 'jsonapi'): Response
    {
        $forced = strtolower($format) === 'rest' ? ApiResponseFormat::REST : ApiResponseFormat::JSON_API;
        $request->attributes->set('steward_api_response_format', $forced);

        return $next($request);
    }
}
