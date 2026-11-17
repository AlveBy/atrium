<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Database;

use Atrium\Admin\Modules\DemoSeeder;
use Atrium\Admin\Support\ContentLanguages;
use Atrium\Admin\Support\Sites;
use Atrium\ImageMap\Models\Area;
use Atrium\ImageMap\Models\ImageMap;
use Atrium\ImageMap\Models\Layer;
use Atrium\ImageMap\Models\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Two made-up maps in the site's language: the Aurora shopping centre on
 * two floors, and a cottage village of twelve lots that are free, booked
 * or sold, with prices in the country's money. The plans are drawn here,
 * from the same shapes the areas get, so the areas sit on the rooms.
 */
final class ImageMapDemoSeeder extends DemoSeeder
{
    public const WIDTH = 1200;

    public const HEIGHT = 800;

    /** A pixel of the village plan is 10 cm: a lot of 170 × 280 px is 476 m². */
    private const METRES_PER_PIXEL = 0.1;

    private DemoCountry $country;

    private string $language;

    /** @var array<string,mixed> */
    private array $t;

    public function run(): bool
    {
        if (ImageMap::query()->withoutGlobalScopes()->exists()) {
            return false;
        }

        $this->language = app(ContentLanguages::class)->default();
        $this->country = DemoCountry::for($this->language);
        $this->t = $this->country->texts();
        $site = app(Sites::class)->default()->id;

        $statuses = $this->statuses($site);
        $this->mall($site, $statuses);
        $this->village($site, $statuses);

        return true;
    }

    /**
     * Each differs by colour, pattern and outline. Contrast of the outline
     * on white: 3.42, 3.87, 6.90 and 5.19 to 1, all above the 3:1 lines need.
     *
     * @return array<string,Status>
     */
    private function statuses(?int $site): array
    {
        $styles = [
            'free' => ['#009E73', 'solid', 'solid'],
            'booked' => ['#D55E00', 'hatch', 'dashed'],
            'sold' => ['#5A5A5A', 'cross', 'thick'],
            'for_lease' => ['#0072B2', 'dots', 'dotted'],
        ];
        $statuses = [];
        $sort = 0;

        foreach ($styles as $code => [$color, $pattern, $outline]) {
            $statuses[$code] = Status::query()->create([
                'site_id' => $site,
                'name' => [$this->language => $this->t['statuses'][$code]],
                'code' => $code,
                'color' => $color,
                'pattern' => $pattern,
                'outline' => $outline,
                'sort' => $sort += 10,
            ]);
        }

        return $statuses;
    }

    /**
     * @param  array<string,Status>  $statuses
     */
    private function mall(?int $site, array $statuses): void
    {
        $map = ImageMap::query()->create([
            'site_id' => $site,
            'name' => [$this->language => $this->t['mall']],
            'description' => [$this->language => $this->t['mall_description']],
            'code' => 'mall',
            'sort' => 10,
        ]);

        $hours = (string) $this->t['hours'];
        $store = $this->store();
        $floors = [
            1 => [
                ['1.01', 'supermarket', 'rect', ['x' => 60, 'y' => 60, 'width' => 380, 'height' => 260], 'record' => $store],
                ['1.02', 'pharmacy', 'rect', ['x' => 460, 'y' => 60, 'width' => 160, 'height' => 260]],
                ['1.03', 'coffee', 'rect', ['x' => 640, 'y' => 60, 'width' => 160, 'height' => 260]],
                ['1.04', 'books', 'rect', ['x' => 820, 'y' => 60, 'width' => 160, 'height' => 260]],
                ['1.05', 'electronics', 'rect', ['x' => 1000, 'y' => 60, 'width' => 140, 'height' => 260]],
                ['1.06', 'flowers', 'rect', ['x' => 60, 'y' => 480, 'width' => 200, 'height' => 260], 'action' => 'tooltip'],
                ['1.07', 'bank', 'rect', ['x' => 280, 'y' => 480, 'width' => 200, 'height' => 260]],
                ['1.08', 'fashion', 'polygon', ['points' => [[720, 480], [1140, 480], [1140, 740], [900, 740], [900, 620], [720, 620]]]],
                ['1.09', 'unit', 'rect', ['x' => 720, 'y' => 640, 'width' => 160, 'height' => 100], 'status' => 'for_lease'],
                [null, 'info', 'point', ['x' => 600, 'y' => 400], 'action' => 'tooltip', 'text' => 'info_text'],
                [null, 'toilets', 'point', ['x' => 1100, 'y' => 400], 'action' => 'tooltip', 'hours' => false],
                [null, 'lift', 'point', ['x' => 480, 'y' => 400], 'action' => 'tooltip', 'hours' => false],
            ],
            2 => [
                ['2.01', 'food_court', 'polygon', ['points' => [[60, 60], [600, 60], [600, 320], [330, 320], [60, 240]]], 'action' => 'modal', 'text' => 'food_court_text'],
                ['2.02', 'cinema', 'polygon', ['points' => [[620, 60], [1080, 60], [1140, 120], [1140, 320], [620, 320]]], 'action' => 'modal', 'html' => 'cinema_html'],
                ['2.03', 'kids', 'circle', ['cx' => 200, 'cy' => 610, 'r' => 120], 'text' => 'kids_text'],
                ['2.04', 'sports', 'rect', ['x' => 360, 'y' => 480, 'width' => 220, 'height' => 260]],
                ['2.05', 'shoes', 'rect', ['x' => 600, 'y' => 480, 'width' => 180, 'height' => 260]],
                ['2.06', 'beauty', 'rect', ['x' => 800, 'y' => 480, 'width' => 160, 'height' => 260]],
                ['2.07', 'unit', 'rect', ['x' => 980, 'y' => 480, 'width' => 160, 'height' => 260], 'status' => 'for_lease'],
                [null, 'toilets', 'point', ['x' => 1100, 'y' => 400], 'action' => 'tooltip', 'hours' => false],
                [null, 'lift', 'point', ['x' => 480, 'y' => 400], 'action' => 'tooltip', 'hours' => false],
            ],
        ];

        foreach ($floors as $n => $areas) {
            $file = $this->path('image-map-mall-'.$n.'.svg');
            $this->put($file, $this->mallPlan($areas, $n));

            $layer = Layer::query()->create([
                'map_id' => $map->id,
                'name' => [$this->language => str_replace(':n', (string) $n, $this->t['floor'])],
                'image' => $file,
                'sort' => $n * 10,
            ]);

            $sort = 0;

            foreach ($areas as $area) {
                [$unit, $place, $shape, $geometry] = $area;
                $lease = ($area['status'] ?? null) === 'for_lease';
                $title = $place === 'unit'
                    ? str_replace(':n', (string) $unit, $this->t['places']['unit'])
                    : ($unit === null ? '' : $unit.' ').$this->t['places'][$place];
                $description = match (true) {
                    $lease => str_replace(':area', $this->country->area($this->size($shape, $geometry) * 0.0025), $this->t['lease_text']),
                    isset($area['text']) && ($area['action'] ?? '') === 'tooltip' => $this->t[$area['text']],
                    ($area['hours'] ?? true) === false => '',
                    default => $hours,
                };

                $this->area($layer, $site, $sort += 10, $shape, $geometry, $title, $description, [
                    'status_id' => isset($area['status']) ? $statuses[$area['status']]->id : null,
                    'action' => $area['action'] ?? 'popover',
                    'content_type' => isset($area['html']) ? 'html' : (($area['record'] ?? null) ? 'record' : 'text'),
                    'content' => isset($area['text']) && ($area['action'] ?? '') !== 'tooltip' ? [$this->language => $this->t[$area['text']]] : null,
                    'html' => isset($area['html']) ? [$this->language => $this->t[$area['html']]] : null,
                    'linkable_type' => ($area['record'] ?? null)?->getMorphClass(),
                    'linkable_id' => ($area['record'] ?? null)?->getKey(),
                ]);
            }
        }
    }

    /**
     * @param  array<string,Status>  $statuses
     */
    private function village(?int $site, array $statuses): void
    {
        $map = ImageMap::query()->create([
            'site_id' => $site,
            'name' => [$this->language => $this->t['village']],
            'description' => [$this->language => $this->t['village_description']],
            'code' => 'village',
            'show_search' => false,
            'sort' => 20,
        ]);

        $state = [1 => 'free', 'free', 'booked', 'sold', 'free', 'sold', 'booked', 'free', 'free', 'sold', 'free', 'booked'];
        $lots = [];

        foreach (range(0, 5) as $i) {
            $x = 60 + $i * 180;
            // The last lots follow the creek: their sides bend.
            $lots[$i + 1] = $i === 5
                ? ['polygon', ['points' => [[$x, 60], [1140, 60], [1140, 340], [$x, 340], [$x + 25, 200]]]]
                : ['rect', ['x' => $x, 'y' => 60, 'width' => 170, 'height' => 280]];
            $lots[$i + 7] = $i === 5
                ? ['polygon', ['points' => [[$x, 460], [1140, 460], [1140, 690], [1080, 740], [$x, 740]]]]
                : ['rect', ['x' => $x, 'y' => 460, 'width' => 170, 'height' => 280]];
        }
        ksort($lots);

        $file = $this->path('image-map-village.svg');
        $this->put($file, $this->villagePlan($lots));

        $layer = Layer::query()->create([
            'map_id' => $map->id,
            'name' => [$this->language => $this->t['site_plan']],
            'image' => $file,
            'sort' => 10,
        ]);

        $sort = 0;

        foreach ($lots as $n => [$shape, $geometry]) {
            $metres = $this->size($shape, $geometry) * self::METRES_PER_PIXEL ** 2;
            $description = strtr($this->t['lot_text'], [
                ':area' => $this->country->area($metres),
                ':price' => $state[$n] === 'sold' ? $this->t['statuses']['sold'] : $this->country->lotPrice($metres),
            ]);

            $this->area($layer, $site, $sort += 10, $shape, $geometry, str_replace(':n', (string) $n, $this->t['places']['lot']), $description, [
                'status_id' => $statuses[$state[$n]]->id,
                'action' => 'popover',
            ]);
        }

        $this->area($layer, $site, $sort += 10, 'circle', ['cx' => 600, 'cy' => 400, 'r' => 30], $this->t['places']['pond'], '', ['action' => 'tooltip']);
    }

    /**
     * @param  array<string,mixed>  $geometry
     * @param  array<string,mixed>  $extra
     */
    private function area(Layer $layer, ?int $site, int $sort, string $shape, array $geometry, string $title, string $description, array $extra): Area
    {
        return Area::query()->create([
            'site_id' => $site,
            'layer_id' => $layer->id,
            'shape' => $shape,
            'geometry' => $geometry,
            'title' => [$this->language => $title],
            'description' => $description === '' ? null : [$this->language => $description],
            'sort' => $sort,
            ...$extra,
        ]);
    }

    /**
     * The first store of the Stores module, when it is installed: the
     * supermarket shows its card.
     */
    private function store(): ?Model
    {
        $class = 'Atrium\\Stores\\Models\\Store';

        if (! class_exists($class) || ! Schema::hasTable((new $class)->getTable())) {
            return null;
        }

        return $class::query()->withoutGlobalScopes()->orderBy('sort')->orderBy('id')->first();
    }

    /**
     * The area of a shape in square pixels of the plan.
     *
     * @param  array<string,mixed>  $g
     */
    private function size(string $shape, array $g): float
    {
        return match ($shape) {
            'rect' => $g['width'] * $g['height'],
            'circle' => M_PI * $g['r'] ** 2,
            'polygon' => abs(array_sum(array_map(
                static fn (array $p, array $q): float => $p[0] * $q[1] - $q[0] * $p[1],
                $g['points'],
                [...array_slice($g['points'], 1), $g['points'][0]],
            ))) / 2,
            default => 0.0,
        };
    }

    private function put(string $path, string $svg): void
    {
        Storage::disk((string) config('atrium.uploads.disk', 'public'))->put($path, $svg);
    }

    /**
     * A floor of the mall: walls, a corridor, the units with their numbers.
     *
     * @param  array<int,array<int|string,mixed>>  $areas
     */
    private function mallPlan(array $areas, int $floor): string
    {
        $rooms = '';

        foreach ($areas as $area) {
            [$unit, , $shape, $g] = $area;

            if ($shape === 'point') {
                continue;
            }

            $rooms .= $this->svgShape($shape, $g, 'fill="#ffffff" stroke="#8a8a8a" stroke-width="2"');

            if ($unit !== null) {
                [$cx, $cy] = $this->labelPoint($shape, $g);
                $rooms .= sprintf('<text x="%s" y="%s" font-size="22" text-anchor="middle" fill="#4d4d4d" font-family="sans-serif">%s</text>', $cx, $cy + 8, $unit);
            }
        }

        $entrance = $floor === 1
            ? '<path d="M560 790 L600 752 L640 790" fill="none" stroke="#4d4d4d" stroke-width="4"/>'
            : '';

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.self::WIDTH.'" height="'.self::HEIGHT.'" viewBox="0 0 '.self::WIDTH.' '.self::HEIGHT.'">'
            .'<rect width="1200" height="800" fill="#f2f0eb"/>'
            .'<rect x="40" y="40" width="1120" height="720" fill="#e6e3dc" stroke="#4d4d4d" stroke-width="6"/>'
            .'<rect x="40" y="340" width="1120" height="120" fill="#f7f6f2"/>'
            .($floor === 1 ? '<rect x="500" y="752" width="200" height="16" fill="#f2f0eb"/>' : '')
            .$rooms.$entrance
            .'<circle cx="480" cy="400" r="18" fill="none" stroke="#8a8a8a" stroke-width="2"/>'
            .'</svg>';
    }

    /**
     * The village: a road, the lots with their numbers, trees and a pond.
     *
     * @param  array<int,array{0:string,1:array<string,mixed>}>  $lots
     */
    private function villagePlan(array $lots): string
    {
        $shapes = '';

        foreach ($lots as $n => [$shape, $g]) {
            $shapes .= $this->svgShape($shape, $g, 'fill="#fbfbf7" stroke="#8a8a8a" stroke-width="2"');
            [$cx, $cy] = $this->labelPoint($shape, $g);
            $shapes .= sprintf('<text x="%s" y="%s" font-size="26" text-anchor="middle" fill="#4d4d4d" font-family="sans-serif">%d</text>', $cx, $cy + 9, $n);
        }

        $trees = '';

        foreach ([[30, 30], [30, 400], [30, 770], [1170, 30], [1170, 770], [600, 30], [600, 770], [420, 400], [780, 400]] as [$x, $y]) {
            $trees .= sprintf('<circle cx="%d" cy="%d" r="14" fill="#a9c79a"/>', $x, $y);
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.self::WIDTH.'" height="'.self::HEIGHT.'" viewBox="0 0 '.self::WIDTH.' '.self::HEIGHT.'">'
            .'<rect width="1200" height="800" fill="#e8efdf"/>'
            .'<rect x="0" y="360" width="1200" height="80" fill="#d6d3cc"/>'
            .'<path d="M0 400 H1200" stroke="#ffffff" stroke-width="3" stroke-dasharray="24 18"/>'
            .'<circle cx="600" cy="400" r="30" fill="#cfe3f0" stroke="#8a8a8a" stroke-width="2"/>'
            .$shapes.$trees
            .'</svg>';
    }

    /**
     * @param  array<string,mixed>  $g
     */
    private function svgShape(string $shape, array $g, string $style): string
    {
        return match ($shape) {
            'rect' => sprintf('<rect x="%s" y="%s" width="%s" height="%s" %s/>', $g['x'], $g['y'], $g['width'], $g['height'], $style),
            'circle' => sprintf('<circle cx="%s" cy="%s" r="%s" %s/>', $g['cx'], $g['cy'], $g['r'], $style),
            default => sprintf('<polygon points="%s" %s/>', implode(' ', array_map(static fn (array $p): string => $p[0].','.$p[1], $g['points'])), $style),
        };
    }

    /**
     * @param  array<string,mixed>  $g
     * @return array{0:float,1:float}
     */
    private function labelPoint(string $shape, array $g): array
    {
        return match ($shape) {
            'rect' => [$g['x'] + $g['width'] / 2, $g['y'] + $g['height'] / 2],
            'circle' => [$g['cx'], $g['cy']],
            default => [
                (min(array_column($g['points'], 0)) + max(array_column($g['points'], 0))) / 2,
                (min(array_column($g['points'], 1)) + max(array_column($g['points'], 1))) / 2,
            ],
        };
    }
}
