<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

use Hatchyu\Steward\Resources\StewardResource;
use Illuminate\Http\Request;
use Override;

final class ListQueryRequestValidationTestAddressResource extends StewardResource
{
    public static function resourceType(): string
    {
        return 'customer-addresses';
    }

    #[Override]
    public static function allowedFields(): array
    {
        return ['city'];
    }

    public function toAttributes(Request $request): array
    {
        return [];
    }
}
