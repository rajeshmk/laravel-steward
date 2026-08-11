<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Actions;

use Hatchyu\Steward\Exceptions\DeleteModelException;
use Hatchyu\Steward\Queries\Contracts\FindModelQueryContract;
use Illuminate\Database\Eloquent\Model;
use Throwable;

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
        if (! $this->isExpectedPersistedModel($model)) {
            throw new DeleteModelException('The supplied model is not valid for this action.');
        }

        $this->transaction(function () use ($model): void {
            try {
                $deleted = $model->delete();
            } catch (Throwable $exception) {
                // Retain diagnostic information for logs while returning a
                // stable, non-sensitive API error to the client.
                throw new DeleteModelException(previous: $exception);
            }

            if (! $deleted) {
                throw new DeleteModelException();
            }
        });
    }
}
