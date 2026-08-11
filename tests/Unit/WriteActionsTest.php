<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

use Hatchyu\Steward\Actions\AbstractAction;
use Hatchyu\Steward\Actions\DeleteModelAction;
use Hatchyu\Steward\Actions\UpdateModelAction;
use Hatchyu\Steward\Data\AbstractData;
use Hatchyu\Steward\Exceptions\UpdateModelException;
use Hatchyu\Steward\Queries\Contracts\FindModelQueryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Hatchyu\Steward\Tests\TestCase;

test('UpdateModelAction executeModel validates model registration and updates attributes', function (): void {
    $model = new class() extends Model
    {
        protected $table = 'dummy_write_models';

        protected $guarded = [];

        public function save(array $options = []): bool
        {
            return true;
        }
    };
    $model->exists = true;
    $model->id = 1;

    $dummyDataClass = new class('Updated Name') extends AbstractData
    {
        public function __construct(public string $name) {}
    };

    $findQuery = new class($model) implements FindModelQueryContract
    {
        public function __construct(private Model $targetModel) {}

        public function modelClass(): string
        {
            return $this->targetModel::class;
        }

        public function byId(int|string $id, array|string $columns = ['*']): ?Model
        {
            return null;
        }

        public function byIdOrFail(int|string $id, array|string $columns = ['*']): Model
        {
            return $this->targetModel;
        }

        public function byIds(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }

        public function byIdsOrFail(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }

        public function find(int|string $id, array|string $columns = ['*']): ?Model
        {
            return null;
        }

        public function findOrFail(int|string $id, array|string $columns = ['*']): Model
        {
            return $this->targetModel;
        }

        public function findMany(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }

        public function findManyOrFail(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }
    };

    $action = new class($findQuery, $model) extends UpdateModelAction
    {
        public function __construct(FindModelQueryContract $query, private Model $targetModel)
        {
            parent::__construct($query);
        }

        public function model(): Model
        {
            return $this->targetModel;
        }
    };

    $result = $action->executeModel($model, $dummyDataClass);

    expect($result->name)->toBe('Updated Name');
});

test('UpdateModelAction supports protected string model property', function (): void {
    $model = new class() extends Model
    {
        protected $table = 'dummy_write_models';

        protected $guarded = [];

        public function save(array $options = []): bool
        {
            return true;
        }
    };
    $model->exists = true;
    $model->id = 1;

    $dummyDataClass = new class('Updated Name') extends AbstractData
    {
        public function __construct(public string $name) {}
    };

    $findQuery = new class($model) implements FindModelQueryContract
    {
        public function __construct(private Model $targetModel) {}

        public function modelClass(): string
        {
            return $this->targetModel::class;
        }

        public function byId(int|string $id, array|string $columns = ['*']): ?Model
        {
            return null;
        }

        public function byIdOrFail(int|string $id, array|string $columns = ['*']): Model
        {
            return $this->targetModel;
        }

        public function byIds(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }

        public function byIdsOrFail(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }

        public function find(int|string $id, array|string $columns = ['*']): ?Model
        {
            return null;
        }

        public function findOrFail(int|string $id, array|string $columns = ['*']): Model
        {
            return $this->targetModel;
        }

        public function findMany(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }

        public function findManyOrFail(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }
    };

    $modelClass = $model::class;

    $action = new class($findQuery, $modelClass) extends UpdateModelAction
    {
        public function __construct(FindModelQueryContract $query, string $targetClass)
        {
            $this->model = $targetClass;
            parent::__construct($query);
        }
    };

    $result = $action->executeModel($model, $dummyDataClass);

    expect($result->name)->toBe('Updated Name');
});

test('UpdateModelAction executeModel throws Exception for non-existent model', function (): void {
    $model = new class() extends Model
    {
        protected $table = 'dummy_write_models';

        protected $guarded = [];
    };
    $model->exists = false;

    $dummyDataClass = new class('Test') extends AbstractData
    {
        public function __construct(public string $name) {}
    };

    $findQuery = new class($model) implements FindModelQueryContract
    {
        public function __construct(private Model $targetModel) {}

        public function modelClass(): string
        {
            return $this->targetModel::class;
        }

        public function byId(int|string $id, array|string $columns = ['*']): ?Model
        {
            return null;
        }

        public function byIdOrFail(int|string $id, array|string $columns = ['*']): Model
        {
            return $this->targetModel;
        }

        public function byIds(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }

        public function byIdsOrFail(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }

        public function find(int|string $id, array|string $columns = ['*']): ?Model
        {
            return null;
        }

        public function findOrFail(int|string $id, array|string $columns = ['*']): Model
        {
            return $this->targetModel;
        }

        public function findMany(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }

        public function findManyOrFail(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }
    };

    $action = new class($findQuery, $model) extends UpdateModelAction
    {
        public function __construct(FindModelQueryContract $query, private Model $targetModel)
        {
            parent::__construct($query);
        }

        public function model(): Model
        {
            return $this->targetModel;
        }
    };

    expect(fn (): mixed => $action->executeModel($model, $dummyDataClass))
        ->toThrow(UpdateModelException::class)
    ;
});

test('DeleteModelAction executeModel executes delete', function (): void {
    $model = new class() extends Model
    {
        protected $table = 'dummy_write_models';

        public function delete(): ?bool
        {
            $this->exists = false;

            return true;
        }
    };
    $model->exists = true;
    $model->id = 1;

    $findQuery = new class($model) implements FindModelQueryContract
    {
        public function __construct(private Model $targetModel) {}

        public function modelClass(): string
        {
            return $this->targetModel::class;
        }

        public function byId(int|string $id, array|string $columns = ['*']): ?Model
        {
            return null;
        }

        public function byIdOrFail(int|string $id, array|string $columns = ['*']): Model
        {
            return $this->targetModel;
        }

        public function byIds(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }

        public function byIdsOrFail(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }

        public function find(int|string $id, array|string $columns = ['*']): ?Model
        {
            return null;
        }

        public function findOrFail(int|string $id, array|string $columns = ['*']): Model
        {
            return $this->targetModel;
        }

        public function findMany(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }

        public function findManyOrFail(array $ids, array|string $columns = ['*']): Collection
        {
            return new Collection();
        }
    };

    $action = new class($findQuery, $model) extends DeleteModelAction
    {
        public function __construct(FindModelQueryContract $query, private Model $targetModel)
        {
            parent::__construct($query);
        }

        public function model(): Model
        {
            return $this->targetModel;
        }
    };

    $action->executeModel($model);
    expect($model->exists)->toBeFalse();
});

test('AbstractAction rethrows DB transaction exception on DB error', function (): void {
    $action = new class() extends AbstractAction {
        public function run(): void
        {
            $this->transaction(function (): void {
                throw new \RuntimeException('Database constraint failure');
            });
        }
    };

    expect(fn () => $action->run())->toThrow(\RuntimeException::class, 'Database constraint failure');
});
