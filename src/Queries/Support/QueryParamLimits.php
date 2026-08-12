<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Support;

use InvalidArgumentException;

/**
 * The single policy for request-controlled query complexity.
 */
final readonly class QueryParamLimits
{
    public function __construct(
        public int $defaultPageSize = 15,
        public int $maxPageSize = 100,
        public int $maxPageNumber = 10_000,
        public int $maxFilterFields = 20,
        public int $maxFilterValues = 100,
        public int $maxIncludes = 20,
        public int $maxFields = 100,
        public int $maxSortFields = 20,
        public int $maxValueLength = 2_048,
    ) {
        foreach ([
            'defaultPageSize' => $this->defaultPageSize,
            'maxPageSize' => $this->maxPageSize,
            'maxPageNumber' => $this->maxPageNumber,
            'maxFilterFields' => $this->maxFilterFields,
            'maxFilterValues' => $this->maxFilterValues,
            'maxIncludes' => $this->maxIncludes,
            'maxFields' => $this->maxFields,
            'maxSortFields' => $this->maxSortFields,
            'maxValueLength' => $this->maxValueLength,
        ] as $name => $value) {
            if ($value < 1) {
                throw new InvalidArgumentException(sprintf('%s must be a positive integer.', $name));
            }
        }

        if ($this->defaultPageSize > $this->maxPageSize) {
            throw new InvalidArgumentException('defaultPageSize may not be greater than maxPageSize.');
        }
    }

    public static function fromConfig(): self
    {
        return new self(
            defaultPageSize: self::positiveConfigInt('default_page_size', 15),
            maxPageSize: self::positiveConfigInt('max_page_size', 100),
            maxPageNumber: self::positiveConfigInt('max_page_number', 10_000),
            maxFilterFields: self::positiveConfigInt('max_filter_fields', 20),
            maxFilterValues: self::positiveConfigInt('max_filter_values', 100),
            maxIncludes: self::positiveConfigInt('max_includes', 20),
            maxFields: self::positiveConfigInt('max_fields', 100),
            maxSortFields: self::positiveConfigInt('max_sort_fields', 20),
            maxValueLength: self::positiveConfigInt('max_value_length', 2_048),
        );
    }

    private static function positiveConfigInt(string $key, int $default): int
    {
        $value = config(sprintf('steward.%s', $key), $default);

        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
            return (int) $value;
        }

        throw new InvalidArgumentException(sprintf('The steward.%s configuration value must be a positive integer.', $key));
    }
}
