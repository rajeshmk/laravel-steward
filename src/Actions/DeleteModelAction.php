<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Actions;

use Hatchyu\Steward\Exceptions\DeleteModelException;
use Hatchyu\Steward\Queries\Contracts\FindModelQueryContract;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 * @extends AbstractModelAction<TModel, \Hatchyu\Steward\Data\AbstractData>
 */
abstract class DeleteModelAction extends AbstractModelAction
{
    public function __construct(
        protected readonly FindModelQueryContract $findModelQuery,
    ) {}

    final public function execute(int|string $id): void
    {
        $model = $this->findModelQuery->byIdOrFail($id);

        $this->executeModel($model);
    }

    /**
     * @param TModel $model
     */
    final public function executeModel(Model $model): void
    {
        $this->transaction(function () use ($model): void {
            $deleted = $model->delete();

            if (! $deleted) {
                throw new DeleteModelException();
            }
        });
    }
}
