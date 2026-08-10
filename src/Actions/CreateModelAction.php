<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Actions;

use Hatchyu\Steward\Data\AbstractData;
use Hatchyu\Steward\Exceptions\CreateModelException;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * @template TModel of Model
 * @template TData of AbstractData
 * @extends AbstractModelAction<TModel, TData>
 */
abstract class CreateModelAction extends AbstractModelAction
{
    /**
     * @param TData $data
     * @return TModel
     */
    final public function execute(AbstractData $data): Model
    {
        return $this->transaction(function () use ($data): Model {
            $model = $this->newModelInstance();

            $this->beforePersist($model, $data);

            try {
                $this->fillModel($model, $data);

                $status = $model->save();
            } catch (Throwable $exception) {
                throw new CreateModelException(message: $exception->getMessage(), code: $exception->getCode(), previous: $exception);
            }

            if ($status === false) {
                throw new CreateModelException();
            }

            $this->afterPersist($model, $data);

            return $this->afterExecute($model, $data);
        });
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
}
