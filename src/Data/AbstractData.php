<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Data;

use BackedEnum;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use UnitEnum;

abstract class AbstractData
{
    /**
     * @var array<class-string, list<ReflectionParameter>>
     */
    private static array $constructorParametersCache = [];

    /**
     * @var array<class-string<BackedEnum>, 'int'|'string'|null>
     */
    private static array $enumBackingTypeCache = [];

    public static function fromArray(array $attributes): static
    {
        $args = [];

        foreach (self::getConstructorParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                continue;
            }

            $name = $parameter->getName();
            $snakeCaseName = self::toSnakeCase($name);

            if (array_key_exists($name, $attributes)) {
                $args[$name] = self::castAttributeToParameterType($parameter, $attributes[$name]);

                continue;
            }

            if (array_key_exists($snakeCaseName, $attributes)) {
                $args[$name] = self::castAttributeToParameterType($parameter, $attributes[$snakeCaseName]);

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[$name] = $parameter->getDefaultValue();

                continue;
            }

            self::throwMissingAttributeException($name);
        }

        return new static(...$args);
    }

    public function toArray(): array
    {
        $payload = [];

        foreach (get_object_vars($this) as $key => $value) {
            $payload[$key] = $this->serializeValueForArray($value);
        }

        return $payload;
    }

    public function toModelAttributes(): array
    {
        $attributes = $this->toArray();
        $normalized = [];

        foreach ($attributes as $key => $value) {
            if (is_int($key)) {
                $normalized[$key] = $value;

                continue;
            }

            $normalized[self::toSnakeCase($key)] = $value;
        }

        return $normalized;
    }

    /**
     * @return list<ReflectionParameter>
     */
    private static function getConstructorParameters(): array
    {
        $class = static::class;

        if (isset(self::$constructorParametersCache[$class])) {
            return self::$constructorParametersCache[$class];
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return self::$constructorParametersCache[$class] = [];
        }

        return self::$constructorParametersCache[$class] = $constructor->getParameters();
    }

    private function serializeValueForArray(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof self) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return array_map($this->serializeValueForArray(...), $value);
        }

        return $value;
    }

    private static function castAttributeToParameterType(ReflectionParameter $parameter, mixed $value): mixed
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType && ! $type instanceof ReflectionUnionType) {
            return $value;
        }

        $enumType = self::resolveEnumTypeName($type);

        if ($value === null && $type->allowsNull()) {
            return null;
        }

        if ($type->allowsNull() && is_string($value) && trim($value) === '') {
            return null;
        }

        if (self::containsBuiltinType($type, 'bool')) {
            return self::castValueToBool($parameter->getName(), $value);
        }

        if ($enumType === null) {
            return $value;
        }

        if ($value instanceof $enumType) {
            return $value;
        }

        return self::castValueToEnum($parameter->getName(), $enumType, $value);
    }

    private static function containsBuiltinType(ReflectionNamedType|ReflectionUnionType $type, string $builtin): bool
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->isBuiltin() && $type->getName() === $builtin;
        }

        foreach ($type->getTypes() as $unionType) {
            if ($unionType instanceof ReflectionNamedType && $unionType->isBuiltin() && $unionType->getName() === $builtin) {
                return true;
            }
        }

        return false;
    }

    private static function resolveEnumTypeName(ReflectionNamedType|ReflectionUnionType $type): ?string
    {
        if ($type instanceof ReflectionNamedType) {
            if ($type->isBuiltin() || ! enum_exists($type->getName())) {
                return null;
            }

            return $type->getName();
        }

        foreach ($type->getTypes() as $unionType) {
            if (! $unionType instanceof ReflectionNamedType || $unionType->isBuiltin()) {
                continue;
            }

            if (enum_exists($unionType->getName())) {
                return $unionType->getName();
            }
        }

        return null;
    }

    /**
     * @param class-string<UnitEnum> $enumType
     */
    private static function castValueToEnum(string $parameterName, string $enumType, mixed $value): UnitEnum
    {
        if (is_subclass_of($enumType, BackedEnum::class)) {
            $value = self::normalizeBackedEnumInput($parameterName, $enumType, $value);

            /** @var class-string<BackedEnum> $enumType */
            $enum = $enumType::tryFrom($value);

            if ($enum instanceof BackedEnum) {
                return $enum;
            }

            self::throwInvalidEnumAttributeException($parameterName, $enumType, $value);
        }

        if (is_subclass_of($enumType, UnitEnum::class)) {
            if (is_string($value)) {
                foreach ($enumType::cases() as $case) {
                    if ($case->name === $value) {
                        return $case;
                    }
                }
            }

            self::throwInvalidEnumAttributeException($parameterName, $enumType, $value);
        }

        self::throwInvalidEnumAttributeException($parameterName, $enumType, $value);
    }

    /**
     * @param class-string<BackedEnum> $enumClass
     */
    private static function normalizeBackedEnumInput(
        string $parameterName,
        string $enumClass,
        mixed $value
    ): int|string {
        $backingType = self::getEnumBackingType($enumClass);

        if ($backingType === 'int') {
            if (is_int($value)) {
                return $value;
            }

            if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
                return (int) $value;
            }
        }

        if ($backingType === 'string' && (is_string($value) || is_int($value) || is_float($value) || is_bool($value))) {
            return (string) $value;
        }

        self::throwInvalidEnumAttributeException($parameterName, $enumClass, $value);
    }

    /**
     * @param class-string<BackedEnum> $enumClass
     *
     * @return 'int'|'string'|null
     */
    private static function getEnumBackingType(string $enumClass): ?string
    {
        if (isset(self::$enumBackingTypeCache[$enumClass])) {
            return self::$enumBackingTypeCache[$enumClass];
        }

        $backingType = new ReflectionEnum($enumClass)->getBackingType()?->getName();

        return self::$enumBackingTypeCache[$enumClass] = in_array($backingType, ['int', 'string'], true)
            ? $backingType
            : null;
    }

    private static function throwMissingAttributeException(string $parameterName): never
    {
        throw new InvalidArgumentException(
            sprintf(
                'Missing required attribute "%s" for %s.',
                $parameterName,
                static::class
            )
        );
    }

    private static function throwInvalidEnumAttributeException(
        string $parameterName,
        string $enumClass,
        mixed $value
    ): never {
        throw new InvalidArgumentException(
            sprintf(
                'Invalid enum value for attribute "%s" in %s. Expected %s, got %s (%s).',
                $parameterName,
                static::class,
                $enumClass,
                get_debug_type($value),
                self::stringifyValue($value)
            )
        );
    }

    private static function stringifyValue(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return var_export($value, true);
        }

        if (is_array($value)) {
            return 'array';
        }

        return get_debug_type($value);
    }

    private static function castValueToBool(string $parameterName, mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid boolean value for attribute "%s" in %s. Got %s (%s).',
                    $parameterName,
                    static::class,
                    get_debug_type($value),
                    self::stringifyValue($value)
                )
            );
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($normalized === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid boolean value for attribute "%s" in %s. Got %s (%s).',
                    $parameterName,
                    static::class,
                    get_debug_type($value),
                    self::stringifyValue($value)
                )
            );
        }

        return $normalized;
    }

    private static function toSnakeCase(string $value): string
    {
        $value = preg_replace('/(?<=\p{Ll})(\p{Lu})/u', '_$1', $value) ?? $value;
        $value = preg_replace('/(?<=\p{Lu})(\p{Lu}\p{Ll})/u', '_$1', $value) ?? $value;

        return strtolower($value);
    }
}
