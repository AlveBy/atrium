<?php

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Models\PageTiming;
use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;

final class DashboardTest extends AdminTestCase
{
    public function test_the_speed_tiles_show_the_measured_pages(): void
    {
        $admin = $this->admin();

        foreach ([['/', 400], ['/catalog', 700], ['/catalog/heavy', 1200]] as [$path, $ms]) {
            PageTiming::query()->create(['path' => $path, 'duration_ms' => $ms]);
        }

        Cache::flush();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor('[data-gadget="site-speed"]')
                // Median 0.7 s: the "Fast" band, named in words.
                ->assertSeeIn('[data-gadget="site-speed"] [data-speed-band]', 'Fast')
                ->assertSeeIn('[data-gadget="site-speed"]', '0,70')
                ->assertMissing('[data-performance-details]')
                ->click('[data-action="performance-details"]')
                ->waitFor('[data-performance-details]')
                ->assertSeeIn('[data-performance-details]', '/catalog/heavy')
                ->assertAttribute('[data-action="performance-details"]', 'aria-expanded', 'true');
        });
    }
}
