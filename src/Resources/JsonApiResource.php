<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Override;

abstract class JsonApiResource extends \Illuminate\Http\Resources\JsonApi\JsonApiResource
{
    abstract public static function jsonApiResourceType(): string;

    /**
     * @return list<string>
     */
    public static function jsonApiAllowedFields(): array
    {
        return [];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function jsonApiFieldColumnMap(): array
    {
        $map = [];

        foreach (static::jsonApiAllowedFields() as $field) {
            $map[$field] = [$field];
        }

        return $map;
    }

    public function toId(Request $request): ?string
    {
        if ($this->resource instanceof Model) {
            return (string) $this->resource->getRouteKey();
        }

        return null;
    }

    public function toType(Request $request): ?string
    {
        return $this->jsonApiType($request);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toAttributes(Request $request): array
    {
        return $this->jsonApiAttributes($request);
    }

    /**
     * @return array<int|string, mixed>
     */
    #[Override]
    public function toRelationships(Request $request): array
    {
        return $this->jsonApiRelationships($request);
    }

    /**
     * @return list<string>
     */
    public static function jsonApiAllowedIncludes(int $depth = 3, string $prefix = ''): array
    {
        if ($depth < 1) {
            return [];
        }

        $includes = [];

        foreach (static::jsonApiIncludeResources() as $relation => $resourceClass) {
            if (! is_string($relation)) {
                continue;
            }

            $path = $prefix === '' ? $relation : $prefix . '.' . $relation;
            $includes[] = $path;

            if (is_string($resourceClass) && is_subclass_of($resourceClass, self::class)) {
                $includes = [
                    ...$includes,
                    ...$resourceClass::jsonApiAllowedIncludes($depth - 1, $path),
                ];
            }
        }

        return array_values(array_unique($includes));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function jsonApiAllowedFieldsets(int $depth = 3): array
    {
        if ($depth < 1) {
            return [];
        }

        $fieldsets = [
            static::jsonApiResourceType() => static::jsonApiAllowedFields(),
        ];

        foreach (static::jsonApiIncludeResources() as $resourceClass) {
            if (! is_string($resourceClass) || ! is_subclass_of($resourceClass, self::class)) {
                continue;
            }

            foreach ($resourceClass::jsonApiAllowedFieldsets($depth - 1) as $type => $fields) {
                $fieldsets[$type] = array_values(array_unique(array_merge($fieldsets[$type] ?? [], $fields)));
            }
        }

        return $fieldsets;
    }

    /**
     * @return class-string<self>|null
     */
    public static function jsonApiResourceForIncludePath(string $path): ?string
    {
        $resource = static::class;

        foreach (array_filter(explode('.', $path)) as $segment) {
            $includes = $resource::jsonApiIncludeResources();
            $next = $includes[$segment] ?? null;

            if (! is_string($next) || ! is_subclass_of($next, self::class)) {
                return null;
            }

            $resource = $next;
        }

        return $resource;
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function jsonApiAttributes(Request $request): array;

    /**
     * @return array<int|string, mixed>
     */
    protected static function jsonApiIncludeResources(): array
    {
        return [];
    }

    protected function jsonApiType(Request $request): string
    {
        return static::jsonApiResourceType();
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function jsonApiRelationships(Request $request): array
    {
        return static::jsonApiIncludeResources();
    }
}
