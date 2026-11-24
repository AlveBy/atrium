<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Tests;

use Atrium\Integrations\Enums\Direction;
use Atrium\Integrations\Enums\Status;
use Atrium\Integrations\Exchange;
use Atrium\Integrations\Models\ExchangeLog;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;

final class AdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $exchange = app(Exchange::class);
        $exchange->record('bitrix24', 'lead.add', Status::Ok, subject: 'Form submission #12', message: 'Lead 15');
        $exchange->record('1c', 'catalog.import', Status::Failed, error: 'Cannot read import.xml', direction: Direction::In);
    }

    public function test_the_list_shows_every_line_newest_first_with_its_words(): void
    {
        $response = $this->actingAs($this->admin(), 'admin')->get('/admin/integration_logs')->assertOk();

        $response->assertSee('Cannot read import.xml')->assertSee('Form submission #12')->assertSee('Failed')->assertSee('Done');
        $ids = collect($response->viewData('page')['props']['items']['data'] ?? [])->pluck('integration')->all();
        $this->assertSame(['1c', 'bitrix24'], $ids);
    }

    public function test_it_narrows_by_result_and_by_integration(): void
    {
        $admin = $this->admin();

        $failed = $this->actingAs($admin, 'admin')->get('/admin/integration_logs?status=failed')->assertOk();
        $this->assertSame(['1c'], collect($failed->viewData('page')['props']['items']['data'])->pluck('integration')->all());

        $bitrix = $this->actingAs($admin, 'admin')->get('/admin/integration_logs?integration=bitrix24')->assertOk();
        $this->assertSame(['bitrix24'], collect($bitrix->viewData('page')['props']['items']['data'])->pluck('integration')->all());
    }

    public function test_the_integration_filter_lists_only_the_integrations_that_wrote_something(): void
    {
        $filters = collect(ExchangeLog::getAdministrationFilters())->map->toArray()->keyBy('name');

        $this->assertSame(['1c', 'bitrix24'], array_column($filters['integration']['options'], 'value'));
        $this->assertSame(['ok', 'retry', 'failed'], array_column($filters['status']['options'], 'value'));
    }

    public function test_a_line_opens_with_everything_it_knows(): void
    {
        $row = ExchangeLog::query()->where('integration', '1c')->sole();

        $this->actingAs($this->admin(), 'admin')->get('/admin/integration_logs/'.$row->id.'/edit')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('can.update', false)
                ->where('can.delete', true)
                ->where('item.error', 'Cannot read import.xml')
                ->where('item.action', 'catalog.import')
                ->where('item.direction_label', 'To the site')
                ->where('item.status_label', 'Failed'));
    }

    public function test_nothing_is_added_or_changed_by_hand(): void
    {
        $admin = $this->admin();
        $row = ExchangeLog::query()->firstOrFail();

        $this->actingAs($admin, 'admin')->get('/admin/integration_logs/create')->assertForbidden();
        $this->actingAs($admin, 'admin')->post('/admin/integration_logs', ['integration' => 'x'])->assertForbidden();
        $this->actingAs($admin, 'admin')->put('/admin/integration_logs/'.$row->id, ['error' => 'x'])->assertForbidden();
        $this->assertSame(2, ExchangeLog::query()->count());
    }

    public function test_a_line_can_be_deleted(): void
    {
        $row = ExchangeLog::query()->firstOrFail();

        $this->actingAs($this->admin(), 'admin')->delete('/admin/integration_logs/'.$row->id)->assertRedirect();

        $this->assertSame(1, ExchangeLog::query()->count());
    }

    public function test_the_screen_follows_the_roles(): void
    {
        $this->actingAs($this->reader([]), 'admin')->get('/admin/integration_logs')->assertForbidden();
        $this->actingAs($this->reader(['integration_logs.view']), 'admin')->get('/admin/integration_logs')->assertOk();
        $this->actingAs($this->reader(['integration_logs.view']), 'admin')->delete('/admin/integration_logs/'.ExchangeLog::query()->value('id'))->assertForbidden();
    }

    public function test_the_name_and_the_labels_of_a_line(): void
    {
        $this->assertSame('Exchange log', ExchangeLog::getAdminLabel());
        $this->assertSame('integration_logs', ExchangeLog::getAdminRoutePrefix());
        $this->assertSame('id', ExchangeLog::getDefaultSortColumn());
        $this->assertSame('desc', ExchangeLog::getDefaultSortDirection());

        $line = new ExchangeLog(['status' => 'retry', 'direction' => 'out']);
        $this->assertSame('Failed, will retry', $line->status_label);
        $this->assertSame('From the site', $line->direction_label);

        $odd = new ExchangeLog(['status' => 'weird', 'direction' => 'sideways']);
        $this->assertSame('weird', $odd->status_label);
        $this->assertSame('sideways', $odd->direction_label);
    }
}
