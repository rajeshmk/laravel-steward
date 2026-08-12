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
        $filters = $this->decodeJsonIfString($value, 'filter');
        if (! is_array($filters)) {
            $fail('The filter must be an array or a JSON object string.');

            return;
        }

        $normalized = $this->normalizeFilterValues($filters);

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
