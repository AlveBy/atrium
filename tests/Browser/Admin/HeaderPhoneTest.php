<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Models\Site;
use Atrium\Admin\Support\Locales;
use Laravel\Dusk\Browser;

/**
 * On an Android phone, 360 pixels wide and with the fonts of Linux, the
 * header cut "Admin language" off and pushed its neighbours out of the bar.
 * The captions now wrap onto a second line; nothing is cut, nothing leaves
 * the screen, and nothing lies on top of its neighbour — in every language
 * of the panel, at the three widths phones come in.
 */
final class HeaderPhoneTest extends AdminTestCase
{
    /**
     * Every width, in every language. A phone, not a narrow desktop window: a
     * window has a floor of 500 pixels on macOS, and on Linux its scroll bar
     * takes 15 of them. A phone draws its scroll bar over the page.
     */
    public function test_the_header_fits_a_phone_in_every_language(): void
    {
        $admin = $this->admin();
        Site::query()->create(['name' => 'Second site with a long name', 'code' => 'second']);
        $locales = array_keys(app(Locales::class)->available());
        $this->assertCount(15, $locales);

        $this->browse(function (Browser $browser) use ($admin, $locales): void {
            $browser->loginAs($admin, 'admin');
            $problems = [];

            try {
                foreach ($locales as $locale) {
                    $admin->forceFill(['locale' => $locale])->save();
                    $this->phone($browser, 360);
                    $browser->visit('/admin')->waitFor('[data-site-switcher]');

                    foreach ([320, 360, 375] as $width) {
                        $this->phone($browser, $width);

                        foreach ($this->measure($browser) as $problem) {
                            $problems[] = "{$locale} at {$width}px: {$problem}";
                        }
                    }
                }
            } finally {
                $this->phone($browser, null);
            }

            $this->assertSame([], $problems);
        });
    }

    /**
     * What is wrong with the header as it stands now: a list of sentences,
     * empty when all is well.
     *
     * @return list<string>
     */
    private function measure(Browser $browser): array
    {
        // Two frames: the media queries answer to the new width after one.
        $problems = $browser->driver->executeAsyncScript(<<<'JS'
            const done = arguments[arguments.length - 1];
            requestAnimationFrame(() => requestAnimationFrame(() => {
                const problems = [];
                const screen = window.innerWidth;
                const header = document.querySelector('header');
                const bar = header.getBoundingClientRect();

                // A page wider than the screen made the phone zoom it all out.
                if (document.documentElement.scrollWidth > screen) {
                    problems.push('the page is wider than the screen');
                }

                if (header.scrollWidth > header.clientWidth) {
                    problems.push('the header is wider than itself');
                }

                const items = [...header.children]
                    .flatMap(side => [...side.children])
                    .map(el => ({ el, box: el.getBoundingClientRect() }))
                    .filter(item => item.box.width > 0)
                    .sort((a, b) => a.box.left - b.box.left);

                items.forEach(({ el, box }, i) => {
                    const name = el.dataset.action || el.querySelector('select')?.name || el.tagName;

                    if (box.left < 0 || box.right > screen + 0.5) {
                        problems.push(name + ' is off the screen');
                    }

                    if (i > 0 && box.left < items[i - 1].box.right - 0.5) {
                        problems.push(name + ' lies on ' + (items[i - 1].el.dataset.action || items[i - 1].el.querySelector('select')?.name || items[i - 1].el.tagName));
                    }
                });

                // The captions are the point: whole, and not taller than two lines.
                const captions = [...document.querySelectorAll('[data-switcher-caption]')];

                if (captions.length < 2) {
                    problems.push('a caption is missing');
                }

                captions.forEach(caption => {
                    const box = caption.getBoundingClientRect();
                    const line = parseFloat(getComputedStyle(caption).lineHeight);

                    if (caption.scrollWidth > caption.clientWidth || caption.scrollHeight > caption.clientHeight) {
                        problems.push('"' + caption.textContent + '" is cut');
                    }

                    if (box.height > 2 * line + 1) {
                        problems.push('"' + caption.textContent + '" takes more than two lines');
                    }

                    if (box.top < bar.top || box.bottom > bar.bottom) {
                        problems.push('"' + caption.textContent + '" leaves the bar');
                    }
                });

                done(problems);
            }));
        JS);

        return $problems;
    }

    /**
     * Emulates a phone of the given width, or puts the window back.
     */
    private function phone(Browser $browser, ?int $width): void
    {
        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', $width !== null
            ? ['cmd' => 'Emulation.setDeviceMetricsOverride', 'params' => ['width' => $width, 'height' => 812, 'deviceScaleFactor' => 2, 'mobile' => true]]
            : ['cmd' => 'Emulation.clearDeviceMetricsOverride', 'params' => (object) []]);
    }
}
