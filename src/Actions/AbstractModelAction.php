<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Actions;

use Hatchyu\Steward\Data\AbstractData;
use Hatchyu\Steward\Exceptions\DataTypeMismatchException;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractModelAction extends AbstractAction
{
    abstract public function model(): Model;

    /**
     * @return list<class-string<AbstractData>>
     */
    abstract protected function expectedDataClasses(): array;

    protected function newModelInstance(): Model
    {
        return $this->model()->newInstance();
    }

    protected function fillModel(Model $model, AbstractData $data): void
    {
        $model->fill($data->toModelAttributes());
    }

    protected function ensureExpectedDataClass(AbstractData $data): void
    {
        $expectedDataClasses = $this->expectedDataClasses();

        foreach ($expectedDataClasses as $expectedDataClass) {
            if ($data instanceof $expectedDataClass) {
                return;
            }
        }

        throw DataTypeMismatchException::forExpectedList($expectedDataClasses, $data);
    }
}
