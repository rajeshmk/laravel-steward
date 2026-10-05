<?php

declare(strict_types=1);

use Hatchyu\Steward\Http\ApiResponseFormat;
use Hatchyu\Steward\Http\ApiResponseFormatResolver;
use Hatchyu\Steward\Middleware\EnforceApiRequestFormat;
use Illuminate\Http\Request;

test('ApiResponseFormatResolver safely handles array query parameter and array headers', function (): void {
    $request = Request::create('/test?format[]=invalid', 'GET');
    $request->headers->set('Accept', ['application/json', 'application/vnd.api+json']);

    $format = ApiResponseFormatResolver::resolve($request);

    expect($format)->toBe(ApiResponseFormat::JSON_API);
});

test('ApiResponseFormatResolver resolves rest when format query is rest', function (): void {
    $request = Request::create('/test?format=rest', 'GET');

    $format = ApiResponseFormatResolver::resolve($request);

    expect($format)->toBe(ApiResponseFormat::REST);
});

test('EnforceApiRequestFormat safely handles array content type headers on mutation requests', function (): void {
    $middleware = new EnforceApiRequestFormat();
    $request = Request::create('/test', 'POST');
    $request->headers->set('Content-Type', ['application/vnd.api+json; charset=utf-8']);
    $request->replace([
        'data' => [
            'type' => 'customers',
            'attributes' => ['name' => 'Alice'],
        ],
    ]);

    $response = $middleware->handle($request, fn ($req) => response()->json(['success' => true]), 'jsonapi');

    expect($response->getStatusCode())->toBe(200);
});
