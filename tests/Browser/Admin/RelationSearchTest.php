<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Catalog\Models\Product;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * "Bought together" of a shop with a thousand products: the form does not
 * carry them all, the editor finds one by a few letters and saves it.
 */
final class RelationSearchTest extends AdminTestCase
{
    public function test_a_product_among_a_thousand_is_found_picked_and_saved(): void
    {
        $admin = $this->admin();
        $now = now()->toDateTimeString();

        foreach (array_chunk(range(1, 1050), 350) as $chunk) {
            DB::table('catalog_products')->insert(array_map(static fn (int $i): array => [
                'name' => sprintf('Gadget %04d', $i),
                'code' => sprintf('gadget-%04d', $i),
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }

        $product = Product::query()->where('code', 'gadget-0001')->sole();
        $wanted = Product::query()->where('code', 'gadget-0987')->sole();

        $this->browse(function (Browser $browser) use ($admin, $product, $wanted): void {
            $browser->loginAs($admin, 'admin')
                ->visit("/admin/catalog_products/{$product->id}/edit")
                ->waitFor('[data-relation-search="related"] [data-relation-input]');

            $page = $browser->driver->getPageSource();
            $this->assertStringContainsString('Gadget 0001', $page);
            $this->assertStringNotContainsString('Gadget 0987', $page, 'the form must not carry every product');
            $this->assertStringNotContainsString('Gadget 1050', $page);

            $browser->type('[data-relation-search="related"] [data-relation-input]', 'dget 098')
                ->waitFor("[data-relation-search=\"related\"] [data-option-value=\"{$wanted->id}\"]")
                ->assertSeeIn('[data-relation-search="related"] [data-relation-options]', 'Gadget 0980')
                ->assertDontSeeIn('[data-relation-search="related"] [data-relation-options]', 'Gadget 0970')
                // Down to the wanted record and Enter, as with the keyboard alone.
                ->keys('[data-relation-search="related"] [data-relation-input]', '{arrow_down}', '{arrow_down}', '{arrow_down}', '{arrow_down}', '{arrow_down}', '{arrow_down}', '{arrow_down}', '{arrow_down}')
                ->waitFor("[data-relation-search=\"related\"] [aria-selected][id=\"related-option-7\"][data-option-value=\"{$wanted->id}\"]")
                ->keys('[data-relation-search="related"] [data-relation-input]', '{enter}')
                ->waitFor("[data-relation-search=\"related\"] [data-chip-value=\"{$wanted->id}\"]")
                ->assertSeeIn("[data-relation-search=\"related\"] [data-chip-value=\"{$wanted->id}\"]", 'Gadget 0987')
                ->assertAttribute("[data-relation-search=\"related\"] [data-option-value=\"{$wanted->id}\"]", 'aria-selected', 'true')
                ->keys('[data-relation-search="related"] [data-relation-input]', '{escape}')
                ->assertAttribute('[data-relation-search="related"] [data-relation-input]', 'aria-expanded', 'false')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/catalog_products');

            $this->assertSame([$wanted->id], $product->fresh()?->related()->pluck('catalog_products.id')->all());

            // Opened again, the chosen product is named without a search.
            $browser->visit("/admin/catalog_products/{$product->id}/edit")
                ->waitFor("[data-relation-search=\"related\"] [data-chip-value=\"{$wanted->id}\"]")
                ->assertSeeIn("[data-relation-search=\"related\"] [data-chip-value=\"{$wanted->id}\"]", 'Gadget 0987');
        });
    }
}
