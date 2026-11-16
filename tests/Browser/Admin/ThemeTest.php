<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use App\Models\Article;
use Atrium\Admin\Support\Themes;
use Laravel\Dusk\Browser;

/**
 * The colour theme is picked in the profile menu, applies at once and stays
 * after a reload. The screenshots in tests/Browser/screenshots/themes show
 * every theme on a list, a form and the open profile menu.
 */
final class ThemeTest extends AdminTestCase
{
    /** The page background each theme paints, as the browser reports it. */
    private const PAGE = [
        'graphite' => 'rgb(243, 244, 246)',
        'night' => 'rgb(15, 20, 27)',
    ];

    public function test_the_picked_theme_applies_at_once_and_stays_after_a_reload(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor('[data-action="user-menu"]');

            $this->assertSame('graphite', $this->theme($browser));
            $this->assertSame(self::PAGE['graphite'], $this->pageColour($browser));

            $browser->click('[data-action="user-menu"]')
                ->waitFor('[data-theme-option="night"]')
                ->assertAttribute('[data-theme-option="graphite"]', 'aria-checked', 'true')
                ->click('[data-theme-option="night"]')
                ->waitUntil('document.documentElement.dataset.theme === "night"')
                ->waitUntil('document.querySelector(\'[data-theme-option="night"]\')?.getAttribute("aria-checked") === "true"');

            $this->assertSame('night', $admin->fresh()?->theme);

            $browser->refresh()->waitFor('[data-action="user-menu"]');

            $this->assertSame('night', $this->theme($browser));
            $this->assertSame(self::PAGE['night'], $this->pageColour($browser));
        });
    }

    public function test_the_selected_menu_entry_differs_by_weight_and_a_bar_not_only_by_colour(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/articles')
                ->waitFor('[data-menu-route="admin.articles.index"]');

            $selected = $browser->script(<<<'JS'
                const link = document.querySelector('[data-menu-route="admin.articles.index"]');
                const other = document.querySelector('[data-menu-route="admin.dashboard"]');
                return {
                    current: link.getAttribute('aria-current'),
                    otherCurrent: other.getAttribute('aria-current'),
                    weight: getComputedStyle(link).fontWeight,
                    otherWeight: getComputedStyle(other).fontWeight,
                    shadow: getComputedStyle(link).boxShadow,
                };
            JS)[0];

            $this->assertSame('page', $selected['current']);
            $this->assertNull($selected['otherCurrent']);
            $this->assertSame('600', $selected['weight']);
            $this->assertSame('500', $selected['otherWeight']);
            $this->assertStringContainsString('inset', $selected['shadow']);
            $this->assertStringContainsString('3px 0px 0px', $selected['shadow']);
        });
    }

    public function test_screenshots_of_every_theme(): void
    {
        $admin = $this->admin();
        Article::query()->create(['name' => 'Summer programmes are open', 'active' => true]);
        Article::query()->create(['name' => 'Holiday opening hours', 'active' => true]);
        Article::query()->create(['name' => 'Draft: questions for the help section', 'active' => false]);
        $article = Article::query()->create(['name' => 'How we check reviews', 'active' => false]);

        $this->browse(function (Browser $browser) use ($admin, $article): void {
            $browser->resize(1280, 820)
                ->visit('/admin/login')
                ->waitFor('[data-atrium-logo]')
                ->screenshot('themes/login');

            foreach (Themes::THEMES as $theme) {
                $admin->forceFill(['theme' => $theme])->save();

                $browser->loginAs($admin, 'admin')
                    ->visit('/admin/articles')
                    ->waitFor('[data-badge="off"]')
                    ->assertPresent('[data-badge="on"]');

                $this->assertSame($theme, $this->theme($browser));

                $browser->screenshot("themes/{$theme}-list")
                    ->click('[data-action="user-menu"]')
                    ->waitFor('[data-theme-option="contrast"]')
                    ->screenshot("themes/{$theme}-menu")
                    ->visit("/admin/articles/{$article->id}/edit")
                    ->waitFor('#name')
                    ->screenshot("themes/{$theme}-form");
            }
        });
    }

    public function test_a_scrolled_list_passes_under_the_header_in_every_theme(): void
    {
        $admin = $this->admin();

        foreach (range(1, 14) as $n) {
            Article::query()->create(['name' => "Article {$n}", 'active' => $n % 3 !== 0]);
        }

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->resize(1280, 420);

            foreach (Themes::THEMES as $theme) {
                $admin->forceFill(['theme' => $theme])->save();

                $browser->loginAs($admin, 'admin')
                    ->visit('/admin/articles')
                    ->waitFor('[data-badge="off"]');

                // Scroll until the table's head row is under the header bar,
                // then ask what the browser draws on top: over the pinned
                // actions column and over the middle of the table.
                $state = $browser->script(<<<'JS'
                    const head = document.querySelector('thead');
                    window.scrollTo(0, head.getBoundingClientRect().top + window.scrollY - 20);
                    const bar = document.querySelector('header');
                    const actions = document.querySelector('thead th:last-child').getBoundingClientRect();
                    const middle = head.getBoundingClientRect();
                    const y = bar.getBoundingClientRect().bottom - 8;
                    const select = document.querySelector('header select');
                    return {
                        headUnderBar: head.getBoundingClientRect().top < bar.getBoundingClientRect().bottom,
                        overActions: bar.contains(document.elementFromPoint(actions.left + actions.width / 2, y)),
                        overMiddle: bar.contains(document.elementFromPoint(middle.left + middle.width / 2, y)),
                        selectAppearance: select ? getComputedStyle(select).appearance : 'none',
                    };
                JS)[0];

                $this->assertTrue($state['headUnderBar'], "{$theme}: the list did not scroll under the header");
                $this->assertTrue($state['overActions'], "{$theme}: the actions column is drawn over the header");
                $this->assertTrue($state['overMiddle'], "{$theme}: the table is drawn over the header");
                $this->assertSame('none', $state['selectAppearance'], "{$theme}: the header select is drawn by the system");

                $browser->screenshot("themes/{$theme}-scrolled");
            }
        });
    }

    private function theme(Browser $browser): string
    {
        return (string) $browser->script('return document.documentElement.dataset.theme;')[0];
    }

    private function pageColour(Browser $browser): string
    {
        return (string) $browser->script('return getComputedStyle(document.body).backgroundColor;')[0];
    }
}
