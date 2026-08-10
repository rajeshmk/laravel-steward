<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Actions;

use Hatchyu\Steward\Data\AbstractData;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 * @template TData of AbstractData
 */
abstract class AbstractModelAction extends AbstractAction
{
    /**
     * @return TModel
     */
    abstract public function model(): Model;

    /**
     * @return TModel
     */
    protected function newModelInstance(): Model
    {
        return $this->model()->newInstance();
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
