<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Http;

enum ApiResponseFormat: string
{
    case REST = 'rest';
    case JSON_API = 'jsonapi';
}
