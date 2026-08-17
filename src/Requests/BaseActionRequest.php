<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Requests;

use Hatchyu\Steward\Http\JsonApiPayloadNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Override;

abstract class BaseActionRequest extends FormRequest
{
    #[Override]
    protected function prepareForValidation(): void
    {
        $normalized = JsonApiPayloadNormalizer::normalize($this->all());
        if ($normalized !== $this->all()) {
            $this->merge($normalized);
        }
    }
}
