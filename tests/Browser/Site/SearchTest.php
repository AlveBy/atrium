<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Site;

use App\Models\Article;
use Atrium\Admin\Models\AdminUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * A visitor searches from the header; an editor searches every module from
 * the admin header.
 */
final class SearchTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_a_visitor_finds_an_article_from_the_header(): void
    {
        Article::query()->create([
            'name' => 'Hello world',
            'code' => 'hello-world',
            'active' => true,
            'detail_text' => '<p>Everything about the garden.</p>',
        ]);

        $this->browse(function (Browser $browser): void {
            $browser->visit('/')
                ->type('[data-search-box] input[name="q"]', 'garden')
                ->press('[data-search-box] button')
                ->waitForLocation('/search')
                ->assertSeeIn('[data-search-hit] h3', 'Hello world')
                ->assertSeeIn('[data-search-hit] mark', 'garden')
                ->assertInputValue('[data-search-form] input[name="q"]', 'garden');

            // Found words stand out by weight, not by colour alone.
            $this->assertSame('700', (string) $browser->script("return getComputedStyle(document.querySelector('[data-search-hit] mark')).fontWeight;")[0]);

            $browser->clickLink('Hello world')->assertPathIs('/articles/hello-world');
        });
    }

    public function test_an_editor_finds_records_from_the_admin_header(): void
    {
        $article = Article::query()->create(['name' => 'Draft about tulips', 'code' => 'tulips', 'active' => false]);
        $admin = AdminUser::query()->create(['name' => 'Ada Admin', 'email' => 'ada@example.com', 'password' => 'correct-horse', 'is_active' => true]);
        $admin->forceFill(['is_superadmin' => true])->save();

        $this->browse(function (Browser $browser) use ($admin, $article): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor('#admin-global-search')
                ->type('#admin-global-search', 'tulips')
                ->waitFor('[data-admin-search-group="articles"]')
                ->clickLink('Draft about tulips')
                ->waitForLocation("/admin/articles/{$article->id}/edit");
        });
    }

    public function test_on_a_narrower_screen_the_admin_box_opens_from_a_button(): void
    {
        Article::query()->create(['name' => 'Draft about tulips', 'code' => 'tulips', 'active' => false]);
        $admin = AdminUser::query()->create(['name' => 'Ada Admin', 'email' => 'ada@example.com', 'password' => 'correct-horse', 'is_active' => true]);
        $admin->forceFill(['is_superadmin' => true])->save();

        $this->browse(function (Browser $browser) use ($admin): void {
            // Below 1280px a field would squeeze the site and language switchers.
            $browser->resize(1024, 800)
                ->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor('[data-admin-search-toggle]')
                ->assertMissing('#admin-global-search')
                ->click('[data-admin-search-toggle]')
                ->waitFor('#admin-global-search')
                ->assertAttribute('[data-admin-search-toggle]', 'aria-expanded', 'true')
                ->assertFocused('#admin-global-search')
                ->type('#admin-global-search', 'tulips')
                ->waitFor('[data-admin-search-group="articles"]');

            [$right, $page, $screen] = $browser->script([
                'return document.querySelector("[data-admin-search-results]").getBoundingClientRect().right;',
                'return document.documentElement.scrollWidth;',
                'return window.innerWidth;',
            ]);
            $this->assertLessThanOrEqual($screen, $right);
            $this->assertLessThanOrEqual($screen, $page);

            $browser->keys('#admin-global-search', '{escape}')
                ->waitUntilMissing('#admin-global-search')
                ->resize(1920, 1080);
        });
    }
}
