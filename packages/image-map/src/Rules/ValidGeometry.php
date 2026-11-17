<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Rules;

use Atrium\ImageMap\Geometry\Geometry;
use Atrium\ImageMap\Models\Layer;
use Atrium\ImageMap\Shape;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The geometry of an area, sent as JSON text (the admin form, the API) or
 * as an array (the editor): checked against the shape and the size of the
 * layer's picture, both read from the rest of the request.
 */
final class ValidGeometry implements DataAwareRule, ValidationRule
{
    /** @var array<string,mixed> */
    private array $data = [];

    public function __construct(private readonly ?string $shape = null, private readonly ?Layer $layer = null) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $geometry = is_string($value) ? json_decode($value, true) : $value;
        $shape = Shape::tryFrom((string) ($this->shape ?? $this->data['shape'] ?? ''));

        if ($shape === null) {
            // The shape field reports that itself.
            return;
        }

        $layer = $this->layer ?? (is_numeric($this->data['layer_id'] ?? null)
            ? Layer::query()->withoutGlobalScopes()->find((int) $this->data['layer_id'])
            : null);

        foreach (Geometry::check($shape, $geometry, $layer?->width, $layer?->height) as [$key, $replace]) {
            $fail(__('atrium-image-map::image-map.'.$key, $replace));
        }
    }
}
