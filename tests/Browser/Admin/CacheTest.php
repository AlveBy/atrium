<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Api\ApiResources;
use Atrium\Admin\Cache\RecordCache;
use Atrium\Catalog\Models\Product;
use Laravel\Dusk\Browser;

/**
 * The "Cache" screen, from the menu to a module's cards dropped and the
 * whole cache cleared.
 */
final class CacheTest extends AdminTestCase
{
    public function test_clear_a_module_and_then_everything(): void
    {
        $admin = $this->admin();
        $key = array_search(Product::class, app(ApiResources::class)->modules(), true);
        $this->assertIsString($key);
        $before = $this->versions(['list:'.Product::class, 'all']);

        $this->browse(function (Browser $browser) use ($admin, $key): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->clickLink('Cache')
                ->waitFor('[data-page-title="admin.cache"]')
                ->assertPresent('[data-cache-stat="rate"]')
                ->assertPresent('[data-cache-stat="size"]')
                ->assertPresent('[data-cache-stat="store"]')
                ->select('[data-cache-module]', $key)
                ->click('[data-action="clear-module"]')
                ->waitForText('The cache is cleared.');
        });

        $afterModule = $this->versions(['list:'.Product::class, 'all']);
        $this->assertNotSame($before['list:'.Product::class], $afterModule['list:'.Product::class]);
        $this->assertSame($before['all'], $afterModule['all']);

        $this->browse(function (Browser $browser): void {
            $browser->visit('/admin/cache')
                ->waitFor('[data-action="clear-all"]')
                ->click('[data-action="clear-all"]')
                ->waitForText('The cache is cleared.');
        });

        $this->assertNotSame($afterModule['all'], $this->versions(['all'])['all']);
    }

    /**
     * The version counters as the site reads them now.
     *
     * @param  array<int,string>  $names
     * @return array<string,string>
     */
    private function versions(array $names): array
    {
        $versions = app(RecordCache::class)->versions();
        $versions->reset();

        return $versions->get($names);
    }
}
