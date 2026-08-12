<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Support;

use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final readonly class QueryParamLimits
{
    /**
     * @param int $defaultPageSize
     * @param int $maxPageSize
     * @param int $maxPageNumber
     * @param int $maxFilterFields
     * @param int $maxFilterValues
     * @param int $maxIncludes
     * @param int $maxFieldsets
     * @param int $maxFields
     * @param int $maxSortFields
     * @param int $maxValueLength
     */
    public function __construct(
        public int $defaultPageSize = 15,
        public int $maxPageSize = 100,
        public int $maxPageNumber = 10000,
        public int $maxFilterFields = 20,
        public int $maxFilterValues = 100,
        public int $maxIncludes = 20,
        public int $maxFieldsets = 20,
        public int $maxFields = 100,
        public int $maxSortFields = 20,
        public int $maxValueLength = 2048,
    ) {
        foreach ([
            'defaultPageSize' => $this->defaultPageSize,
            'maxPageSize' => $this->maxPageSize,
            'maxPageNumber' => $this->maxPageNumber,
            'maxFilterFields' => $this->maxFilterFields,
            'maxFilterValues' => $this->maxFilterValues,
            'maxIncludes' => $this->maxIncludes,
            'maxFieldsets' => $this->maxFieldsets,
            'maxFields' => $this->maxFields,
            'maxSortFields' => $this->maxSortFields,
            'maxValueLength' => $this->maxValueLength,
        ] as $name => $value) {
            if ($value < 1) {
                throw new InvalidArgumentException(sprintf('%s must be a positive integer.', $name));
            }
        }

        if ($this->maxPageSize < $this->defaultPageSize) {
            throw new InvalidArgumentException('maxPageSize must be >= defaultPageSize.');
        }
    }

    public static function fromConfig(): self
    {
        return new self(
            defaultPageSize: self::positiveConfigInt('default_page_size', 15),
            maxPageSize: self::positiveConfigInt('max_page_size', 100),
            maxPageNumber: self::positiveConfigInt('max_page_number', 10000),
            maxFilterFields: self::positiveConfigInt('max_filter_fields', 20),
            maxFilterValues: self::positiveConfigInt('max_filter_values', 100),
            maxIncludes: self::positiveConfigInt('max_includes', 20),
            maxFieldsets: self::positiveConfigInt('max_fieldsets', 20),
            maxFields: self::positiveConfigInt('max_fields', 100),
            maxSortFields: self::positiveConfigInt('max_sort_fields', 20),
            maxValueLength: self::positiveConfigInt('max_value_length', 2048),
        );
    }

    public function assertValueLength(string $paramName, mixed $value): void
    {
        if (is_string($value) && mb_strlen($value) > $this->maxValueLength) {
            throw ValidationException::withMessages([
                $paramName => sprintf('The %s parameter value exceeds the maximum allowed length of %d characters.', $paramName, $this->maxValueLength),
            ]);
        }
    }

    private static function positiveConfigInt(string $key, int $default): int
    {
        $val = config(sprintf('steward.%s', $key), $default);

        if (is_int($val) && $val > 0) {
            return $val;
        }

        if (is_string($val) && ctype_digit(trim($val)) && (int) trim($val) > 0) {
            return (int) trim($val);
        }

        return $default;
    }
}
