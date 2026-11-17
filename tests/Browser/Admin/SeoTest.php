<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Catalog\Models\Product;
use Atrium\Seo\Models\NotFound;
use Atrium\Seo\Models\Redirect;
use Laravel\Dusk\Browser;

/**
 * The SEO screens: a missing address from the 404 log becomes a redirect,
 * the check leads to the record to fix, a CSV file brings redirects in.
 */
final class SeoTest extends AdminTestCase
{
    public function test_a_missing_address_becomes_a_redirect(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->visit('/old-lamp')->assertSee('404');

            $browser->loginAs($admin, 'admin')
                ->visit('/admin/seo_not_found')
                ->waitForText('/old-lamp')
                ->screenshot('seo-404-log');

            // Link columns open in a new tab; follow the address instead.
            $create = (string) $browser->attribute('a[target="_blank"][href*="/admin/seo_redirects/create"]', 'href');
            $this->assertStringContainsString('source=%2Fold-lamp', $create);

            $browser->visit($create)
                ->waitFor('#target')
                ->assertInputValue('#source', '/old-lamp')
                ->type('#target', '/catalog')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/seo_redirects')
                ->waitForText('/old-lamp')
                ->screenshot('seo-redirects');

            $browser->visit('/old-lamp')->assertPathIs('/catalog');
        });

        $this->assertSame('/catalog', Redirect::query()->sole()->target);
        $this->assertSame(0, NotFound::query()->count());
    }

    public function test_the_check_leads_to_the_record_to_fix(): void
    {
        $admin = $this->admin();
        $name = 'Adjustable desk lamp with a warm light and a long flexible arm';
        $lamp = Product::query()->create(['name' => $name, 'code' => 'lamp', 'preview_text' => 'A lamp.']);

        $this->browse(function (Browser $browser) use ($admin, $lamp, $name): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/seo_issues')
                ->waitForText($name)
                ->assertSee(__('atrium-seo::seo.checks.title_long'))
                ->screenshot('seo-check');

            $edit = "/admin/catalog_products/{$lamp->id}/edit";
            $browser->visit((string) $browser->attribute('a[target="_blank"][href$="'.$edit.'"]', 'href'))
                ->waitForLocation($edit)
                ->assertInputValue('#name', $name);
        });
    }

    public function test_redirects_are_imported_from_a_csv_file(): void
    {
        $admin = $this->admin();
        $csv = tempnam(sys_get_temp_dir(), 'seo').'.csv';
        file_put_contents($csv, "source;target;status\n/old-page;/new-page;301\n/sale;/catalog;302\n/broken;;999\n");

        $this->browse(function (Browser $browser) use ($admin, $csv): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/seo_redirect_imports/create')
                ->waitFor('#file')
                ->attach('#file', $csv)
                ->waitForText('.csv')
                ->press('[data-action="save"]')
                ->waitForText('Added 2, updated 0, skipped 1')
                ->screenshot('seo-import-report');
        });

        unlink($csv);
        $this->assertSame(['/old-page' => 301, '/sale' => 302], Redirect::query()->orderBy('source')->pluck('status', 'source')->all());
    }
}
