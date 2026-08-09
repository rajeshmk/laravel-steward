<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Criteria;

use Illuminate\Database\Eloquent\Builder;

abstract class AbstractCriterion
{
    protected string $field;

    public function __construct(
        protected string $name,
        ?string $field = null,
    ) {
        $this->field = $field ?? $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function field(): string
    {
        return $this->field;
    }

    public function matches(string $name): bool
    {
        return $this->name === $name;
    }

    abstract public function apply(Builder $query, mixed $value): Builder;

    /**
     * Wrap a field name for use in raw expressions (e.g. reserved words).
     * Override in subclasses if your driver uses different identifier quotes.
     */
    protected function wrapField(string $field): string
    {
        return '`' . str_replace('.', '`.`', $field) . '`';
    }
}
