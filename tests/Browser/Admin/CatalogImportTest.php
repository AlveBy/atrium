<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Laravel\Dusk\Browser;

/**
 * An editor uploads a price list from the products list, checks it, reads
 * which rows fail and imports the rest, in a real browser.
 */
final class CatalogImportTest extends AdminTestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = sys_get_temp_dir().'/atrium-dusk-price.csv';
        file_put_contents($this->file, mb_convert_encoding(
            "Артикул,Наименование,Цена,Раздел\nP-1,Куртка,\"12 990,50\",Одежда / Куртки\nP-2,,100,Одежда\n",
            'Windows-1251',
            'UTF-8',
        ));
    }

    protected function tearDown(): void
    {
        @unlink($this->file);

        parent::tearDown();
    }

    public function test_check_and_import_a_price_list(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/catalog_products')
                ->waitFor('[data-link="import"]')
                ->click('[data-link="import"]')
                ->waitForLocation('/admin/catalog-import')
                ->attach('@import-file', $this->file)
                ->press('@import-submit')
                ->waitFor('@import-check')
                ->assertSeeIn('@import-file-info', 'Windows-1251')
                ->assertSelected('@import-target-0', 'article')
                ->assertSelected('@import-target-1', 'name')
                ->assertSelected('@import-target-2', 'price')
                ->assertSelected('@import-target-3', 'section')
                ->check('@import-create-sections')
                ->press('@import-check')
                ->waitFor('@import-checked')
                ->assertSeeIn('@import-count-created', '1')
                ->assertSeeIn('@import-count-failed', '1')
                ->assertSeeIn('@import-errors', '3');

            $this->assertSame(0, Product::query()->count());

            $browser->press('@import-run')
                ->waitFor('@import-done')
                ->assertSeeIn('@import-count-created', '1')
                ->assertSeeIn('@import-count-sections', '2');
        });

        $product = Product::query()->sole();
        $this->assertSame(['P-1', 12990.5], [$product->article, (float) $product->price]);
        $this->assertSame('Куртки', CatalogSection::query()->findOrFail($product->section_id)->name);
    }
}
