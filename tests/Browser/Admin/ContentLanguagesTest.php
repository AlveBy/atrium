<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use App\Models\Article;
use Atrium\Admin\Models\Language;
use Laravel\Dusk\Browser;

/**
 * Stage 4 acceptance: a language added in the admin, a record filled in two
 * languages, both versions on the site under their own addresses.
 */
final class ContentLanguagesTest extends AdminTestCase
{
    public function test_add_a_language_fill_an_article_in_both_and_see_both_on_the_site(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/languages')
                ->waitFor('[data-action="create"]')
                ->click('[data-action="create"]')
                ->waitForLocation('/admin/languages/create')
                ->waitFor('#name')
                ->type('#name', 'Deutsch')
                ->type('#code', 'de')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/languages')
                ->waitForText('Deutsch');

            $this->assertSame('de', Language::query()->where('name', 'Deutsch')->sole()->code);

            // English first: the default language, no switch needed.
            $browser->visit('/admin/articles/create')
                ->waitFor('[data-content-locale="de"]')
                ->assertAttribute('[data-content-locale="en"]', 'aria-pressed', 'true')
                ->type('#name', 'Good morning')
                ->waitUsing(5, 100, fn () => $browser->value('[data-field="code"] input') === 'good-morning')

                // German: the same inputs, now holding the German text.
                ->click('[data-content-locale="de"]')
                ->waitFor('[data-content-locale-field="de"]')
                ->assertValue('#name', '')
                ->assertSeeIn('[data-field="name"] label', 'Deutsch')
                ->type('#name', 'Guten Morgen')
                ->waitUsing(5, 100, fn () => $browser->value('[data-field="code"] input') === 'guten-morgen')

                // Back to English: nothing was lost.
                ->click('[data-content-locale="en"]')
                ->waitFor('[data-content-locale-field="en"]')
                ->assertValue('#name', 'Good morning')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/articles')
                ->waitForText('Good morning');

            $article = Article::query()->sole();
            $this->assertSame(['en' => 'Good morning', 'de' => 'Guten Morgen'], $article->getTranslations('name'));
            $this->assertSame(['en' => 'good-morning', 'de' => 'guten-morgen'], $article->getTranslations('code'));

            $browser->visit('/articles/good-morning')
                ->assertSeeIn('h1', 'Good morning')
                ->assertSeeLink('Deutsch');
            $this->assertSame('en', $browser->script('return document.documentElement.lang;')[0]);
            $this->assertSame(
                url('/de/articles/guten-morgen'),
                $browser->script('return document.querySelector(\'link[hreflang="de"]\').href;')[0]
            );

            $browser->clickLink('Deutsch')
                ->waitForLocation('/de/articles/guten-morgen')
                ->assertSeeIn('h1', 'Guten Morgen');
            $this->assertSame('de', $browser->script('return document.documentElement.lang;')[0]);
        });
    }

    public function test_an_error_in_the_default_language_is_shown_on_its_switch(): void
    {
        $admin = $this->admin();
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch']);

        $this->browse(function (Browser $browser) use ($admin): void {
            // Only German is filled in; the English title is required.
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/articles/create')
                ->waitFor('[data-content-locale="de"]')
                ->click('[data-content-locale="de"]')
                ->waitFor('[data-content-locale-field="de"]')
                ->type('#name', 'Nur Deutsch')
                ->press('[data-action="save"]')
                ->waitFor('[data-content-locale="en"] span')
                ->assertSeeIn('[data-content-locale="en"] span', '1')
                ->assertPathIs('/admin/articles/create')
                ->click('[data-content-locale="en"]')
                ->waitFor('[data-field="name"] [data-error]')
                ->assertSeeIn('[data-field="name"] [data-error]', 'English');
        });

        $this->assertSame(0, Article::query()->count());
    }
}
