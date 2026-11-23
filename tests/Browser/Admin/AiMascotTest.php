<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Modules\Settings;
use Laravel\Dusk\Browser;
use Tests\Browser\Admin\Concerns\TalksToAStandInProvider;

/**
 * The face of the helper in the corner of the admin: the moods follow what
 * the helper does, a quiet minute brings a small trick, reduced motion keeps
 * the faces and stops the movement, and Modules → AI turns it all off. The
 * face is read from the drawing's data attributes, the mood's shape from its
 * parts (brows, mouth, eyes), never from a colour.
 *
 * PHP's own server answers one request at a time, so the chat's polls wait
 * for the whole answer and never see one half done. The tests of what the
 * helper is busy with (a magnifier, glasses) and of the daily limit answer
 * the chat's requests in the page; the server side of them is tested in
 * packages/ai/tests/MascotTest.php.
 */
final class AiMascotTest extends AdminTestCase
{
    use TalksToAStandInProvider;

    public function test_the_face_thinks_while_it_waits_then_smiles_and_rests_again(): void
    {
        $admin = $this->admin();

        $this->withProvider([], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openChat($browser, $admin)
                    ->assertAttribute('@ai-mascot', 'data-playful', 'true')
                    ->assertAttribute('@ai-mascot-face', 'aria-hidden', 'true')
                    ->waitFor('[dusk="ai-mascot-face"][data-mood="idle"]')
                    ->assertPresent('@ai-mascot-face [data-brows]')
                    ->assertPresent('@ai-mascot-face [data-mouth="belt"]');
                $resting = $this->brows($browser);

                $browser->type('@ai-input', 'How many articles?')
                    ->click('@ai-send')
                    ->assertAttribute('@ai-mascot-face', 'data-mood', 'thinking')
                    ->assertSeeIn('@ai-mascot-status', 'The helper is thinking');

                // Brows up: both ends of both brows higher than at rest.
                $thinking = $this->brows($browser);
                foreach ([0, 1] as $brow) {
                    $this->assertLessThan($resting[$brow][1], $thinking[$brow][1]);
                    $this->assertLessThan($resting[$brow][3], $thinking[$brow][3]);
                }

                $browser->waitFor('[dusk="ai-mascot-face"][data-mood="happy"]', 15)
                    ->assertPresent('@ai-mascot-face [data-mouth="smile"]')
                    ->assertPresent('@ai-mascot-face [data-eyes="shut"]')
                    ->assertMissing('@ai-mascot-face [data-mouth="belt"]')
                    ->assertSeeIn('@ai-mascot-status', 'The helper has answered')
                    ->assertSeeIn('@ai-answer', 'Question 1: How many articles?')
                    // The smile is short.
                    ->waitFor('[dusk="ai-mascot-face"][data-mood="idle"]', 5)
                    ->assertPresent('@ai-mascot-face [data-mouth="belt"]')
                    ->assertMissing('@ai-mascot-face [data-mouth="smile"]');

                $this->assertSame('', trim($browser->text('@ai-mascot-status')));
                $this->assertSame($resting, $this->brows($browser));
            });
        });
    }

    public function test_an_answer_that_fails_gets_a_sorry_face(): void
    {
        $admin = $this->admin();

        $this->withProvider([], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openChat($browser, $admin)
                    ->type('@ai-input', 'Is it broken?')
                    ->click('@ai-send')
                    ->waitFor('[dusk="ai-mascot-face"][data-mood="sorry"]', 15)
                    ->assertPresent('@ai-mascot-face [data-mouth="frown"]')
                    ->assertMissing('@ai-mascot-face [data-mouth="smile"]')
                    ->assertSeeIn('@ai-mascot-status', 'The helper could not answer')
                    ->assertSeeIn('@ai-error', 'The AI provider did not answer');

                // Brows like a roof: the inner ends higher than the outer ones.
                [$left, $right] = $this->brows($browser);
                $this->assertLessThan($left[1], $left[3]);
                $this->assertLessThan($right[3], $right[1]);

                $browser->waitFor('[dusk="ai-mascot-face"][data-mood="idle"]', 6)
                    ->assertPresent('@ai-mascot-face [data-mouth="belt"]');
            });
        });
    }

    public function test_the_used_up_daily_limit_gets_the_same_sorry_face(): void
    {
        $admin = $this->admin();

        $this->withProvider([], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openChat($browser, $admin);
                $this->answer($browser, [[429, ['message' => 'The daily AI limit is used up. Try again tomorrow.']]]);

                $browser->type('@ai-input', 'One more?')
                    ->click('@ai-send')
                    ->waitFor('@ai-error')
                    ->assertSeeIn('@ai-error', 'The daily AI limit is used up.')
                    ->assertAttribute('@ai-mascot-face', 'data-mood', 'sorry')
                    ->assertPresent('@ai-mascot-face [data-mouth="frown"]')
                    ->assertSeeIn('@ai-mascot-status', 'The helper could not answer');
            });
        });
    }

    public function test_the_face_shows_a_magnifier_over_the_records_and_glasses_over_the_guides(): void
    {
        $admin = $this->admin();

        $this->withProvider([], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openChat($browser, $admin);
                $this->answer($browser, [
                    [202, ['id' => 'one', 'status' => 'pending']],
                    [200, ['status' => 'pending', 'step' => 'search']],
                    [200, ['status' => 'pending', 'step' => 'read']],
                    [200, ['status' => 'pending', 'step' => 'read']],
                    [200, ['status' => 'done', 'value' => 'Two articles.']],
                ]);

                $browser->type('@ai-input', 'Where is it written?')
                    ->click('@ai-send')
                    ->assertMissing('@ai-mascot-face [data-costume]')
                    ->waitFor('[dusk="ai-mascot-face"][data-prop="glass"]', 5)
                    ->assertPresent('@ai-mascot-face [data-costume="glass"]')
                    ->assertSeeIn('@ai-mascot-status', 'The helper is looking through the records')
                    ->waitFor('[dusk="ai-mascot-face"][data-prop="glasses"]', 5)
                    ->assertPresent('@ai-mascot-face [data-costume="glasses"]')
                    ->assertMissing('@ai-mascot-face [data-costume="glass"]')
                    ->assertSeeIn('@ai-mascot-status', 'The helper is reading the guides')
                    ->waitFor('[dusk="ai-mascot-face"][data-mood="happy"]', 10)
                    ->assertSeeIn('@ai-answer', 'Two articles.')
                    // A costume is a moment: it goes, and the helper is itself again.
                    ->waitUntilMissing('@ai-mascot-face [data-costume]', 5)
                    ->assertMissing('[dusk="ai-mascot-face"][data-prop]');
            });
        });
    }

    public function test_writing_a_field_gives_the_helper_a_pencil_for_a_moment(): void
    {
        $admin = $this->admin();

        $this->withProvider([], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $browser->loginAs($admin, 'admin')
                    ->visit('/admin/news/create')
                    ->waitFor('@ai-mascot')
                    ->assertMissing('@ai-mascot-face [data-costume="pencil"]');

                // The field may sit on a tab of its own: the buttons are pressed where they are.
                // A visual editor keeps Generate in the AI menu of its toolbar.
                $browser->script('document.querySelector("[data-ai-toolbar]").click();');
                $browser->waitUntil('document.querySelector(\'[data-ai-item="generate"]\') !== null');
                $browser->script('document.querySelector(\'[data-ai-item="generate"]\').click();');

                $browser->waitFor('[dusk="ai-mascot-face"][data-prop="pencil"]')
                    ->assertPresent('@ai-mascot-face [data-costume="pencil"]')
                    ->assertSeeIn('@ai-mascot-status', 'The AI is writing a text')
                    ->waitUntilMissing('@ai-mascot-face [data-costume="pencil"]', 5);
            });
        });
    }

    public function test_coming_near_gets_a_wink(): void
    {
        $admin = $this->admin();

        $this->withProvider([], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openAdmin($browser, $admin)
                    ->waitFor('[dusk="ai-mascot-face"][data-mood="idle"]')
                    ->assertMissing('@ai-mascot-face [data-eyes="wink"]')
                    ->mouseover('@ai-mascot')
                    ->waitFor('[dusk="ai-mascot-face"][data-mood="hello"]')
                    ->assertPresent('@ai-mascot-face [data-eyes="wink"]')
                    // A wink is not news: a screen reader hears nothing.
                    ->assertDontSeeIn('@ai-mascot-status', 'helper')
                    ->mouseover('[data-action="user-menu"]')
                    ->waitFor('[dusk="ai-mascot-face"][data-mood="idle"]', 3)
                    ->assertMissing('@ai-mascot-face [data-eyes="wink"]')
                    ->assertMissing('@ai-chat');
            });
        });
    }

    public function test_a_quiet_minute_brings_a_small_trick_and_nothing_pops_up(): void
    {
        $admin = $this->admin();

        $this->withProvider([], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openAdmin($browser, $admin)->waitFor('[dusk="ai-mascot-face"][data-mood="idle"]');
                $this->assertStringStartsWith('ai-mascot-breathe', $this->animation($browser));

                $this->hurryTheQuietMinutes($browser);
                $browser->mouseover('@ai-mascot')
                    ->waitFor('[dusk="ai-mascot-face"][data-mood="hello"]')
                    ->mouseover('[data-action="user-menu"]')
                    ->waitFor('[dusk="ai-mascot-face"][data-trick]', 5);

                $this->assertContains($browser->attribute('@ai-mascot-face', 'data-trick'), ['yawn', 'look', 'hop']);
                $quiet = $browser->script('return window.__quiet;')[0];
                $this->assertNotEmpty($quiet);
                $this->assertGreaterThanOrEqual(60000, min($quiet));
                $this->assertLessThanOrEqual(120000, max($quiet));

                $browser->waitUntilMissing('[dusk="ai-mascot-face"][data-trick]', 5)
                    ->assertMissing('@ai-chat');
                $this->assertSame('', trim($browser->text('@ai-mascot-status')));
            });
        });
    }

    public function test_reduced_motion_keeps_the_faces_and_stops_the_movement(): void
    {
        $admin = $this->admin();

        $this->withProvider([], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openAdmin($browser, $admin);
                $this->reduceMotion($browser, true);

                try {
                    $browser->refresh()->waitFor('[dusk="ai-mascot-face"][data-mood="idle"]');
                    $this->assertSame('none', $this->animation($browser));

                    // No trick is waited for.
                    $this->hurryTheQuietMinutes($browser);
                    $browser->mouseover('@ai-mascot')
                        ->waitFor('[dusk="ai-mascot-face"][data-mood="hello"]')
                        ->mouseover('[data-action="user-menu"]')
                        ->waitFor('[dusk="ai-mascot-face"][data-mood="idle"]', 3);
                    $this->assertSame([], $browser->script('return window.__quiet;')[0]);
                    $browser->assertMissing('[dusk="ai-mascot-face"][data-trick]');

                    // The faces still change.
                    $browser->click('@ai-mascot')
                        ->waitFor('@ai-chat')
                        ->type('@ai-input', 'Still there?')
                        ->click('@ai-send')
                        ->assertAttribute('@ai-mascot-face', 'data-mood', 'thinking')
                        ->waitFor('[dusk="ai-mascot-face"][data-mood="happy"]', 15)
                        ->assertPresent('@ai-mascot-face [data-mouth="smile"]');
                    $this->assertSame('none', $this->animation($browser));
                } finally {
                    $this->reduceMotion($browser, false);
                }
            });
        });
    }

    public function test_switched_off_in_the_module_settings_it_is_the_calm_arch(): void
    {
        $admin = $this->admin();

        $this->withProvider([], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $browser->loginAs($admin, 'admin')
                    ->visit('/admin/modules/ai/settings')
                    ->waitFor('[data-settings-form]')
                    ->assertSee('Playful helper')
                    ->assertChecked('#playful')
                    ->uncheck('#playful')
                    ->press('[data-action="save"]')
                    ->waitForText('Settings saved.');

                app(Settings::class)->flush();
                $this->assertFalse(Setting::get('ai.playful'));

                $this->openChat($browser, $admin)
                    ->assertAttribute('@ai-mascot', 'data-playful', 'false')
                    ->assertAttributeMissing('@ai-mascot-face', 'data-mood')
                    ->assertMissing('@ai-mascot-face [data-brows]')
                    ->type('@ai-input', 'How many articles?')
                    ->click('@ai-send')
                    // Still told, not shown.
                    ->assertSeeIn('@ai-mascot-status', 'The helper is thinking')
                    ->assertPresent('@ai-mascot-face [data-mouth="belt"]')
                    ->waitFor('@ai-answer', 15)
                    ->assertPresent('@ai-mascot-face [data-mouth="belt"]')
                    ->assertMissing('@ai-mascot-face [data-mouth="smile"]')
                    ->assertMissing('@ai-mascot-face [data-eyes]')
                    ->mouseover('@ai-mascot')
                    ->assertMissing('@ai-mascot-face [data-eyes="wink"]');
                $browser->script('window.dispatchEvent(new CustomEvent("atrium:ai-writing"));');
                $browser->assertMissing('@ai-mascot-face [data-costume]');

                // And on again.
                $browser->visit('/admin/modules/ai/settings')
                    ->waitFor('[data-settings-form]')
                    ->check('#playful')
                    ->press('[data-action="save"]')
                    ->waitForText('Settings saved.')
                    ->visit('/admin')
                    ->waitFor('@ai-mascot')
                    ->assertAttribute('@ai-mascot', 'data-playful', 'true')
                    ->assertPresent('@ai-mascot-face [data-brows]');
            });
        });

        app(Settings::class)->flush();
        $this->assertTrue(Setting::get('ai.playful'));
    }

    /**
     * Every face in a light and a dark theme, four pixels to a point, for
     * the pull request: tests/Browser/screenshots/ai-mascot.
     */
    public function test_screenshots_of_every_face_in_a_light_and_a_dark_theme(): void
    {
        $this->withProvider([], function (): void {
            foreach (['graphite', 'night'] as $theme) {
                $admin = AdminUser::query()->updateOrCreate(['email' => "{$theme}@example.com"], [
                    'name' => 'Ada', 'password' => 'correct-horse', 'is_active' => true,
                ]);
                $admin->forceFill(['is_superadmin' => true, 'theme' => $theme])->save();

                $this->browse(function (Browser $browser) use ($admin, $theme): void {
                    $this->openAdmin($browser, $admin)->waitFor('[dusk="ai-mascot-face"][data-mood="idle"]');
                    $this->sharpen($browser, true);
                    $this->shoot($browser, $theme, 'idle');

                    $browser->mouseover('@ai-mascot')->waitFor('[dusk="ai-mascot-face"][data-mood="hello"]');
                    $this->shoot($browser, $theme, 'hello');
                    $browser->mouseover('[data-action="user-menu"]')->waitFor('[dusk="ai-mascot-face"][data-mood="idle"]', 3);

                    $browser->script('window.dispatchEvent(new CustomEvent("atrium:ai-writing"));');
                    $browser->waitFor('@ai-mascot-face [data-costume="pencil"]');
                    $this->shoot($browser, $theme, 'pencil');
                    $browser->waitUntilMissing('@ai-mascot-face [data-costume]', 5);

                    $browser->script('Math.random = () => 0;');
                    $this->hurryTheQuietMinutes($browser);
                    $browser->mouseover('@ai-mascot')->waitFor('[dusk="ai-mascot-face"][data-mood="hello"]')
                        ->mouseover('[data-action="user-menu"]')
                        ->waitFor('[dusk="ai-mascot-face"][data-trick="yawn"]', 5);
                    $this->shoot($browser, $theme, 'yawn');
                    $browser->waitUntilMissing('[dusk="ai-mascot-face"][data-trick]', 5);

                    $browser->click('@ai-mascot')->waitFor('@ai-chat');
                    $this->answer($browser, [
                        [202, ['id' => 'one', 'status' => 'pending']],
                        [200, ['status' => 'pending', 'step' => 'search']],
                        [200, ['status' => 'pending', 'step' => 'read']],
                        [200, ['status' => 'pending', 'step' => 'read']],
                        [200, ['status' => 'done', 'value' => 'Two articles.']],
                        [202, ['id' => 'two', 'status' => 'pending']],
                        [500, ['message' => 'Down.']],
                    ]);
                    $browser->type('@ai-input', 'Where?')->click('@ai-send')
                        ->assertAttribute('@ai-mascot-face', 'data-mood', 'thinking');
                    $this->shoot($browser, $theme, 'thinking');
                    $browser->waitFor('@ai-mascot-face [data-costume="glass"]', 5);
                    $this->shoot($browser, $theme, 'magnifier');
                    $browser->waitFor('@ai-mascot-face [data-costume="glasses"]', 5);
                    $this->shoot($browser, $theme, 'glasses');
                    $browser->waitFor('[dusk="ai-mascot-face"][data-mood="happy"]:not([data-prop])', 10);
                    $this->shoot($browser, $theme, 'smile');
                    $browser->type('@ai-input', 'Again?')->click('@ai-send')
                        ->waitFor('[dusk="ai-mascot-face"][data-mood="sorry"]', 5);
                    $this->shoot($browser, $theme, 'sorry');
                    $this->sharpen($browser, false);
                });
            }
        });
    }

    private function openChat(Browser $browser, AdminUser $admin): Browser
    {
        return $this->openAdmin($browser, $admin)
            ->waitFor('@ai-mascot')
            ->click('@ai-mascot')
            ->waitFor('@ai-chat');
    }

    /**
     * The two brows, each as [x1, y1, x2, y2] in the drawing's units.
     *
     * @return list<list<float>>
     */
    private function brows(Browser $browser): array
    {
        return $browser->script('return [...document.querySelectorAll("[dusk=ai-mascot-face] [data-brows] line")].map((line) => ["x1", "y1", "x2", "y2"].map((name) => Number(line.getAttribute(name))));')[0];
    }

    /** The animation the arch's body runs now, as the browser computes it. */
    private function animation(Browser $browser): string
    {
        return (string) $browser->script('return getComputedStyle(document.querySelector("[dusk=ai-mascot-face] .ai-mascot-body")).animationName;')[0];
    }

    /**
     * The chat's next requests get these answers, in order: [status, body].
     *
     * @param  list<array{int, array<string, mixed>}>  $answers
     */
    private function answer(Browser $browser, array $answers): void
    {
        $browser->script('window.__answers = '.json_encode($answers).';
            window.__fetch = window.__fetch || window.fetch;
            window.fetch = (url, options) => {
                if (!String(url).includes("/admin/ai/")) {
                    return window.__fetch(url, options);
                }
                const [status, body] = window.__answers.shift();
                return Promise.resolve(new Response(JSON.stringify(body), { status, headers: { "Content-Type": "application/json" } }));
            };');
    }

    /**
     * The next wait of a minute or more is cut to a moment, and the length of
     * every such wait noted in window.__quiet: one trick comes at once, and
     * the test sees how long the helper meant to keep quiet.
     */
    private function hurryTheQuietMinutes(Browser $browser): void
    {
        $browser->script('window.__quiet = [];
            window.__setTimeout = window.__setTimeout || window.setTimeout.bind(window);
            window.setTimeout = (callback, wait, ...rest) => {
                if (wait >= 60000) {
                    wait = window.__quiet.push(wait) === 1 ? 20 : wait;
                }
                return window.__setTimeout(callback, wait, ...rest);
            };');
    }

    private function reduceMotion(Browser $browser, bool $reduce): void
    {
        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', [
            'cmd' => 'Emulation.setEmulatedMedia',
            'params' => ['features' => [['name' => 'prefers-reduced-motion', 'value' => $reduce ? 'reduce' : '']]],
        ]);
    }

    /** Four pixels to a point while the faces are photographed. */
    private function sharpen(Browser $browser, bool $sharp): void
    {
        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', $sharp
            ? ['cmd' => 'Emulation.setDeviceMetricsOverride', 'params' => ['width' => 0, 'height' => 0, 'deviceScaleFactor' => 4, 'mobile' => false]]
            : ['cmd' => 'Emulation.clearDeviceMetricsOverride', 'params' => (object) []]);
    }

    /**
     * The helper alone, with room around it for a hop or a hat: the chat is
     * set aside, and the corner grows inwards so the face stays under the
     * pointer. A costume's entrance is let finish first.
     */
    private function shoot(Browser $browser, string $theme, string $face): void
    {
        $browser->script('const helper = document.querySelector("[dusk=ai-helper]");
            Object.assign(helper.style, { padding: "16px", right: "0", bottom: "0" });
            document.querySelector("[dusk=ai-chat]")?.style.setProperty("display", "none");');
        $browser->pause(350)->screenshotElement('@ai-helper', "ai-mascot/{$theme}-{$face}");
        $browser->script('const helper = document.querySelector("[dusk=ai-helper]");
            Object.assign(helper.style, { padding: "", right: "", bottom: "" });
            document.querySelector("[dusk=ai-chat]")?.style.removeProperty("display");');
    }
}
