<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
use Hatchyu\Steward\Queries\Support\QueryParamLimits;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class FieldsQueryRule implements ValidationRule
{
    private QueryParamLimits $limits;

    /**
     * @param array<string, list<string>> $allowedFields
     */
    public function __construct(
        private array $allowedFields = [],
        ?QueryParamLimits $limits = null,
    ) {
        $this->limits = $limits ?? QueryParamLimits::fromConfig();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value)) {
            if (mb_strlen($value) > $this->limits->maxValueLength) {
                $fail(sprintf('The fields parameter value exceeds the maximum allowed length of %d characters.', $this->limits->maxValueLength));

                return;
            }

            if (QuerySyntax::isJsonPayloadString($value)) {
                $trimmed = trim($value);
                if (! json_validate($trimmed)) {
                    $fail('The fields parameter contains an invalid JSON string.');

                    return;
                }

                $value = json_decode($trimmed, true);
            }
        }

        if (! is_array($value) || ! QuerySyntax::isAssociativeArray($value)) {
            $fail('The fields parameter must be an object keyed by resource type.');

            return;
        }

        if (count($value) > $this->limits->maxFieldsets) {
            $fail(sprintf('At most %d resource fieldsets are allowed.', $this->limits->maxFieldsets));

            return;
        }

        if ($value !== [] && $this->allowedFields === []) {
            $fail('Sparse fieldsets are not supported for this request.');

            return;
        }

        $fieldCount = 0;

        foreach ($value as $type => $fieldSet) {
            if (! is_string($type) || ! QuerySyntax::isValidResourceType((string) $type)) {
                $fail(sprintf('Invalid resource type for fields: %s.', (string) $type));

                continue;
            }

            if (! is_string($fieldSet) && ! is_array($fieldSet)) {
                $fail(sprintf('The fields set for %s must be a string or list of strings.', (string) $type));

                continue;
            }

            if (! array_key_exists($type, $this->allowedFields)) {
                $fail(sprintf('Unsupported fields type: %s.', (string) $type));

                continue;
            }

            $fields = QuerySyntax::parseStringList($fieldSet);
            if ($fields === null) {
                $fail(sprintf('The fields set for %s contains invalid element types.', (string) $type));

                continue;
            }

            $fieldCount += count($fields);
            if ($fieldCount > $this->limits->maxFields) {
                $fail(sprintf('At most %d fields are allowed.', $this->limits->maxFields));

                return;
            }

            $allowed = $this->allowedFields[(string) $type] ?? null;

            foreach ($fields as $field) {
                if (! QuerySyntax::isValidSimpleIdentifier($field)) {
                    $fail(sprintf('Invalid field name for %s: %s.', (string) $type, $field));

                    continue;
                }

                if (is_array($allowed) && ! in_array($field, $allowed, true)) {
                    $fail(sprintf('Unsupported field for %s: %s.', (string) $type, $field));
                }
            }
        }
    }
}
