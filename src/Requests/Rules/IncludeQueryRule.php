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
        $value = QuerySyntax::tryDecodeJson($value);

        if (! is_string($value) && ! is_array($value)) {
            $fail('The include must be a comma-separated string or array.');

            return;
        }

        $includes = QuerySyntax::parseStringList($value);

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
