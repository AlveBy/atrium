<?php

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Demo\DemoSeeder;
use Atrium\Admin\Demo\Models\Product;
use Laravel\Dusk\Browser;

/**
 * Stage 6a acceptance: the demo of `atrium:install --demo`, from the admin
 * form with its Bitrix tabs to the page a visitor sees.
 */
final class DemoShopTest extends AdminTestCase
{
    public function test_edit_a_demo_product_and_see_it_on_the_site(): void
    {
        $admin = $this->admin();
        (new DemoSeeder)->run();
        $product = Product::query()->where('code', 'aero-14')->sole();

        $this->browse(function (Browser $browser) use ($admin, $product): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/demo_products')
                ->waitForText('Aero 16 Pro')
                ->assertSee('Aero 14')
                // The preview pictures are on the public disk: the list has to
                // load them from /storage, not from /admin/demo/....
                ->waitUntil("[...document.querySelectorAll('tbody img')].length > 0 && [...document.querySelectorAll('tbody img')].every(img => img.complete && img.naturalWidth > 0)")
                ->visit("/admin/demo_products/{$product->id}/edit")
                ->waitFor('[data-tab="seo"]')
                ->assertSeeIn('[data-tab="params"]', 'General')
                ->assertSelected('#brand_id', (string) $product->brand_id)
                ->assertInputValue('#article', 'LM-A14')
                ->click('[data-tab="seo"]')
                ->waitFor('#seo_title')
                ->assertInputValue('#seo_title', 'Aero 14 laptop: 1.2 kg, 18 hours of battery')
                ->click('[data-tab="params"]')
                ->waitFor('#price')
                ->clear('#price')
                ->type('#price', '1099')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/demo_products');

            $this->assertSame('1099.00', $product->fresh()?->price);

            $browser->visit('/catalog/computers/laptops/aero-14')
                ->assertSeeIn('h1', 'Aero 14')
                ->assertSee('1099.00')
                ->assertSee('Bestseller')
                ->clickLink('Quiet One')
                ->assertPathIs('/catalog/audio/headphones/quiet-one')
                ->assertSeeIn('h1', 'Quiet One');
        });
    }
}
