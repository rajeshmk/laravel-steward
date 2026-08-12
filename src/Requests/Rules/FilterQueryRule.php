<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
use Hatchyu\Steward\Queries\Parsers\Concerns\NormalizesQueryParams;
use Hatchyu\Steward\Queries\Support\QueryParamLimits;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Contracts\Validation\ValidationRule;

final class FilterQueryRule implements ValidationRule
{
    use NormalizesQueryParams;

    public function __construct(private QueryParamLimits $limits = new QueryParamLimits()) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && QuerySyntax::isJsonPayloadString($value)) {
            $trimmed = trim($value);
            if (! json_validate($trimmed)) {
                $fail('The filter parameter contains an invalid JSON string.');

                return;
            }

            $value = json_decode($trimmed, true);
        }

        if (! is_array($value) || ! QuerySyntax::isAssociativeArray($value)) {
            $fail('The filter must be an object or associative array.');

            return;
        }

        $filterCount = count($value) - (array_key_exists('search', $value) ? 1 : 0);
        if ($filterCount > $this->limits->maxFilterFields) {
            $fail(sprintf('At most %d filter fields are allowed.', $this->limits->maxFilterFields));

            return;
        }

        $normalized = $this->normalizeFilterValues($value);

        if (array_key_exists('search', $normalized) && ! is_string($normalized['search']) && $normalized['search'] !== null) {
            $fail('The filter search value must be a string.');

            return;
        }

        foreach ($normalized as $key => $filterValue) {
            if (! QuerySyntax::isValidDotIdentifier($key)) {
                $fail(sprintf('Invalid filter key: %s.', (string) $key));

                continue;
            }

            if ($this->isScalarOrNull($filterValue)) {
                continue;
            }

            if (is_array($filterValue) && $this->isScalarList($filterValue)) {
                if (count($filterValue) > $this->limits->maxFilterValues) {
                    $fail(sprintf('At most %d values are allowed for %s.', $this->limits->maxFilterValues, $key));
                }

                continue;
            }

            $fail(sprintf(
                'Invalid filter value for %s. Expected scalar, null, or list of scalars.',
                $key
            ));
        }
    }
}
