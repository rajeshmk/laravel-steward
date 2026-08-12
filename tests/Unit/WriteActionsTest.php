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
use Illuminate\Support\Facades\DB;
use RuntimeException;

function fakeFindModelQuery(Model $model): FindModelQueryContract
{
    return new class($model) implements FindModelQueryContract
    {
        public function __construct(private Model $model) {}

        public function modelClass(): string { return $this->model::class; }
        public function byId(int|string $id, array|string $columns = ['*']): ?Model { return $this->model; }
        public function byIdOrFail(int|string $id, array|string $columns = ['*']): Model { return $this->model; }
        public function byIds(array $ids, array|string $columns = ['*']): Collection { return new Collection([$this->model]); }
        public function byIdsOrFail(array $ids, array|string $columns = ['*']): Collection { return new Collection([$this->model]); }
        public function find(int|string $id, array|string $columns = ['*']): ?Model { return $this->model; }
        public function findOrFail(int|string $id, array|string $columns = ['*']): Model { return $this->model; }
        public function findMany(array $ids, array|string $columns = ['*']): Collection { return new Collection([$this->model]); }
        public function findManyOrFail(array $ids, array|string $columns = ['*']): Collection { return new Collection([$this->model]); }
    };
}

test('UpdateModelAction executeModel validates model registration and updates attributes', function (): void {
    DB::shouldReceive('transactionLevel')->andReturn(0);
    DB::shouldReceive('transaction')->andReturnUsing(static fn (callable $cb) => $cb());

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
    DB::shouldReceive('transactionLevel')->andReturn(0);
    DB::shouldReceive('transaction')->andReturnUsing(static fn (callable $cb) => $cb());

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
    DB::shouldReceive('transactionLevel')->andReturn(0);
    DB::shouldReceive('transaction')->andReturnUsing(static fn (callable $cb) => $cb());

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
    DB::shouldReceive('transactionLevel')->andReturn(0);
    DB::shouldReceive('transaction')->andReturnUsing(static fn (callable $cb) => $cb());

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

test('UpdateModelAction rejects a different model class even when it uses the same table', function (): void {
    $expected = new class() extends Model
    {
        protected $table = 'shared_models';
    };
    $foreign = new class() extends Model
    {
        protected $table = 'shared_models';
    };
    $foreign->exists = true;
    $foreign->id = 1;

    $expectedClass = $expected::class;
    $action = new class(fakeFindModelQuery($expected), $expectedClass) extends UpdateModelAction
    {
        public function __construct(FindModelQueryContract $query, string $modelClass)
        {
            $this->model = $modelClass;
            parent::__construct($query);
        }
    };
    $data = new class('ignored') extends AbstractData
    {
        public function __construct(public string $name) {}
    };

    expect(fn (): Model => $action->executeModel($foreign, $data))
        ->toThrow(UpdateModelException::class);
});

test('DeleteModelAction rejects a model that does not match its configured model class', function (): void {
    $expected = new class() extends Model
    {
        protected $table = 'shared_models';
    };
    $foreign = new class() extends Model
    {
        protected $table = 'shared_models';
    };
    $foreign->exists = true;
    $foreign->id = 1;

    $expectedClass = $expected::class;
    $action = new class(fakeFindModelQuery($expected), $expectedClass) extends DeleteModelAction
    {
        public function __construct(FindModelQueryContract $query, string $modelClass)
        {
            $this->model = $modelClass;
            parent::__construct($query);
        }
    };

    expect(fn (): mixed => $action->executeModel($foreign))
        ->toThrow(\Hatchyu\Steward\Exceptions\DeleteModelException::class);
});

test('UpdateModelAction retains persistence errors as previous exceptions without exposing their message', function (): void {
    DB::shouldReceive('transactionLevel')->andReturn(0);
    DB::shouldReceive('transaction')->andReturnUsing(static fn (callable $callback) => $callback());

    $model = new class() extends Model
    {
        protected $table = 'write_models';

        public function save(array $options = []): bool
        {
            throw new RuntimeException('SQLSTATE secret value');
        }
    };
    $model->exists = true;
    $model->id = 1;
    $modelClass = $model::class;
    $action = new class(fakeFindModelQuery($model), $modelClass) extends UpdateModelAction
    {
        public function __construct(FindModelQueryContract $query, string $modelClass)
        {
            $this->model = $modelClass;
            parent::__construct($query);
        }
    };
    $data = new class('name') extends AbstractData
    {
        public function __construct(public string $name) {}
    };

    try {
        $action->executeModel($model, $data);
    } catch (UpdateModelException $exception) {
        expect($exception->getMessage())->toBe('Failed to update model')
            ->and($exception->getPrevious())->toBeInstanceOf(RuntimeException::class);

        return;
    }

    throw new RuntimeException('Expected UpdateModelException was not thrown.');
});

test('AbstractAction delegates unconditionally to DB::transaction callback', function (): void {
    $called = false;
    DB::shouldReceive('transaction')->once()->andReturnUsing(static function (callable $callback) use (&$called) {
        $called = true;

        return $callback();
    });

    $action = new class() extends AbstractAction {
        public function run(): bool
        {
            return $this->transaction(static fn (): bool => true);
        }
    };

    expect($action->run())->toBeTrue();
    expect($called)->toBeTrue();
});

test('AbstractAction delegates to DB::afterCommit for post-transaction callbacks', function (): void {
    $called = false;
    DB::shouldReceive('afterCommit')->once()->andReturnUsing(static function (callable $callback) use (&$called) {
        $called = true;

        $callback();
    });

    $action = new class() extends AbstractAction {
        public function runAfterCommit(callable $cb): void
        {
            $this->afterCommit($cb);
        }
    };

    $action->runAfterCommit(static function (): void {});
    expect($called)->toBeTrue();
});
