<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Params;

final readonly class QueryParams
{
    /**
     * @param array<string, mixed>        $filters
     * @param list<SortField>             $sort
     * @param list<string>                $includes
     * @param array<string, list<string>> $fields
     */
    public function __construct(
        public ?string $search = null,
        public array $filters = [],
        public array $sort = [],
        public array $includes = [],
        public array $fields = [],
        public int $page = 1,
        public int $size = 15,
        public ?string $cursor = null,
    ) {}

    public function offset(): int
    {
        return ($this->page - 1) * $this->size;
    }
}
