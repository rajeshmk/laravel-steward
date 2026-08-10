<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Actions;

use Hatchyu\Steward\Data\AbstractData;
use Hatchyu\Steward\Exceptions\UpdateModelException;
use Hatchyu\Steward\Queries\Contracts\FindModelQueryContract;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Throwable;

/**
 * @template TModel of Model
 * @template TData of AbstractData
 * @extends AbstractModelAction<TModel, TData>
 */
abstract class UpdateModelAction extends AbstractModelAction
{
    public function __construct(
        private readonly FindModelQueryContract $findModelQuery,
    ) {}

    /**
     * @param int|string $id
     * @param TData $data
     * @return TModel
     */
    final public function execute(int|string $id, AbstractData $data): Model
    {
        return $this->transaction(function () use ($id, $data): Model {
            $actionModelClass = $this->model()::class;
            $queryModelClass = $this->findModelQuery->modelClass();

            if ($queryModelClass !== $actionModelClass) {
                throw new LogicException(sprintf(
                    'Expected query model class to be %s, got %s.',
                    $actionModelClass,
                    $queryModelClass
                ));
            }

            $model = $this->findModelQuery->byIdOrFail($id);

            return $this->executeUpdate($model, $data);
        });
    }

    /**
     * @param TModel $model
     * @param TData $data
     * @return TModel
     */
    final public function executeModel(Model $model, AbstractData $data): Model
    {
        $modelKey = $model->getKey();

        if (
            ! $model->exists
            || $modelKey === null
            || ! $this->isRegisteredInstance($model)
        ) {
            throw new UpdateModelException(message: sprintf('Model [%s] with key [%s] is not a valid registered instance.', $model::class, (string) $modelKey));
        }

        return $this->transaction(fn (): Model => $this->executeUpdate($model, $data));
    }

    /**
     * @param TModel $model
     * @param TData $data
     */
    protected function beforePersist(Model $model, AbstractData $data): void
    {
        // Hook for subclasses.
    }

    /**
     * @param TModel $model
     * @param TData $data
     */
    protected function afterPersist(Model $model, AbstractData $data): void
    {
        // Hook for subclasses.
    }

    /**
     * @param TModel $model
     * @param TData $data
     * @return TModel
     */
    protected function afterExecute(Model $model, AbstractData $data): Model
    {
        return $model;
    }

    /**
     * @param TModel $model
     * @param TData $data
     * @return TModel
     */
    private function executeUpdate(Model $model, AbstractData $data): Model
    {
        $this->beforePersist($model, $data);

        try {
            $this->fillModel($model, $data);

            $status = $model->save();
        } catch (Throwable $exception) {
            throw new UpdateModelException(message: $exception->getMessage(), code: $exception->getCode(), previous: $exception);
        }

        if ($status === false) {
            throw new UpdateModelException();
        }

        $this->afterPersist($model, $data);

        return $this->afterExecute($model, $data);
    }

    private function isRegisteredInstance(Model $model): bool
    {
        $registeredModel = $this->model();

        return $registeredModel->getTable() === $model->getTable()
            && (
                $registeredModel->getConnectionName() === null
                || $registeredModel->getConnectionName() === $model->getConnectionName()
            );
    }
}
