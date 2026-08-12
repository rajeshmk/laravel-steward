<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
use Hatchyu\Steward\Queries\Support\QueryParamLimits;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class IncludeQueryRule implements ValidationRule
{
    private QueryParamLimits $limits;

    /**
     * @param list<string> $allowedIncludes
     */
    public function __construct(
        private array $allowedIncludes = [],
        ?QueryParamLimits $limits = null,
    ) {
        $this->limits = $limits ?? QueryParamLimits::fromConfig();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value)) {
            if (mb_strlen($value) > $this->limits->maxValueLength) {
                $fail(sprintf('The include parameter value exceeds the maximum allowed length of %d characters.', $this->limits->maxValueLength));

                return;
            }

            if (QuerySyntax::isJsonPayloadString($value)) {
                $trimmed = trim($value);
                if (! json_validate($trimmed)) {
                    $fail('The include parameter contains an invalid JSON string.');

                    return;
                }

                $value = json_decode($trimmed, true);
            }
        }

        if (! is_string($value) && ! is_array($value)) {
            $fail('The include must be a comma-separated string or array.');

            return;
        }

        $includes = QuerySyntax::parseStringList($value);
        if ($includes === null) {
            $fail('The include parameter contains invalid element types.');

            return;
        }

        if (count($includes) > $this->limits->maxIncludes) {
            $fail(sprintf('At most %d include paths are allowed.', $this->limits->maxIncludes));

            return;
        }

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
