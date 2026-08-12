<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class IncludeQueryRule implements ValidationRule
{
    /**
     * @param list<string> $allowedIncludes
     */
    public function __construct(
        private array $allowedIncludes = [],
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_array($value)) {
            $fail('The include must be a comma-separated string or array.');

            return;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if (str_starts_with($trimmed, '[')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    $value = $decoded;
                }
            }
        }

        $includes = is_array($value)
            ? array_values(array_filter(array_map(static fn (mixed $i): string => trim((string) $i), $value)))
            : array_values(array_filter(array_map(trim(...), explode(',', $value))));

        if ($includes !== [] && $this->allowedIncludes === []) {
            $fail('Includes are not supported for this request.');

            return;
        }

        foreach ($includes as $include) {
            if (! QuerySyntax::isValidDotIdentifier($include)) {
                $fail(sprintf('Invalid include path: %s.', $include));

                continue;
            }

            if (! in_array($include, $this->allowedIncludes, true)) {
                $fail(sprintf('Unsupported include path: %s.', $include));
            }
        }
    }
}
