<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Site;

use Atrium\Admin\Modules\Modules;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DuskTestCase;

/**
 * The "atrium" skin (ATRIUM_THEME_SKIN=atrium), the demo's own, over the
 * demo content: the home page, the catalog with its filters, a product
 * with its gallery, the news, an article and the stores, on a desktop and a
 * phone, light and dark, in English and in German. Every page fits the
 * screen; the screenshots are in tests/Browser/screenshots/skins.
 */
final class AtriumSkinTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * @return array<string,array{string,int,int}>
     */
    public static function screens(): array
    {
        return [
            'desktop, light' => ['light', 1440, 900],
            'desktop, dark' => ['dark', 1440, 900],
            'phone, light' => ['light', 375, 812],
            'phone, dark' => ['dark', 375, 812],
        ];
    }

    #[DataProvider('screens')]
    public function test_every_page_of_the_skin_fits_the_screen_in_both_themes(string $theme, int $width, int $height): void
    {
        $this->seedDemo();

        $this->withEnv(['ATRIUM_THEME_SKIN' => 'atrium'], function () use ($theme, $width, $height): void {
            $this->browse(function (Browser $browser) use ($theme, $width, $height): void {
                $screen = ($width > 800 ? 'desktop' : 'phone').'-'.$theme;
                $browser->resize($width, $height)->visit('/');
                $browser->script("localStorage.setItem('atrium-theme', '{$theme}');");

                $pages = [
                    'home' => ['/', 'The whole site, run from one panel'],
                    'catalog' => ['/catalog', 'Catalog'],
                    'product' => ['/catalog/computers/laptops/aero-14', 'Aero 14'],
                    'news' => ['/news', 'News'],
                    'article' => ['/news/a-new-store-in-the-city-centre', 'A new store in the city centre'],
                    'stores' => ['/stores', 'Stores'],
                ];

                foreach ($pages as $name => [$path, $heading]) {
                    $browser->visit($path)->assertSeeIn('h1', $heading);
                    $this->assertSame($theme, $browser->script('return document.documentElement.dataset.theme;')[0]);
                    $this->assertFits($browser, "{$name}, {$screen}");
                    // Once the page has finished moving (the hero's tiles rise in).
                    $browser->waitUntil('document.getAnimations().every(animation => animation.playState !== "running")');
                    // The whole page in one picture, not only the first screen.
                    $tall = min((int) $browser->script('return document.documentElement.scrollHeight;')[0], 4000);
                    $browser->resize($width, max($height, $tall))->screenshot("skins/atrium-{$name}-{$screen}")->resize($width, $height);
                }

                // German: the longest words of the languages the site speaks.
                $browser->visit('/de')->assertSeeIn('h1', 'Die ganze Website aus einem Panel');
                $this->assertFits($browser, "home, {$screen}, German");
                $browser->visit('/de/catalog')->assertSeeIn('h1', 'Katalog');
                $this->assertFits($browser, "catalog, {$screen}, German");
            });
        });
    }

    public function test_the_catalog_filters_by_brand_and_the_gallery_swaps_the_picture(): void
    {
        $this->seedDemo();

        $this->withEnv(['ATRIUM_THEME_SKIN' => 'atrium'], function (): void {
            $this->browse(function (Browser $browser): void {
                $browser->resize(1440, 900)->visit('/catalog')->waitFor('@brand-filter');
                $all = count($browser->elements('@product'));

                $browser->click('@brand-filter');
                $shown = (int) $browser->script("return document.querySelectorAll('[dusk=\"product\"]:not([hidden])').length;")[0];
                $this->assertGreaterThan(0, $shown);
                $this->assertLessThan($all, $shown);

                $browser->visit('/catalog/computers/laptops/aero-14')->waitFor('@gallery-main');
                $before = $browser->attribute('@gallery-main', 'src');
                $browser->elements('@gallery-thumb')[1]->click();
                $browser->waitUntil("document.querySelector('[dusk=\"gallery-main\"]').getAttribute('src') !== ".json_encode($before));
                $this->assertNotSame($before, $browser->attribute('@gallery-main', 'src'));
            });
        });
    }

    private function seedDemo(): void
    {
        $modules = app(Modules::class);

        foreach ($modules->dependencyOrder($modules->installed()) as $module) {
            $modules->seedDemo($module);
        }
    }

    /**
     * Nothing sticks out to the right: no sideways scrolling.
     */
    private function assertFits(Browser $browser, string $where): void
    {
        [$page, $screen] = $browser->script('return [document.documentElement.scrollWidth, document.documentElement.clientWidth];')[0];

        $this->assertLessThanOrEqual($screen, $page, "{$where}: the page is {$page}px wide on a {$screen}px screen");
    }

    /**
     * Runs the test with lines added to the .env the served app reads on
     * every request (Dusk has swapped it for .env.dusk.*), then puts it back.
     *
     * @param  array<string,string>  $values
     */
    private function withEnv(array $values, callable $test): void
    {
        $file = base_path('.env');
        $original = (string) file_get_contents($file);
        // The first line of a name wins, so the old ones go.
        $kept = preg_grep('/^\s*('.implode('|', array_keys($values)).')\s*=/', explode("\n", $original), PREG_GREP_INVERT) ?: [];
        $lines = array_map(fn (string $key, string $value): string => "{$key}={$value}", array_keys($values), $values);

        file_put_contents($file, rtrim(implode("\n", $kept))."\n".implode("\n", $lines)."\n");

        try {
            $test();
        } finally {
            file_put_contents($file, $original);
        }
    }
}
