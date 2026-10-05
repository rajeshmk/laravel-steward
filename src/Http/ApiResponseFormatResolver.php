<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Http;

use Illuminate\Http\Request;

final class ApiResponseFormatResolver
{
    public static function resolve(?Request $request = null): ApiResponseFormat
    {
        $request ??= request();

        if ($request->attributes->has('steward_api_response_format')) {
            $forcedFormat = $request->attributes->get('steward_api_response_format');
            if ($forcedFormat instanceof ApiResponseFormat) {
                return $forcedFormat;
            }
        }

        $allowOverride = (bool) config('steward.api.allow_format_override', true);
        if ($allowOverride) {
            $paramName = (string) config('steward.api.query_parameter', 'format');
            $rawQuery = $request->query($paramName, '');
            $formatParam = strtolower(is_string($rawQuery) ? $rawQuery : '');

            if (in_array($formatParam, ['jsonapi', 'json_api'], true)) {
                return ApiResponseFormat::JSON_API;
            }

            if ($formatParam === 'rest') {
                return ApiResponseFormat::REST;
            }

            $acceptHeader = strtolower(implode(',', $request->headers->all('accept')));
            if (str_contains($acceptHeader, 'application/vnd.api+json')) {
                return ApiResponseFormat::JSON_API;
            }
        }

        $defaultConfig = strtolower((string) config('steward.api.default_format', 'rest'));

        return in_array($defaultConfig, ['jsonapi', 'json_api'], true)
            ? ApiResponseFormat::JSON_API
            : ApiResponseFormat::REST;
    }
}
