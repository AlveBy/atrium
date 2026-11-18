<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Monitoring\Buffer\Flusher;
use Atrium\Monitoring\Collector\SqlNormalizer;
use Atrium\Monitoring\Models\UptimeCheck;
use Atrium\Monitoring\Uptime\UptimeChecker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Laravel\Dusk\Browser;

/**
 * Speed and uptime: the dashboard tile, the report with its filters, and a
 * slow query's own page. The numbers come from measurements written the way
 * the flush writes them; the maths is covered by the module's PHPUnit tests.
 */
final class MonitoringTest extends AdminTestCase
{
    private string $hash;

    protected function setUp(): void
    {
        parent::setUp();

        // The panel's own requests are measured into this file while the test runs; a
        // leftover from earlier runs could reach the early-flush size and add rows.
        File::deleteDirectory((string) config('atrium-monitoring.buffer.path'));

        $now = Carbon::now()->getTimestamp();
        $sql = SqlNormalizer::normalize('select * from "products" where "price" > 100');
        $this->hash = SqlNormalizer::hash($sql, 'sqlite');
        $records = [];

        foreach ([['catalog.show', '/catalog/blue-mug', 640.0], ['home', '/', 90.0]] as [$route, $path, $ms]) {
            foreach (range(1, 4) as $n) {
                $records[] = ['k' => 'r', 'at' => $now - $n * 60, 'site' => 1, 'loc' => 'en', 'route' => $route, 'path' => $path, 'm' => 'GET', 's' => 200, 'd' => $ms + $n, 'q' => 6, 'qm' => 4.0, 'ch' => 3, 'cm' => 1, 'mem' => 18432];
            }
        }

        $records[] = ['k' => 'q', 'at' => $now, 'site' => 1, 'route' => 'catalog.show', 'path' => '/catalog/blue-mug', 'h' => $this->hash, 'sql' => $sql, 'c' => 'sqlite', 'd' => 340.0];

        foreach ([['/', 'mobile', 1800], ['/catalog/blue-mug', 'mobile', 4600], ['/catalog/blue-mug', 'desktop', 2900]] as [$path, $device, $lcp]) {
            $records[] = ['k' => 'v', 'at' => $now, 'site' => 1, 'loc' => 'en', 'path' => $path, 'dev' => $device, 'con' => '4g', 'lcp' => $lcp, 'inp' => 150, 'cls' => 0.04, 'ttfb' => 500, 'fcp' => 1200];
        }

        app(Flusher::class)->write($records);

        $check = new UptimeCheck;
        $check->forceFill(['name' => 'Demo shop', 'url' => 'https://shop.example.test/'])->save();
        config(['atrium-monitoring.uptime.failures' => 1]);
        app(UptimeChecker::class)->record($check, false, 503, 40.0, 'HTTP 503', Carbon::now()->subMinute());
    }

    public function test_the_tile_leads_to_the_report_and_the_filters_narrow_it(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor('[data-gadget="monitoring"]')
                ->assertSeeIn('@monitoring-down', 'Demo shop')
                ->assertSeeIn('[data-gadget="monitoring"]', 'catalog.show')
                ->click('@monitoring-open')
                ->waitFor('@monitoring-server')
                ->assertSeeIn('@summary-requests', '8')
                ->assertSeeIn('@slow-routes', 'catalog.show')
                ->assertSeeIn('@slow-pages', '/catalog/blue-mug')
                ->assertSeeIn('@uptime-checks', 'Demo shop')
                ->assertSeeIn('@incidents', 'HTTP 503')
                // Each chart carries its numbers as a table too.
                ->assertPresent('@chart-lcp table')
                ->assertMissing('@filter-route')
                ->click('@slow-routes @route-filter')
                ->waitFor('@filter-route')
                ->assertSeeIn('@filter-route', 'catalog.show')
                ->assertQueryStringHas('route', 'catalog.show')
                ->assertSeeIn('@summary-requests', '4')
                ->click('@filter-clear')
                ->waitUntilMissing('@filter-route')
                ->select('@filter-device', 'desktop')
                // The pages table counts every device: hidden while one is chosen.
                ->waitUntilMissing('@slow-pages')
                ->assertQueryStringHas('device', 'desktop')
                ->select('@filter-device', '*')
                ->waitFor('@slow-pages')
                ->click('@slow-pages @page-filter')
                ->waitFor('@filter-page')
                ->assertSeeIn('@filter-page', '/catalog/blue-mug');
        });
    }

    public function test_a_slow_query_opens_its_own_page(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/monitoring')
                ->waitFor('@slow-queries')
                ->click('@slow-queries @query-link')
                ->waitFor('@query-sql')
                ->assertPathIs('/admin/monitoring/queries/'.$this->hash)
                ->assertSeeIn('@query-sql', 'select * from "products" where "price" > ?')
                ->assertSeeIn('@query-routes', 'catalog.show')
                ->assertSeeIn('@query-routes', '/catalog/blue-mug')
                ->assertPresent('@chart-query table')
                ->click('@monitoring-back')
                ->waitFor('@monitoring-server')
                ->assertPathIs('/admin/monitoring');
        });
    }

    public function test_the_menu_entry_sits_in_site_health(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitForText('Site health')
                // The module's lists live in the folded group, not on top of the sidebar.
                ->assertMissing('[data-menu-route="admin.uptime_checks.index"]')
                ->assertMissing('[data-menu-route="admin.monitoring_incidents.index"]')
                ->press('Site health')
                ->waitFor('[data-menu-route="admin.monitoring.index"]')
                ->assertVisible('[data-menu-route="admin.uptime_checks.index"]')
                ->assertVisible('[data-menu-route="admin.monitoring_incidents.index"]')
                ->click('[data-menu-route="admin.monitoring.index"]')
                ->waitFor('@monitoring-server')
                ->assertPathIs('/admin/monitoring');
        });
    }
}
