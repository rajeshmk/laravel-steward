<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Queries\Criteria\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class SimpleFilter extends AbstractFilter
{
    /** @param Builder<Model> $query @return Builder<Model> */
    public function apply(Builder $query, mixed $value): Builder
    {
        if (is_string($value) && str_contains($value, ',')) {
            $value = array_values(array_filter(
                array_map(trim(...), explode(',', $value)),
                static fn (string $v): bool => $v !== ''
            ));
        }

        $values = is_array($value) ? $value : [$value];
        $count = count($values);

        if ($count === 0) {
            return $query;
        }

        if ($count === 1) {
            $value = $values[0];

            if ($value === null) {
                $query->whereNull($this->field);

                return $query;
            }

            $query->where($this->field, $value);

            return $query;
        }

        $hasNull = in_array(null, $values, true);
        $nonNullValues = array_values(array_filter($values, static fn (mixed $v): bool => $v !== null));

        if ($hasNull) {
            $query->where(function (Builder $q) use ($nonNullValues): void {
                if ($nonNullValues !== []) {
                    $q->whereIn($this->field, $nonNullValues)->orWhereNull($this->field);
                } else {
                    $q->whereNull($this->field);
                }
            });

            return $query;
        }

        $query->whereIn($this->field, $values);

        return $query;
    }
}
