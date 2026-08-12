<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
use Hatchyu\Steward\Queries\Parsers\Concerns\NormalizesQueryParams;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Contracts\Validation\ValidationRule;

final class FilterQueryRule implements ValidationRule
{
    use NormalizesQueryParams;

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

        $normalized = $this->normalizeFilterValues($value);

        foreach ($normalized as $key => $filterValue) {
            if (! is_string($key) || ! QuerySyntax::isValidDotIdentifier((string) $key)) {
                $fail(sprintf('Invalid filter key: %s.', (string) $key));

                continue;
            }

            if ($this->isScalarOrNull($filterValue)) {
                continue;
            }

            if (is_array($filterValue) && $this->isScalarList($filterValue)) {
                continue;
            }

            $fail(sprintf(
                'Invalid filter value for %s. Expected scalar, null, or list of scalars.',
                $key
            ));
        }
    }
}
