<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ModelQuery extends AbstractModelQuery
{
    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return $this->model::class;
    }

    public function whereKey(int|string $id): static
    {
        return $this->withConstraint(
            static fn (Builder $query): Builder => $query->whereKey($id)
        );
    }

    public function whereInKey(int|string ...$ids): static
    {
        return $this->withConstraint(
            static fn (Builder $query): Builder => $query->whereKey($ids)
        );
    }
}
