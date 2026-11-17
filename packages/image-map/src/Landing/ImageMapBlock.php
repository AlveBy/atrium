<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Landing;

use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\TextField;
use Atrium\ImageMap\Fields\ImageMapField;
use Atrium\ImageMap\View\Components\Map;
use Illuminate\Support\Facades\Blade;

/**
 * The map as a block of a landing page. The landing builder collects its
 * blocks through the `atrium.landing.blocks` filter; a block says its key
 * and name, the fields of its form and how it is drawn from what was saved.
 */
final class ImageMapBlock
{
    public const KEY = 'image-map';

    public static function key(): string
    {
        return self::KEY;
    }

    public static function label(): string
    {
        return __('atrium-image-map::image-map.block.label');
    }

    public static function description(): string
    {
        return __('atrium-image-map::image-map.block.description');
    }

    /**
     * @return array<int,\Atrium\Admin\Fields\Field>
     */
    public static function fields(): array
    {
        return [
            TextField::make('heading')->label(__('atrium-image-map::image-map.block.heading')),
            ImageMapField::make('map')->required(),
            CheckboxField::make('search')->label(__('atrium-image-map::image-map.fields.show_search'))->default(true),
            CheckboxField::make('legend')->label(__('atrium-image-map::image-map.fields.show_legend'))->default(true),
            CheckboxField::make('list')->label(__('atrium-image-map::image-map.fields.show_list'))->default(true),
        ];
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function render(array $data): string
    {
        $heading = trim((string) ($data['heading'] ?? ''));
        $map = Map::find(is_numeric($data['map'] ?? null) ? (int) $data['map'] : null);

        // The map was deleted or switched off: the block leaves no empty heading.
        if ($map === null) {
            return '';
        }

        return Blade::render(
            '@if ($heading !== \'\')<h2 class="aim-block-heading">{{ $heading }}</h2>@endif'
            .'<x-atrium-image-map::map :map="$map" :search="$search" :legend="$legend" :list="$list" :heading="$heading !== \'\' ? \'h3\' : \'h2\'" />',
            [
                'heading' => $heading,
                'map' => $map,
                'search' => self::flag($data, 'search'),
                'legend' => self::flag($data, 'legend'),
                'list' => self::flag($data, 'list'),
            ],
        );
    }

    /**
     * @param  array<string,mixed>  $data
     */
    private static function flag(array $data, string $key): bool
    {
        return ! array_key_exists($key, $data) || filter_var($data[$key], FILTER_VALIDATE_BOOLEAN);
    }
}
