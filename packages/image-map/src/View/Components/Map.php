<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\View\Components;

use Atrium\ImageMap\Models\ImageMap;
use Atrium\ImageMap\Support\Assets;
use Atrium\ImageMap\Support\MapPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A map on any page:
 *
 *     <x-atrium-image-map::map map="mall" />
 *     <x-atrium-image-map::map :map="$record->image_map_id" :list="false" />
 *
 * `map` is a code, an id or the model. A map that is not there, is switched
 * off or belongs to another site draws nothing. `search`, `legend` and
 * `list` override the map's own settings.
 */
final class Map extends Component
{
    public ?ImageMap $model;

    public function __construct(
        ImageMap|int|string|null $map = null,
        public ?bool $search = null,
        public ?bool $legend = null,
        public ?bool $list = null,
        public string $heading = 'h3',
    ) {
        $this->model = self::find($map);
    }

    public static function find(ImageMap|int|string|null $map): ?ImageMap
    {
        if ($map instanceof ImageMap) {
            return $map->active ? $map : null;
        }

        if ($map === null || $map === '') {
            return null;
        }

        return ImageMap::query()
            ->where('active', true)
            ->where(fn ($query) => is_numeric($map)
                ? $query->whereKey((int) $map)->orWhere('code', (string) $map)
                : $query->where('code', $map))
            ->first();
    }

    public function shouldRender(): bool
    {
        return $this->model !== null;
    }

    public function render(): View
    {
        /** @var ImageMap $map */
        $map = $this->model;
        $data = app(MapPresenter::class)->present($map);

        return view('atrium-image-map::components.map', [
            ...$data,
            'showSearch' => ($this->search ?? $map->show_search) && $data['areaCount'] > 0,
            'showLegend' => ($this->legend ?? $map->show_legend) && $data['legend'] !== [],
            'showList' => ($this->list ?? $map->show_list) && $data['areaCount'] > 0,
            'headingTag' => in_array($this->heading, ['h2', 'h3', 'h4', 'h5', 'h6'], true) ? $this->heading : 'h3',
            'assets' => app(Assets::class)->once(),
        ]);
    }
}
