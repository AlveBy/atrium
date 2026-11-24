<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC\Tests;

use Atrium\OneC\Models\Link;
use Atrium\OneC\Models\Run;
use Inertia\Testing\AssertableInertia;

/**
 * The exchange screen: the runs, newest first, read-only.
 */
final class RunTest extends TestCase
{
    private function makeRun(string $file = 'import.xml', string $status = Run::DONE, int $products = 3): Run
    {
        return Run::query()->create(['file' => $file, 'status' => $status, 'started_at' => now(), 'products_created' => $products]);
    }

    public function test_the_list_shows_what_came_newest_first(): void
    {
        $this->makeRun('import.xml', Run::DONE, 7);
        $this->makeRun('offers.xml', Run::FAILED);

        $response = $this->actingAs($this->admin(), 'admin')->get('/admin/onec_runs')->assertOk();

        $this->assertSame(['offers.xml', 'import.xml'], collect($response->viewData('page')['props']['items']['data'])->pluck('file')->all());
    }

    public function test_a_run_opens_with_its_counts_and_its_errors(): void
    {
        $run = $this->makeRun();
        $run->forceFill(['error' => 'The picture a.png did not come.', 'errors' => 1])->save();

        $this->actingAs($this->admin(), 'admin')->get('/admin/onec_runs/'.$run->id.'/edit')->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('can.update', false)
                ->where('can.delete', true)
                ->where('item.error', 'The picture a.png did not come.')
                ->where('item.status_label', 'Done')
                ->where('item.products_created', 3));
    }

    public function test_a_run_cannot_be_made_or_changed_by_hand(): void
    {
        $admin = $this->admin();
        $run = $this->makeRun();

        $this->actingAs($admin, 'admin')->get('/admin/onec_runs/create')->assertForbidden();
        $this->actingAs($admin, 'admin')->post('/admin/onec_runs', ['file' => 'x'])->assertForbidden();
        $this->actingAs($admin, 'admin')->put('/admin/onec_runs/'.$run->id, ['file' => 'x'])->assertForbidden();
        $this->assertSame('import.xml', $run->fresh()->file);
    }

    public function test_a_run_can_be_deleted(): void
    {
        $run = $this->makeRun();

        $this->actingAs($this->admin(), 'admin')->delete('/admin/onec_runs/'.$run->id)->assertRedirect();

        $this->assertSame(0, Run::query()->count());
    }

    public function test_the_list_can_be_narrowed_by_result(): void
    {
        $this->makeRun('good.xml');
        $this->makeRun('bad.xml', Run::FAILED);

        $response = $this->actingAs($this->admin(), 'admin')->get('/admin/onec_runs?status=failed')->assertOk();

        $this->assertSame(['bad.xml'], collect($response->viewData('page')['props']['items']['data'])->pluck('file')->all());
    }

    public function test_every_status_has_a_word(): void
    {
        foreach ([Run::RUNNING => 'In progress', Run::DONE => 'Done', Run::FAILED => 'Failed'] as $status => $word) {
            $this->assertSame($word, $this->makeRun(status: $status)->status_label);
        }
    }

    public function test_a_new_run_starts_from_zero(): void
    {
        $run = Run::query()->create(['file' => 'x.xml', 'status' => Run::RUNNING, 'started_at' => now()]);

        $this->assertSame([0, 0, 0, 0, 0, 0, 0], [$run->sections_created, $run->sections_updated, $run->products_created, $run->products_updated, $run->offers_updated, $run->skipped, $run->errors]);
    }

    public function test_a_link_is_found_by_kind_and_id_and_remembered_once(): void
    {
        Link::remember(Link::PRODUCT, 'g-1', 5);
        Link::remember(Link::PRODUCT, 'g-1', 6);
        Link::remember(Link::SECTION, 'g-1', 9);

        $this->assertSame(6, Link::find1c(Link::PRODUCT, 'g-1'));
        $this->assertSame(9, Link::find1c(Link::SECTION, 'g-1'));
        $this->assertNull(Link::find1c(Link::PRODUCT, 'g-2'));
        $this->assertSame(2, Link::query()->count());
    }
}
