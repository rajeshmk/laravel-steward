<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

use Hatchyu\Steward\Resources\JsonApiResource;
use Illuminate\Http\Request;
use Override;

final class ListQueryRequestValidationTestCustomerResource extends JsonApiResource
{
    public static function jsonApiResourceType(): string
    {
        return 'customers';
    }

    #[Override]
    public static function jsonApiAllowedFields(): array
    {
        return ['name', 'email'];
    }

    #[Override]
    protected static function jsonApiIncludeResources(): array
    {
        return [
            'addresses' => ListQueryRequestValidationTestAddressResource::class,
        ];
    }

    protected function jsonApiAttributes(Request $request): array
    {
        return [];
    }
}
