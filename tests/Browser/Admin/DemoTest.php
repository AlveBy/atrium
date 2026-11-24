<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Models\AdminUser;
use Atrium\TwoFactor\Models\Factor;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * A public demo (docs/demo.md): the sign-in page says how to get in, and
 * nobody can set a second factor up.
 */
final class DemoTest extends AdminTestCase
{
    private const NOTE = '@login-demo';

    /**
     * The visitor the reset makes: not a superadmin, with the default details.
     */
    private function visitor(): AdminUser
    {
        return AdminUser::query()->create([
            'name' => 'Demo',
            'email' => 'demo@atrium.test',
            'password' => 'demo',
            'is_active' => true,
        ]);
    }

    public function test_the_sign_in_page_of_a_demo_shows_the_details_filled_in_and_they_let_the_visitor_in(): void
    {
        $this->visitor();

        $this->withEnv(['ATRIUM_DEMO' => 'true'], function (): void {
            $this->browse(function (Browser $browser): void {
                $browser->visit('/admin/login')
                    ->waitFor(self::NOTE)
                    ->assertSeeIn('@login-demo-title', 'Public demo')
                    ->assertSeeIn('@login-demo-email', 'demo@atrium.test')
                    ->assertSeeIn('@login-demo-password', 'demo')
                    ->assertInputValue('#email', 'demo@atrium.test')
                    ->assertInputValue('#password', 'demo')
                    ->press('button[type="submit"]')
                    ->waitForLocation('/admin')
                    ->assertSee('Demo');
            });
        });
    }

    public function test_the_sign_in_page_of_another_site_shows_no_details_and_no_filled_in_fields(): void
    {
        $this->visitor();

        $this->browse(function (Browser $browser): void {
            $browser->visit('/admin/login')
                ->waitFor('#email')
                ->assertMissing(self::NOTE)
                ->assertDontSee('Public demo')
                ->assertInputValue('#email', '')
                ->assertInputValue('#password', '');
        });
    }

    public function test_a_wrong_password_is_still_refused_in_a_demo_and_the_field_is_filled_in_again(): void
    {
        $this->visitor();

        $this->withEnv(['ATRIUM_DEMO' => 'true'], function (): void {
            $this->browse(function (Browser $browser): void {
                $browser->visit('/admin/login')
                    ->waitFor(self::NOTE);

                // The field comes filled in, so typing clears it first. A
                // keystroke that goes astray leaves it empty, and the
                // browser itself then refuses to send the form (required):
                // no request, no error. Type until the text is really there.
                $browser->waitUsing(5, 100, function () use ($browser): bool {
                    $browser->type('#password', 'not-the-password');

                    return $browser->inputValue('#password') === 'not-the-password';
                }, 'The wrong password did not land in the field.');

                $browser->press('button[type="submit"]')
                    ->waitFor('[data-error]')
                    ->assertPathIs('/admin/login')
                    ->assertInputValue('#password', 'demo');
            });
        });
    }

    /**
     * @return array<string, array{string}>
     */
    public static function themes(): array
    {
        return [
            'graphite' => ['graphite'],
            'day' => ['day'],
            'night' => ['night'],
            'sand' => ['sand'],
            'contrast' => ['contrast'],
        ];
    }

    #[DataProvider('themes')]
    public function test_the_details_are_readable_in_every_theme(string $theme): void
    {
        $this->withEnv(['ATRIUM_DEMO' => 'true', 'ATRIUM_ADMIN_THEME' => $theme], function () use ($theme): void {
            $this->browse(function (Browser $browser) use ($theme): void {
                $browser->visit('/admin/login')->waitFor(self::NOTE);

                $this->assertSame($theme, $browser->script('return document.documentElement.dataset.theme;')[0]);

                // WCAG 1.4.3: 4.5:1 for the heading, the sentence and both values.
                foreach (['@login-demo-title', '@login-demo-email', '@login-demo-password', self::NOTE] as $selector) {
                    $ratio = $this->contrast($browser, $selector);

                    $this->assertGreaterThanOrEqual(4.5, $ratio, "{$theme}: {$selector} has a contrast of {$ratio}:1");
                }
            });
        });
    }

    /**
     * @return array<string, array{string}>
     */
    public static function locales(): array
    {
        $codes = array_map('basename', glob(__DIR__.'/../../../packages/admin/lang/*', GLOB_ONLYDIR));

        return array_combine($codes, array_map(static fn (string $code): array => [$code], $codes));
    }

    #[DataProvider('locales')]
    public function test_the_note_is_in_the_language_of_the_page_and_fits_a_phone(string $locale): void
    {
        $lines = (require __DIR__."/../../../packages/admin/lang/{$locale}/auth.php")['login'];

        $this->withEnv(['ATRIUM_DEMO' => 'true'], function () use ($lines, $locale): void {
            $this->browse(function (Browser $browser) use ($lines, $locale): void {
                $browser->resize(375, 800)
                    ->visit('/admin/login')
                    ->waitFor(self::NOTE)
                    ->select('[data-locale-switcher]', $locale)
                    ->waitForTextIn('@login-demo-title', $lines['demo_title'])
                    ->assertSeeIn('@login-demo-title', $lines['demo_title'])
                    ->assertSeeIn(self::NOTE, $lines['demo_text'])
                    ->assertSeeIn(self::NOTE, $lines['email'])
                    ->assertSeeIn(self::NOTE, $lines['password']);

                $this->assertTrue($browser->script('const note = document.querySelector("[dusk=login-demo]"); return note.scrollWidth <= note.clientWidth && document.documentElement.scrollWidth <= window.innerWidth;')[0], 'the note fits the page without scrolling sideways');
            });
        });
    }

    public function test_the_security_page_of_a_demo_has_no_forms_and_nothing_can_be_set_up(): void
    {
        $visitor = $this->visitor();

        $this->withEnv(['ATRIUM_DEMO' => 'true', 'ATRIUM_2FA_ENABLED' => 'true'], function () use ($visitor): void {
            $this->browse(function (Browser $browser) use ($visitor): void {
                $browser->loginAs($visitor, 'admin')
                    ->visit('/admin/auth/security')
                    ->waitFor('@a2f-demo-locked')
                    ->assertSeeIn('@a2f-demo-locked', 'Not available in the demo')
                    ->assertMissing('@a2f-totp-start')
                    ->assertMissing('@a2f-sms-start')
                    ->assertMissing('@a2f-email-enable')
                    ->assertMissing('@a2f-passkey-add')
                    ->assertMissing('@a2f-recovery-regenerate')
                    ->assertMissing('@a2f-devices-revoke')
                    ->assertMissing('form');
            });
        });

        $this->assertSame(0, Factor::query()->count());
    }

    public function test_the_security_page_of_another_site_has_its_forms(): void
    {
        $visitor = $this->visitor();

        $this->withEnv(['ATRIUM_2FA_ENABLED' => 'true'], function () use ($visitor): void {
            $this->browse(function (Browser $browser) use ($visitor): void {
                $browser->loginAs($visitor, 'admin')
                    ->visit('/admin/auth/security')
                    ->waitFor('@a2f-security')
                    ->assertPresent('@a2f-totp-start')
                    ->assertMissing('@a2f-demo-locked');
            });
        });
    }

    /**
     * The WCAG contrast ratio of the text of an element against the
     * background it sits on, as the browser paints them.
     */
    private function contrast(Browser $browser, string $selector): float
    {
        $css = $selector === self::NOTE ? '[dusk="login-demo"]' : '[dusk="'.substr($selector, 1).'"]';

        /** @var array{list<int>, list<int>} $colours */
        $colours = $browser->script(<<<JS
            const parse = (value) => value.match(/[\\d.]+/g).slice(0, 4).map(Number);
            const element = document.querySelector('{$css}');
            let background = [0, 0, 0, 0];

            for (let node = element; node && background[3] === 0; node = node.parentElement) {
                const own = parse(getComputedStyle(node).backgroundColor);
                background = own.length === 3 ? [...own, 1] : own;
            }

            return [parse(getComputedStyle(element).color), background];
        JS)[0];

        $luminance = static function (array $rgb): float {
            [$r, $g, $b] = array_map(static function (float $channel): float {
                $channel /= 255;

                return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
            }, array_slice($rgb, 0, 3));

            return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        };

        [$lighter, $darker] = [max($luminance($colours[0]), $luminance($colours[1])), min($luminance($colours[0]), $luminance($colours[1]))];

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }
}
