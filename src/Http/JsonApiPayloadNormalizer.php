<?php

declare(strict_types=1);

namespace Hatchyu\Steward\Http;

final class JsonApiPayloadNormalizer
{
    /**
     * Normalize request input data supporting both flat REST bodies and JSON:API payloads.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        if (! isset($data['data']) || ! is_array($data['data'])) {
            return $data;
        }

        $rawResource = $data['data'];
        $attributes = isset($rawResource['attributes']) && is_array($rawResource['attributes'])
            ? $rawResource['attributes']
            : [];

        $normalized = $attributes;

        if (array_key_exists('id', $rawResource) && ! array_key_exists('id', $normalized)) {
            $normalized['id'] = $rawResource['id'];
        }

        if (array_key_exists('type', $rawResource) && ! array_key_exists('type', $normalized)) {
            $normalized['type'] = $rawResource['type'];
        }

        if (isset($rawResource['relationships']) && is_array($rawResource['relationships'])) {
            foreach ($rawResource['relationships'] as $relationName => $relationData) {
                if (! is_array($relationData) || ! isset($relationData['data'])) {
                    continue;
                }

                $relData = $relationData['data'];

                if (is_array($relData) && array_is_list($relData)) {
                    $normalized[$relationName] = array_values(array_filter(array_map(
                        static fn ($item): mixed => is_array($item) ? ($item['id'] ?? null) : null,
                        $relData
                    )));
                } elseif (is_array($relData) && isset($relData['id'])) {
                    $normalized[$relationName . '_id'] = $relData['id'];
                    $normalized[$relationName] = $relData['id'];
                }
            }
        }

        if (isset($data['included']) && is_array($data['included'])) {
            $normalized['_included'] = $data['included'];
        }

        return array_merge($data, $normalized);
    }
}
