<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Site;

use Atrium\Admin\Support\PublicUrl;
use Atrium\Theme\Tests\Skins\Fixtures\SkinContent;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DuskTestCase;

/**
 * The home page of each skin (ATRIUM_THEME_SKIN) in a real browser: its
 * heading, a readable heading and button, the credit, and no page wider
 * than the screen, on a desktop and a phone, in English and in German.
 */
final class SkinsTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * @return array<string,array{string,string,string}>
     */
    public static function skins(): array
    {
        return [
            'travel' => ['travel', 'Trips put together around you', 'Reisen, die wir um Sie herum planen'],
            'restaurant' => ['restaurant', 'Home cooking you will want to repeat', 'Küche zum Wiederkommen'],
            'clinic' => ['clinic', 'A medical centre close to home', 'Ein medizinisches Zentrum in Ihrer Nähe'],
            'shop' => ['shop', 'Things for the home that last for years', 'Dinge für Zuhause, die Jahre halten'],
        ];
    }

    #[DataProvider('skins')]
    public function test_the_skin_s_home_page_is_readable_and_fits_the_screen(string $skin, string $english, string $german): void
    {
        (new SkinContent($skin))->setContainer($this->app)->__invoke();
        PublicUrl::forgetPaths();

        $this->withEnv(['ATRIUM_THEME_SKIN' => $skin], function () use ($skin, $english, $german): void {
            $this->browse(function (Browser $browser) use ($skin, $english, $german): void {
                $browser->resize(1440, 900)
                    ->visit('/')
                    ->script('localStorage.clear()');

                $browser->visit('/')
                    ->assertSeeIn('#sk-hero-title', $english)
                    ->assertSeeIn('.sk-footer .credit', 'Made with Atrium');
                // Dusk looks for elements inside <body>; the skin is on <html>.
                $this->assertSame($skin, $browser->script('return document.documentElement.dataset.skin;')[0]);

                $this->assertReadable($browser, '#sk-hero-title');
                // The first button: a link styled as one, or a form's (the travel hero).
                $this->assertReadable($browser, '.sk-btn, .atrium-form button');
                $this->assertFits($browser, "{$skin}, desktop");
                $browser->screenshot("skins/{$skin}");

                $browser->resize(375, 812)->visit('/');
                $this->assertFits($browser, "{$skin}, phone");

                // German: the longest words of every language the site speaks.
                $browser->visit('/de')->assertSeeIn('#sk-hero-title', $german);
                $this->assertFits($browser, "{$skin}, phone, German");
                $browser->screenshot("skins/{$skin}-de-phone");
            });
        });
    }

    /**
     * The text against the first background behind it: at least 4.5:1.
     */
    private function assertReadable(Browser $browser, string $selector): void
    {
        [$front, $back] = $browser->script(<<<JS
            const element = document.querySelector('{$selector}');
            let behind = element;
            while (behind && getComputedStyle(behind).backgroundColor.match(/rgba\\(.*, 0\\)|transparent/)) {
                behind = behind.parentElement;
            }
            return [getComputedStyle(element).color, getComputedStyle(behind || document.documentElement).backgroundColor];
        JS)[0];

        $ratio = $this->ratio((string) $front, (string) $back);
        $this->assertGreaterThanOrEqual(4.5, $ratio, "{$selector}: {$front} on {$back} is ".round($ratio, 2).':1');
    }

    /**
     * Nothing sticks out to the right: no sideways scrolling.
     */
    private function assertFits(Browser $browser, string $where): void
    {
        [$page, $screen] = $browser->script('return [document.documentElement.scrollWidth, document.documentElement.clientWidth];')[0];

        $this->assertLessThanOrEqual($screen, $page, "{$where}: the page is {$page}px wide on a {$screen}px screen");
    }

    private function ratio(string $front, string $back): float
    {
        $light = [$this->luminance($front), $this->luminance($back)];

        return (max($light) + 0.05) / (min($light) + 0.05);
    }

    private function luminance(string $rgb): float
    {
        preg_match_all('/[\d.]+/', $rgb, $numbers);
        $channels = array_map(static function (string $value): float {
            $channel = (float) $value / 255;

            return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }, array_slice($numbers[0], 0, 3));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
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
