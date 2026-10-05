<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Resources;

use Hatchyu\Steward\Http\ApiResponseFormat;
use Hatchyu\Steward\Http\ApiResponseFormatResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Override;

abstract class StewardResource extends JsonApiResource
{
    /**
     * The resource type string (defaults to kebab-case plural of class basename without 'Resource').
     */
    public static function resourceType(): string
    {
        $className = class_basename(static::class);
        $baseName = Str::beforeLast($className, 'Resource');

        return Str::kebab(Str::pluralStudly($baseName));
    }

    /**
     * Allowed fields for sparse fieldsets.
     *
     * @return list<string>
     */
    public static function allowedFields(): array
    {
        return [];
    }

    /**
     * Map of resource field names to database column names.
     *
     * @return array<string, list<string>>
     */
    public static function fieldColumnMap(): array
    {
        $map = [];

        foreach (static::allowedFields() as $field) {
            $map[$field] = [$field];
        }

        return $map;
    }

    /**
     * Resource attributes payload.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(Request $request): array
    {
        if ($this->resource instanceof Model) {
            return $this->resource->attributesToArray();
        }

        return [];
    }

    /**
     * JSON:API relationships mapping (defaults to allowedIncludes()).
     *
     * @return array<int|string, mixed>
     */
    public function toRelationships(Request $request): array
    {
        return static::allowedIncludes();
    }

    // Helper compatibility aliases for Steward Query Parsers & Validators
    public static function jsonApiResourceType(): string
    {
        return static::resourceType();
    }

    /**
     * @return list<string>
     */
    public static function jsonApiAllowedFields(): array
    {
        return static::allowedFields();
    }

    /**
     * @return array<string, list<string>>
     */
    public static function jsonApiFieldColumnMap(): array
    {
        return static::fieldColumnMap();
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
        return static::resourceType();
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

        foreach (static::allowedIncludes() as $relation => $resourceClass) {
            if (! is_string($relation) || ! is_string($resourceClass) || ! is_subclass_of($resourceClass, self::class)) {
                continue;
            }

            $path = $prefix === '' ? $relation : $prefix . '.' . $relation;
            $includes[] = $path;

            $includes = [
                ...$includes,
                ...$resourceClass::jsonApiAllowedIncludes($depth - 1, $path),
            ];
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
            static::resourceType() => static::allowedFields(),
        ];

        foreach (static::allowedIncludes() as $resourceClass) {
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
            $includes = $resource::allowedIncludes();
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
    #[Override]
    public function toArray(Request $request): array
    {
        if (ApiResponseFormatResolver::resolve($request) === ApiResponseFormat::REST) {
            return $this->toRestArray($request);
        }

        $result = parent::toArray($request);

        return is_array($result) ? $result : (array) $result;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function with($request)
    {
        if (ApiResponseFormatResolver::resolve($request) === ApiResponseFormat::REST) {
            return [];
        }

        return parent::with($request);
    }

    #[Override]
    public function withResponse(Request $request, JsonResponse $response): void
    {
        if (ApiResponseFormatResolver::resolve($request) === ApiResponseFormat::JSON_API) {
            $response->header('Content-Type', 'application/vnd.api+json');
        } else {
            $response->header('Content-Type', 'application/json');
        }
    }

    /**
     * Allowed relationship inclusion paths.
     *
     * @return array<string, class-string<self>>
     */
    protected static function allowedIncludes(): array
    {
        return [];
    }

    /**
     * @return array<int|string, mixed>
     */
    protected static function jsonApiIncludeResources(): array
    {
        return static::allowedIncludes();
    }

    /**
     * @return array<string, mixed>
     */
    protected function toRestArray(Request $request): array
    {
        $id = $this->toId($request);
        $attributes = $this->toAttributes($request);

        $rest = [];
        if ($id !== null) {
            $rest['id'] = is_numeric($id) ? (int) $id : $id;
        }

        $rest = array_merge($rest, $attributes);

        foreach (static::allowedIncludes() as $relation => $resourceClass) {
            if (is_string($relation) && $this->resource instanceof Model && $this->resource->relationLoaded($relation)) {
                $related = $this->resource->getRelation($relation);
                if ($related !== null) {
                    if (is_string($resourceClass) && is_subclass_of($resourceClass, self::class)) {
                        $rest[$relation] = $related instanceof Collection || is_array($related)
                            ? $resourceClass::collection($related)
                            : new $resourceClass($related);
                    } else {
                        $rest[$relation] = $related;
                    }
                }
            }
        }

        return $rest;
    }

    #[Override]
    protected static function newCollection($resource)
    {
        return new AnonymousResourceCollection($resource, static::class);
    }
}
