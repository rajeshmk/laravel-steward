<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests\Rules;

use Closure;
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
        if (! is_array($value)) {
            $fail('The fields parameter must be an object keyed by resource type.');

            return;
        }

        if ($value !== [] && $this->allowedFields === []) {
            $fail('Sparse fieldsets are not supported for this request.');

            return;
        }

        foreach ($value as $type => $fieldSet) {
            if (! is_string($type) || ! preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $type)) {
                $fail(sprintf('Invalid resource type for fields: %s.', (string) $type));

                continue;
            }

            if (! is_string($fieldSet)) {
                $fail(sprintf('The fields set for %s must be a comma-separated string.', $type));

                continue;
            }

            if (! array_key_exists($type, $this->allowedFields)) {
                $fail(sprintf('Unsupported fields type: %s.', $type));

                continue;
            }

            $fields = array_values(array_filter(array_map(trim(...), explode(',', $fieldSet))));
            $allowed = array_key_exists($type, $this->allowedFields)
                ? $this->allowedFields[$type]
                : null;

            foreach ($fields as $field) {
                if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $field)) {
                    $fail(sprintf('Invalid field name for %s: %s.', $type, $field));

                    continue;
                }

                if (is_array($allowed) && ! in_array($field, $allowed, true)) {
                    $fail(sprintf('Unsupported field for %s: %s.', $type, $field));
                }
            }
        }
    }
}
