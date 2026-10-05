<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

use Hatchyu\Steward\Resources\StewardResource;
use Illuminate\Http\Request;
use Override;

final class ListQueryRequestValidationTestCustomerResource extends StewardResource
{
    public static function resourceType(): string
    {
        return 'customers';
    }

    #[Override]
    public static function allowedFields(): array
    {
        return ['name', 'email'];
    }

    public function toAttributes(Request $request): array
    {
        return [];
    }

    #[Override]
    protected static function allowedIncludes(): array
    {
        return [
            'addresses' => ListQueryRequestValidationTestAddressResource::class,
        ];
    }
}
