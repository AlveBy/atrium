<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use App\Models\Article;
use Atrium\Admin\Models\Site;
use Laravel\Dusk\Browser;

/**
 * Several sites on one domain: a second site set up in the admin in the
 * folder /site2/ of the same domain. Its pages open there, and a
 * link on them stays inside the folder.
 */
final class FolderSitesTest extends AdminTestCase
{
    public function test_a_site_in_a_folder_opens_there_and_its_links_stay_inside(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/sites/create')
                ->waitFor('#name')
                ->type('#name', 'Second site')
                ->type('#code', 'second')
                ->type('#folder', '/site2/')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/sites')
                ->waitForText('Second site');

            $site = Site::query()->where('code', 'second')->sole();
            $this->assertSame('site2', $site->folder);

            // Its own article, written with the site picked in the header.
            $browser->visit('/admin/articles')
                ->waitFor('[data-site-switcher]')
                ->select('[data-site-switcher]', (string) $site->id)
                ->waitUsing(5, 100, fn () => $browser->attribute('[data-action="visit-site"]', 'href') === rtrim((string) config('app.url'), '/').'/site2')
                ->visit('/admin/articles/create')
                ->waitFor('#name')
                ->type('#name', 'Folder news')
                ->waitUsing(5, 100, fn () => $browser->value('[data-field="code"] input') === 'folder-news')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/articles')
                ->waitForText('Folder news');

            $this->assertSame($site->id, Article::query()->withoutGlobalScopes()->where('code->en', 'folder-news')->sole()->site_id);

            // The folder site: its home page, an article, back to the list.
            $browser->visit('/site2')
                ->assertSeeIn('h2', 'Articles')
                ->clickLink('Folder news')
                ->assertPathIs('/site2/articles/folder-news')
                ->assertSeeIn('h1', 'Folder news')
                ->clickLink('Articles')
                ->assertPathIs('/site2/articles')
                ->assertSee('Folder news');

            // The root site does not have it.
            $browser->visit('/articles/folder-news')->assertSee('404');
        });
    }
}
