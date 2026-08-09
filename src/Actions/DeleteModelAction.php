<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Actions;

use Hatchyu\Steward\Data\AbstractData;
use Hatchyu\Steward\Exceptions\DeleteModelException;
use Hatchyu\Steward\Queries\Contracts\FindModelQueryContract;
use Illuminate\Database\Eloquent\Model;

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

    final public function executeModel(Model $model): void
    {
        $this->transaction(function () use ($model): void {
            $deleted = $model->delete();

            if (! $deleted) {
                throw new DeleteModelException();
            }
        });
    }

    /**
     * DeleteModelAction does not accept request data.
     *
     * @return list<class-string<AbstractData>>
     */
    protected function expectedDataClasses(): array
    {
        return [];
    }
}
