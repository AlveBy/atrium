<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Integrations\Enums\Status;
use Atrium\Integrations\Exchange;
use Atrium\Integrations\Models\ExchangeLog;
use Laravel\Dusk\Browser;

/**
 * The exchange log: what a connector did and why it failed, in the menu,
 * narrowed by result.
 */
final class IntegrationsTest extends AdminTestCase
{
    public function test_an_administrator_reads_the_log_and_narrows_it_to_failures(): void
    {
        $admin = $this->admin();
        $exchange = app(Exchange::class);
        $exchange->record('bitrix24', 'lead.add', Status::Ok, subject: 'Form submission #12', message: 'Lead 15');
        $exchange->record('1c', 'catalog.import', Status::Failed, subject: 'import.xml', error: 'Cannot read import.xml');

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/integration_logs')
                ->waitForText('Form submission #12')
                ->assertSee('Cannot read import.xml')
                ->assertSee('catalog.import')
                ->screenshot('integrations-log');

            $this->assertInPanel($browser, 'admin.integration_logs.index');

            $browser->visit('/admin/integration_logs?status=failed')
                ->waitForText('Cannot read import.xml')
                ->assertDontSee('Form submission #12');
        });

        $this->assertSame(2, ExchangeLog::query()->count());
    }
}
