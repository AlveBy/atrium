<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Support;

use Atrium\ImageMap\Action;
use Atrium\ImageMap\ContentType;
use Atrium\ImageMap\Geometry\Geometry;
use Atrium\ImageMap\Models\Area;
use Atrium\ImageMap\Models\ImageMap;
use Atrium\ImageMap\Models\Layer;
use Atrium\ImageMap\Models\Status;
use Atrium\ImageMap\Pattern;
use Atrium\ImageMap\Shape;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * What the map component draws, worked out once: the layers with their
 * areas as SVG shapes, the text each area opens, the legend and the words
 * the search box looks in. Everything is in the page, so the map works
 * (as links and a list) before and without the script.
 */
final class MapPresenter
{
    /** Stroke colour of an area without a status: Okabe–Ito blue, 5.19:1 on white. */
    public const DEFAULT_COLOR = '#0072b2';

    /**
     * @return array<string,mixed>
     */
    public function present(ImageMap $map): array
    {
        $locale = app()->getLocale();
        $id = 'aim-'.$map->id;

        $layers = $map->layers()
            ->with(['areas' => fn ($query) => $query->where('active', true)->with(['status', 'linkable'])])
            ->get()
            ->filter(fn (Layer $layer): bool => $layer->imageUrl() !== null && $layer->width > 0 && $layer->height > 0)
            ->values();

        $statuses = $layers->flatMap(fn (Layer $layer) => $layer->areas->pluck('status'))->filter()->unique('id')->sortBy('sort')->values();

        $presentedLayers = $layers->map(function (Layer $layer, int $index) use ($id, $locale): array {
            $name = (string) $layer->getTranslation('name', $locale);
            $areas = $layer->areas
                ->map(fn (Area $area): array => $this->area($area, $id, $locale, (int) $layer->width))
                ->sortBy([['sort', 'asc'], ['cy', 'asc'], ['cx', 'asc']])
                ->values();

            return [
                'id' => $id.'-l'.$layer->id,
                'index' => $index,
                'name' => $name,
                'image' => $layer->imageUrl(),
                'width' => (int) $layer->width,
                'height' => (int) $layer->height,
                'areas' => $areas->all(),
            ];
        });

        return [
            'id' => $id,
            'map' => $map,
            'name' => (string) $map->getTranslation('name', $locale),
            'description' => (string) $map->getTranslation('description', $locale),
            'layers' => $presentedLayers->all(),
            'legend' => $statuses->map(fn (Status $status): array => $this->legendItem($status, $layers))->all(),
            'statuses' => $statuses->map(fn (Status $status): array => $this->statusStyle($status))->keyBy('id')->all(),
            'areaCount' => $presentedLayers->sum(fn (array $layer): int => count($layer['areas'])),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function area(Area $area, string $mapId, string $locale, int $layerWidth): array
    {
        $shape = $area->shapeCase();
        $geometry = $area->geometry ?? [];
        [$cx, $cy] = Geometry::center($shape, $geometry);

        $title = trim((string) $area->getTranslation('title', $locale));
        $description = trim((string) $area->getTranslation('description', $locale));
        $card = $area->contentTypeCase() === ContentType::Record ? RecordCard::for($area->linkable) : null;
        $status = $area->status;
        $statusName = $status === null ? '' : trim((string) $status->getTranslation('name', $locale));

        $url = trim((string) $area->url);

        if ($url === '' && $card !== null) {
            $url = (string) ($card['url'] ?? '');
        }

        // A link with nowhere to go (its record is gone) opens its text instead.
        $action = $area->actionCase();

        if ($action === Action::Link && $url === '') {
            $action = Action::Popover;
        }

        $html = match ($area->contentTypeCase()) {
            ContentType::Text => trim((string) $area->getTranslation('content', $locale)),
            ContentType::Html => trim((string) $area->getTranslation('html', $locale)),
            ContentType::Record => '',
        };

        $label = $statusName === '' ? $title : $title.', '.$statusName;

        return [
            'dom' => $mapId.'-a'.$area->id,
            'id' => $area->id,
            'sort' => $area->sort,
            'cx' => round($cx, 1),
            'cy' => round($cy, 1),
            'shape' => $shape->value,
            'svg' => $this->svgAttributes($shape, $geometry, $layerWidth),
            'action' => $action->value,
            'url' => $url,
            'external' => preg_match('#^https?://#i', $url) === 1 && ! str_starts_with($url, url('/')),
            'title' => $title === '' ? '#'.$area->id : $title,
            'label' => $label === '' ? '#'.$area->id : $label,
            'description' => $description,
            'html' => $html,
            'card' => $card,
            'status' => $status?->id,
            'statusName' => $statusName,
            'search' => Str::lower(implode(' ', array_filter([$title, $description, $statusName, $card['title'] ?? null, strip_tags($html)]))),
        ];
    }

    /**
     * The attributes of the SVG element for a shape. A point is a marker
     * whose size follows the picture, at least 8 px at 800 px wide.
     *
     * @param  array<string,mixed>  $geometry
     * @return array{tag:string,attributes:array<string,string>,marker:float}
     */
    private function svgAttributes(Shape $shape, array $geometry, int $layerWidth): array
    {
        $n = static fn (mixed $value): string => rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.');
        $marker = max(8.0, $layerWidth / 90);

        return match ($shape) {
            Shape::Rect => ['tag' => 'rect', 'marker' => 0.0, 'attributes' => [
                'x' => $n($geometry['x']), 'y' => $n($geometry['y']), 'width' => $n($geometry['width']), 'height' => $n($geometry['height']),
            ]],
            Shape::Circle => ['tag' => 'circle', 'marker' => 0.0, 'attributes' => [
                'cx' => $n($geometry['cx']), 'cy' => $n($geometry['cy']), 'r' => $n($geometry['r']),
            ]],
            Shape::Point => ['tag' => 'circle', 'marker' => $marker, 'attributes' => [
                'cx' => $n($geometry['x']), 'cy' => $n($geometry['y']), 'r' => $n($marker),
            ]],
            Shape::Polygon => ['tag' => 'polygon', 'marker' => 0.0, 'attributes' => [
                'points' => implode(' ', array_map(static fn (array $p): string => $n($p[0]).','.$n($p[1]), $geometry['points'] ?? [])),
            ]],
        };
    }

    /**
     * @return array<string,mixed>
     */
    private function statusStyle(Status $status): array
    {
        return [
            'id' => $status->id,
            'pattern' => $status->patternCase()->value,
            'color' => $status->hex(),
            'dash' => $status->outlineCase()->value,
        ];
    }

    /**
     * @param  Collection<int,Layer>  $layers
     * @return array<string,mixed>
     */
    private function legendItem(Status $status, Collection $layers): array
    {
        return [
            ...$this->statusStyle($status),
            'name' => (string) $status->getTranslation('name', app()->getLocale()),
            'count' => $layers->sum(fn (Layer $layer): int => $layer->areas->where('status_id', $status->id)->count()),
        ];
    }

    /**
     * The fill of an area: its status's pattern, or a light tint. Patterns
     * are drawn in each picture's own units, so every layer (and the
     * legend) has its own set, named after `$scope`.
     *
     * @param  array<string,mixed>|null  $status
     */
    public static function fill(?array $status, string $scope): string
    {
        if ($status === null) {
            return 'rgba(0,114,178,.14)';
        }

        return $status['pattern'] === Pattern::None->value ? 'transparent' : 'url(#'.$scope.'-p'.$status['id'].')';
    }
}
