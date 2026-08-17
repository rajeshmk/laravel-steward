<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnforceApiRequestFormat
{
    public function handle(Request $request, Closure $next, string $requiredFormat = 'jsonapi'): Response
    {
        $policy = strtolower($requiredFormat);

        if ($policy === 'jsonapi' || $policy === 'json_api') {
            $hasHeader = str_contains(strtolower((string) $request->header('Content-Type', '')), 'application/vnd.api+json');
            $hasDataAttributes = $request->has('data.attributes') || $request->has('data.type');

            if (! $hasHeader && ! $hasDataAttributes) {
                return new JsonResponse([
                    'errors' => [
                        [
                            'status' => '415',
                            'title' => 'Unsupported Media Type',
                            'detail' => 'Requests to this endpoint must use Content-Type: application/vnd.api+json and supply a valid JSON:API payload body.',
                        ],
                    ],
                ], 415, ['Content-Type' => 'application/vnd.api+json']);
            }
        } elseif ($policy === 'rest') {
            if ($request->has('data.attributes')) {
                return new JsonResponse([
                    'message' => 'Requests to this endpoint must use standard REST payload format.',
                ], 400);
            }
        }

        return $next($request);
    }
}
