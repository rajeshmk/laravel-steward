<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
use Hatchyu\Steward\Queries\Support\QuerySyntax;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class FieldsQueryRule implements ValidationRule
{
    /**
     * @param array<string, list<string>> $allowedFields
     */
    public function __construct(
        private array $allowedFields = [],
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = QuerySyntax::tryDecodeJson($value);

        if (! is_array($value)) {
            $fail('The fields parameter must be an object keyed by resource type.');

            return;
        }

        if ($value !== [] && $this->allowedFields === []) {
            $fail('Sparse fieldsets are not supported for this request.');

            return;
        }

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

            $fields = is_array($fieldSet)
                ? array_values(array_filter(array_map(static fn (mixed $f): string => trim((string) $f), $fieldSet)))
                : array_values(array_filter(array_map(trim(...), explode(',', $fieldSet))));

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
