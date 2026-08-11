<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Actions;

use Hatchyu\Steward\Data\AbstractData;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * @template TModel of Model
 * @template TData of AbstractData
 */
abstract class AbstractModelAction extends AbstractAction
{
    /**
     * The Eloquent model class string.
     *
     * @var class-string<TModel>|null
     */
    protected ?string $model = null;

    /**
     * @return TModel
     */
    public function model(): Model
    {
        if ($this->model !== null) {
            return new $this->model();
        }

        throw new LogicException(sprintf(
            'Action [%s] must define protected string $model or override the model() method.',
            static::class
        ));
    }

    /**
     * @return TModel
     */
    protected function newModelInstance(): Model
    {
        return $this->model()->newInstance();
    }

    /**
     * Determine whether an existing model is safe for this action to persist.
     *
     * Accepting a model merely because it uses the same table is unsafe: two
     * Eloquent model classes can intentionally share a table while having
     * different casts, guards, observers, or authorization semantics.
     *
     * @param TModel $model
     */
    protected function isExpectedPersistedModel(Model $model): bool
    {
        $expectedModelClass = $this->model()::class;

        return $model instanceof $expectedModelClass
            && $model->exists
            && $model->getKey() !== null;
    }

    /**
     * @param TModel $model
     * @param TData $data
     */
    protected function fillModel(Model $model, AbstractData $data): void
    {
        $model->fill($data->toModelAttributes());
    }
}
