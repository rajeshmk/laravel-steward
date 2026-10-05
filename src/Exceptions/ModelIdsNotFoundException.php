<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Exceptions;

use Hatchyu\ApiExceptions\Model\ModelNotFoundException as ApiModelNotFoundException;
use Illuminate\Database\Eloquent\Model;

class ModelIdsNotFoundException extends ApiModelNotFoundException
{
    /**
     * @var list<int|string>
     */
    private array $ids;

    /**
     * @param class-string<Model> $modelClass
     * @param list<int|string>    $ids
     */
    public function __construct(string $modelClass, array $ids)
    {
        $this->ids = $ids;
        $firstId = $this->ids[0] ?? '';

        parent::__construct($modelClass, $firstId);
    }

    /**
     * @return list<int|string>
     */
    public function getIds(): array
    {
        return $this->ids;
    }
}
