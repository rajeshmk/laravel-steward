<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractModelQuery extends AbstractQuery
{
    /**
     * @var list<Closure(Builder): Builder>
     */
    private array $constraints = [];

    public function __construct(
        protected readonly Model $model,
    ) {}

    protected function query(): Builder
    {
        $query = $this->apply($this->model->query());

        foreach ($this->constraints as $constraint) {
            $query = $constraint($query);
        }

        return $query;
    }

    protected function withConstraint(Closure $constraint): static
    {
        $clone = clone $this;
        $clone->constraints[] = $constraint;

        return $clone;
    }

    protected function apply(Builder $query): Builder
    {
        return $query;
    }
}
