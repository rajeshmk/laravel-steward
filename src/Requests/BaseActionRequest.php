<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Override;

abstract class BaseActionRequest extends FormRequest
{
    #[Override]
    protected function prepareForValidation(): void
    {
        if (! $this->isJsonApiRequest()) {
            return;
        }

        $payload = $this->jsonApiBodyPayload();
        if ($payload === []) {
            return;
        }

        $this->merge($payload);
    }

    protected function isJsonApiRequest(): bool
    {
        $accept = strtolower((string) $this->header('Accept', ''));
        $contentType = strtolower((string) $this->header('Content-Type', ''));

        if (Str::contains($accept, 'application/vnd.api+json') || Str::contains($contentType, 'application/vnd.api+json')) {
            return true;
        }

        return is_array($this->input('data'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function jsonApiBodyPayload(): array
    {
        $data = $this->input('data');
        if (! is_array($data)) {
            return [];
        }

        /** @var mixed $attributes */
        $attributes = $data['attributes'] ?? [];
        if (! is_array($attributes)) {
            $attributes = [];
        }

        $payload = $attributes;

        if (array_key_exists('id', $data) && ! array_key_exists('id', $payload)) {
            $payload['id'] = $data['id'];
        }

        if (array_key_exists('type', $data) && ! array_key_exists('type', $payload)) {
            $payload['type'] = $data['type'];
        }

        return $payload;
    }
}
