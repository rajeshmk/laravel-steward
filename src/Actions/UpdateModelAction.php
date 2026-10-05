<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Actions;

use Hatchyu\Steward\Data\AbstractData;
use Hatchyu\Steward\Exceptions\UpdateModelException;
use Hatchyu\Steward\Queries\Contracts\FindModelQueryContract;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * @template TModel of Model
 * @template TData of AbstractData
 *
 * @extends AbstractModelAction<TModel, TData>
 */
abstract class UpdateModelAction extends AbstractModelAction
{
    public function __construct(
        private readonly FindModelQueryContract $findModelQuery,
    ) {}

    /**
     * @param TData $data
     *
     * @return TModel
     */
    final public function execute(int|string $id, AbstractData $data): Model
    {
        return $this->transaction(function () use ($id, $data): Model {
            $this->ensureQueryModelMatchesAction($this->findModelQuery);

            /** @var TModel $model */
            $model = $this->findModelQuery->byIdOrFail($id);

            return $this->executeUpdate($model, $data);
        });
    }

    /**
     * @param TModel $model
     * @param TData  $data
     *
     * @return TModel
     */
    final public function executeModel(Model $model, AbstractData $data): Model
    {
        if (! $this->isExpectedPersistedModel($model)) {
            throw new UpdateModelException('The supplied model is not valid for this action.');
        }

        return $this->transaction(fn (): Model => $this->executeUpdate($model, $data));
    }

    /**
     * @param TModel $model
     * @param TData  $data
     */
    protected function beforePersist(Model $model, AbstractData $data): void
    {
        // Hook for subclasses.
    }

    /**
     * @param TModel $model
     * @param TData  $data
     */
    protected function afterPersist(Model $model, AbstractData $data): void
    {
        // Hook for subclasses.
    }

    /**
     * @param TModel $model
     * @param TData  $data
     *
     * @return TModel
     */
    protected function afterExecute(Model $model, AbstractData $data): Model
    {
        return $model;
    }

    /**
     * @param TModel $model
     * @param TData  $data
     *
     * @return TModel
     */
    private function executeUpdate(Model $model, AbstractData $data): Model
    {
        $this->beforePersist($model, $data);

        try {
            $this->fillModel($model, $data);

            $status = $model->save();
        } catch (Throwable $exception) {
            // Preserve the cause for logging without exposing persistence
            // internals (such as SQL or bound values) to API consumers.
            throw new UpdateModelException(previous: $exception);
        }

        if ($status === false) {
            throw new UpdateModelException();
        }

        $this->afterPersist($model, $data);

        return $this->afterExecute($model, $data);
    }
}
