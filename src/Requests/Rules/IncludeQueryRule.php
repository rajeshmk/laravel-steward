<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
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
        if (! is_string($value)) {
            $fail('The include must be a comma-separated string.');

            return;
        }

        $includes = array_values(array_filter(array_map(trim(...), explode(',', $value))));

        if ($includes !== [] && $this->allowedIncludes === []) {
            $fail('Includes are not supported for this request.');

            return;
        }

        foreach ($includes as $include) {
            if (! preg_match('/^[A-Za-z_][A-Za-z0-9_\.]*$/', $include)) {
                $fail(sprintf('Invalid include path: %s.', $include));

                continue;
            }

            if (! in_array($include, $this->allowedIncludes, true)) {
                $fail(sprintf('Unsupported include path: %s.', $include));
            }
        }
    }
}
