<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Tests\Feature;

use Atrium\ImageMap\Models\Area;
use Atrium\ImageMap\Models\ImageMap;
use Atrium\ImageMap\Models\Layer;
use Atrium\ImageMap\Models\Status;
use Atrium\ImageMap\Tests\TestCase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

final class AdminTest extends TestCase
{
    public function test_every_section_opens_in_the_admin(): void
    {
        $layer = $this->layer();
        $area = $this->area($layer, ['status_id' => $this->status()->id]);

        $this->actingAs($this->admin(), 'admin');

        foreach (['image_maps', 'image_map_layers', 'image_map_areas', 'image_map_statuses'] as $section) {
            $this->get("/admin/{$section}")->assertOk();
            $this->get("/admin/{$section}/create")->assertOk();
        }

        $this->get("/admin/image_maps/{$layer->map_id}/edit")->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Resource/Edit'));
        $this->get("/admin/image_map_layers/{$layer->id}/edit")->assertOk();
        $this->get("/admin/image_map_areas/{$area->id}/edit")->assertOk();
        $this->get('/admin/image_map_statuses/'.Status::query()->value('id').'/edit')->assertOk();
    }

    public function test_a_map_a_floor_and_an_area_are_made_through_the_forms(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/image_maps', ['name' => ['en' => 'Riverside Mall'], 'code' => 'riverside', 'active' => true])
            ->assertSessionHasNoErrors();
        $map = ImageMap::query()->sole();
        $this->assertSame('riverside', $map->code);

        $this->post('/admin/image_map_layers', ['map_id' => $map->id, 'name' => ['en' => 'Ground floor'], 'image' => $this->picture(1200, 800)])
            ->assertSessionHasNoErrors();
        $layer = Layer::query()->sole();
        $this->assertSame([1200, 800], [$layer->width, $layer->height], 'The size is read from the picture');

        $this->post('/admin/image_map_areas', [
            'layer_id' => $layer->id,
            'title' => ['en' => 'Bookshop'],
            'shape' => 'polygon',
            'geometry_json' => '{"points":[[10,10],[300,10],[300,200],[10,200]]}',
            'action' => 'modal',
            'content_type' => 'text',
            'content' => ['en' => '<p onclick="x()">Open <b>daily</b></p><script>steal()</script>'],
            'active' => true,
        ])->assertSessionHasNoErrors();

        $area = Area::query()->sole();
        $this->assertSame([[10.0, 10.0], [300.0, 10.0], [300.0, 200.0], [10.0, 200.0]], $area->geometry['points']);
        $this->assertSame('<p>Open <b>daily</b></p>', $area->getTranslation('content', 'en'));
    }

    /**
     * @return iterable<string,array{0:string,1:string}>
     */
    public static function badGeometry(): iterable
    {
        yield 'self-intersecting' => ['polygon', '{"points":[[0,0],[100,100],[100,0],[0,100]]}'];
        yield 'outside the picture' => ['rect', '{"x":950,"y":10,"width":100,"height":20}'];
        yield 'not JSON' => ['circle', 'cx=1'];
        yield 'empty' => ['point', '{}'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badGeometry')]
    public function test_a_shape_the_map_could_not_draw_is_refused_by_the_form(string $shape, string $json): void
    {
        $layer = $this->layer();

        $this->actingAs($this->admin(), 'admin')
            ->from('/admin/image_map_areas/create')
            ->post('/admin/image_map_areas', ['layer_id' => $layer->id, 'title' => ['en' => 'Bad'], 'shape' => $shape, 'geometry_json' => $json, 'action' => 'popover', 'content_type' => 'text'])
            ->assertSessionHasErrors('geometry_json');

        $this->assertSame(0, Area::query()->count());
    }

    public function test_the_model_refuses_bad_shapes_whoever_saves_them(): void
    {
        $layer = $this->layer();

        try {
            $this->area($layer, ['geometry' => ['x' => 990, 'y' => 0, 'width' => 50, 'height' => 50]]);
            $this->fail('A shape outside the picture was saved');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('1000', $e->errors()['geometry_json'][0]);
        }

        $this->expectException(ValidationException::class);
        $this->area($layer, ['shape' => 'hexagon']);
    }

    public function test_custom_html_needs_its_own_permission(): void
    {
        $layer = $this->layer();
        $html = '<section class="promo"><iframe src="https://player.vimeo.com/video/1" title="Tour"></iframe><img src="x" onerror="steal()"></section>';
        $data = ['layer_id' => $layer->id, 'title' => ['en' => 'Cinema'], 'shape' => 'rect', 'geometry_json' => '{"x":0,"y":0,"width":50,"height":50}', 'action' => 'modal', 'content_type' => 'html', 'html' => ['en' => $html]];

        $plain = $this->editor(['image_map_areas.view', 'image_map_areas.create']);
        $this->actingAs($plain, 'admin')
            ->get('/admin/image_map_areas/create')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('fields', fn ($fields) => collect($fields)->pluck('name')->doesntContain('html')));
        $this->post('/admin/image_map_areas', $data)->assertSessionHasNoErrors();
        $this->assertNull(Area::query()->sole()->getTranslation('html', 'en', false) ?: null, 'Sent anyway, it is not taken');

        $trusted = $this->editor(['image_map_areas.view', 'image_map_areas.create', 'image_map.html']);
        $this->actingAs($trusted, 'admin')
            ->get('/admin/image_map_areas/create')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('fields', fn ($fields) => collect($fields)->pluck('name')->contains('html')));
        $this->post('/admin/image_map_areas', $data)->assertSessionHasNoErrors();

        $this->assertSame(
            '<section class="promo"><iframe src="https://player.vimeo.com/video/1" title="Tour"></iframe><img src="x"></section>',
            Area::query()->latest('id')->first()->getTranslation('html', 'en'),
        );
    }

    public function test_the_role_form_offers_the_html_permission(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/roles/create')
            ->assertOk()
            ->assertSee('image_map.html');
    }

    public function test_a_status_shows_its_contrast_as_a_number(): void
    {
        $status = $this->status('free', ['color' => '#009E73']);
        $this->assertEqualsWithDelta(3.42, $status->contrastRatio(), 0.01);

        $this->actingAs($this->admin(), 'admin')
            ->get("/admin/image_map_statuses/{$status->id}/edit")
            ->assertOk()
            ->assertSee('3.42');
    }

    public function test_a_sharper_picture_keeps_the_areas_in_place(): void
    {
        $layer = $this->layer();
        $area = $this->area($layer, ['shape' => 'polygon', 'geometry' => ['points' => [[100, 60], [200, 60], [150, 120]]]]);
        $circle = $this->area($layer, ['shape' => 'circle', 'geometry' => ['cx' => 500, 'cy' => 300, 'r' => 50]]);

        $layer->update(['image' => $this->picture(2000, 1200, 'plan@2x.svg')]);

        $this->assertSame([2000, 1200], [$layer->width, $layer->height]);
        $this->assertSame([[200.0, 120.0], [400.0, 120.0], [300.0, 240.0]], $area->fresh()->geometry['points']);
        $this->assertSame(['cx' => 1000.0, 'cy' => 600.0, 'r' => 100.0], $circle->fresh()->geometry);

        // A picture whose size cannot be read changes nothing.
        $layer->update(['image' => 'https://cdn.example.org/plan.png']);
        $this->assertSame([2000, 1200], [$layer->fresh()->width, $layer->fresh()->height]);
    }

    public function test_deleting_a_map_takes_its_floors_and_areas(): void
    {
        $layer = $this->layer();
        $this->area($layer);

        $this->actingAs($this->admin(), 'admin')->delete("/admin/image_maps/{$layer->map_id}")->assertRedirect();

        $this->assertSame([0, 0, 0], [ImageMap::query()->count(), Layer::query()->count(), Area::query()->count()]);
    }

    public function test_the_map_form_lists_floors_with_links_to_the_editor(): void
    {
        $layer = $this->layer();
        $this->area($layer);

        $this->actingAs($this->admin(), 'admin')
            ->get("/admin/image_maps/{$layer->map_id}/edit")
            ->assertSee('/admin/image_map_layers/'.$layer->id.'/edit', false)
            ->assertSee('&lt;x-atrium-image-map::map map=&quot;mall&quot; /&gt;', false);

        $this->get("/admin/image_map_layers/{$layer->id}/edit")
            ->assertSee(route('admin.image-map.editor', $layer->id), false);
    }
}
