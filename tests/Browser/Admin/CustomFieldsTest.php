<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Catalog\Models\Product;
use Atrium\CustomFields\CustomFields;
use Atrium\CustomFields\Models\CustomField;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\Browser;

/**
 * An editor adds a field to the products from their list, fills it on a
 * product and sees it in the list, in a real browser.
 */
final class CustomFieldsTest extends AdminTestCase
{
    public function test_add_a_field_to_the_products_and_fill_it(): void
    {
        $admin = $this->admin();
        $product = Product::query()->create(['name' => 'Aero 14', 'active' => true]);

        $this->browse(function (Browser $browser) use ($admin, $product): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/catalog_products')
                ->waitFor('[data-link="custom-fields"]')
                ->click('[data-link="custom-fields"]')
                ->waitForLocation('/admin/custom_fields')
                ->click('[data-action="create"]')
                ->waitForLocation('/admin/custom_fields/create')
                ->waitFor('#code')
                ->type('#label', 'Colour')
                ->type('#code', 'colour')
                ->select('#type', 'select')
                ->type('#options', "red = Red\nblue = Blue")
                ->check('#filterable')
                ->check('#show_in_list')
                ->press('[data-action="save"]')
                ->waitForText('Colour');

            $field = CustomField::query()->sole();
            $this->assertSame((new Product)->getMorphClass(), $field->module);
            $this->assertTrue(Schema::hasColumn('catalog_products', 'custom'));

            $browser->visit("/admin/catalog_products/{$product->id}/edit")
                ->waitFor('#colour')
                ->select('#colour', 'blue')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/catalog_products')
                ->waitForText('Blue');
        });

        // The field was made by the server process; this one had read the list before.
        app(CustomFields::class)->flush();

        $product = Product::query()->findOrFail($product->id);
        $this->assertSame('blue', app(CustomFields::class)->get($product, 'colour'));
        $this->assertSame([$product->id], Product::query()->whereCustom('colour', 'blue')->pluck('id')->all());
    }
}
