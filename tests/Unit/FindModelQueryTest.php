<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Tests\Unit;

use Hatchyu\Steward\Exceptions\ModelIdsNotFoundException;
use Hatchyu\Steward\Queries\FindModelQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

test('byIdsOrFail throws ModelIdsNotFoundException when any requested ID is missing', function (): void {
    $dummyModel = new class() extends Model
    {
        protected $table = 'customers';
    };

    $foundModel = new class() extends Model
    {
        protected $keyType = 'int';

        public function getKey()
        {
            return 1;
        }
    };

    $query = new class($dummyModel) extends FindModelQuery
    {
        public Collection $mockCollection;

        public function byIds(array $ids, array|string $columns = ['*']): Collection
        {
            return $this->mockCollection;
        }
    };

    $query->mockCollection = new Collection([$foundModel]);

    expect(fn (): mixed => $query->byIdsOrFail([1, 2]))
        ->toThrow(ModelIdsNotFoundException::class)
    ;
});

test('normalizeId throws InvalidArgumentException on empty string', function (): void {
    $dummyModel = new class() extends Model
    {
        protected $table = 'customers';
    };

    $query = new class($dummyModel) extends FindModelQuery {};

    expect(fn (): mixed => $query->byId(''))
        ->toThrow(InvalidArgumentException::class)
    ;
});

test('find aliases delegate correctly to byId and byIds', function (): void {
    $dummyModel = new class() extends Model
    {
        protected $table = 'customers';
    };

    $foundModel = new class() extends Model
    {
        public function getKey()
        {
            return 10;
        }
    };

    $query = new class($dummyModel) extends FindModelQuery
    {
        public ?Model $mockModel = null;

        public Collection $mockCollection;

        public function byId(int|string $id, array|string $columns = ['*']): ?Model
        {
            return $this->mockModel;
        }

        public function byIds(array $ids, array|string $columns = ['*']): Collection
        {
            return $this->mockCollection;
        }
    };

    $query->mockModel = $foundModel;
    $query->mockCollection = new Collection([$foundModel]);

    expect($query->find(10))->toBe($foundModel);
    expect($query->findMany([10]))->toHaveCount(1);
});
