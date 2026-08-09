<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

use Hatchyu\Steward\Resources\JsonApiResource;
use Illuminate\Http\Request;
use Override;

final class ListQueryRequestValidationTestAddressResource extends JsonApiResource
{
    public static function jsonApiResourceType(): string
    {
        return 'customer-addresses';
    }

    #[Override]
    public static function jsonApiAllowedFields(): array
    {
        return ['city'];
    }

    protected function jsonApiAttributes(Request $request): array
    {
        return [];
    }
}
