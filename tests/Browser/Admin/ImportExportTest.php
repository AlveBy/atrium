<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Transfer\Exporter;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Atrium\News\Models\News;
use Laravel\Dusk\Browser;

/**
 * Any module's list imports and exports, in a real browser: a price list in
 * windows-1251 into the catalog with its sections made on the way, a file the
 * list itself exported back into news without a change, and the buttons only
 * where a module offers them.
 */
final class ImportExportTest extends AdminTestCase
{
    /** @var array<int,string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    private function file(string $name, string $content): string
    {
        return $this->files[] = tap(sys_get_temp_dir().'/atrium-dusk-'.$name, static fn (string $path) => file_put_contents($path, $content));
    }

    public function test_check_and_import_a_price_list_into_the_catalog(): void
    {
        $admin = $this->admin();
        $file = $this->file('price.csv', mb_convert_encoding(
            "Артикул,Наименование,Цена,Раздел\nP-1,Куртка,\"12 990,50\",Одежда / Куртки\nP-2,,100,Одежда\n",
            'Windows-1251',
            'UTF-8',
        ));

        $this->browse(function (Browser $browser) use ($admin, $file): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/catalog_products')
                ->waitFor('[data-action="import"]')
                ->click('[data-action="import"]')
                ->waitForLocation('/admin/catalog_products/import')
                ->attach('@import-file', $file)
                ->press('@import-submit')
                ->waitFor('@import-settings')
                ->assertSeeIn('@import-file-info', 'Windows-1251')
                ->assertSelected('@import-target-0', 'article')
                ->assertSelected('@import-target-1', 'name')
                ->assertSelected('@import-target-2', 'price')
                ->assertSelected('@import-target-3', 'section_id')
                ->check('@import-option-create_sections')
                ->press('@import-check')
                ->waitFor('@import-checked')
                ->assertSeeIn('@import-count-created', '1')
                ->assertSeeIn('@import-count-failed', '1')
                ->assertSeeIn('@import-errors', '3');

            $this->assertSame(0, Product::query()->count());

            $browser->press('@import-run')
                ->waitFor('@import-done')
                ->assertSeeIn('@import-count-created', '1')
                ->assertSeeIn('@import-count-nodes', '2');
        });

        $product = Product::query()->sole();
        $this->assertSame(['P-1', 12990.5], [$product->article, (float) $product->price]);
        $this->assertSame('Куртки', CatalogSection::query()->findOrFail($product->section_id)->name);
    }

    public function test_a_list_exported_by_news_goes_back_in_unchanged(): void
    {
        $admin = $this->admin();
        $news = News::query()->create(['name' => 'Spring sale', 'code' => 'spring-sale', 'active' => true, 'sort' => 500, 'detail_text' => '<p>Everything 20% off</p>']);
        $before = $news->fresh()->only(['name', 'code', 'active', 'sort', 'detail_text']);

        $path = $this->file('news.csv', '');
        Exporter::for(News::class)->write(Exporter::output(Exporter::CSV, $path), News::query());

        $this->browse(function (Browser $browser) use ($admin, $path): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/news')
                ->waitFor('[data-action="export-csv"]')
                ->assertPresent('[data-action="export-xlsx"]')
                ->visit('/admin/news/import')
                ->attach('@import-file', $path)
                ->press('@import-submit')
                ->waitFor('@import-settings')
                ->assertSelected('@import-key', 'id')
                ->press('@import-check')
                ->waitFor('@import-checked')
                ->assertSeeIn('@import-count-updated', '1')
                ->assertSeeIn('@import-count-created', '0')
                ->press('@import-run')
                ->waitFor('@import-done')
                ->assertSeeIn('@import-count-updated', '1');
        });

        $this->assertSame(1, News::query()->count());
        $this->assertSame($before, $news->fresh()->only(['name', 'code', 'active', 'sort', 'detail_text']));
    }

    public function test_buttons_are_there_only_for_what_a_module_offers(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/pages')
                ->waitFor('[data-action="export-csv"]')
                ->assertPresent('[data-action="import"]')
                ->visit('/admin/sites')
                ->waitFor('[data-action="export-csv"]')
                ->assertMissing('[data-action="import"]');
        });
    }
}
