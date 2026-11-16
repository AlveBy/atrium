<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use App\Models\Article;
use Laravel\Dusk\Browser;

/**
 * A module the app only listed in config/atrium.php — no controller, no pages —
 * goes through a full round trip in a real browser.
 */
final class ArticleCrudTest extends AdminTestCase
{
    public function test_create_find_edit_and_delete_an_article(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/articles')
                ->waitFor('[data-action="create"]')
                ->click('[data-action="create"]')
                ->waitForLocation('/admin/articles/create')
                ->waitFor('#name')
                ->type('#name', 'Hello, Atrium')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/articles')
                ->waitForText('Hello, Atrium');

            $article = Article::query()->sole();
            $this->assertSame('Hello, Atrium', $article->name);
            $this->assertTrue($article->active);
            $this->assertSame(500, $article->sort);

            $browser->type('[data-search-input]', 'Atrium')
                ->click('[data-action="search"]')
                ->waitForText('Hello, Atrium')
                ->click('[data-action="edit"]')
                ->waitForLocation("/admin/articles/{$article->id}/edit")
                ->clear('#name')
                ->type('#name', 'Renamed')
                ->press('[data-action="save"]')
                ->waitForText('Renamed');

            $this->assertSame('Renamed', $article->fresh()->name);

            $browser->click('[data-action="delete"]')
                ->waitFor('[data-action="delete-confirm"]')
                ->click('[data-action="delete-confirm"]')
                ->waitUntilMissingText('Renamed');

            $this->assertSame(0, Article::query()->count());
        });
    }

    public function test_an_empty_required_field_is_reported_and_nothing_is_saved(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/articles/create')
                ->waitFor('[data-action="save"]')
                ->press('[data-action="save"]')
                ->pause(300)
                ->assertPathIs('/admin/articles/create');

            // The browser stops the submit itself…
            $this->assertTrue($browser->script('return document.querySelector("#name").validity.valueMissing;')[0]);

            // …and the server says the same when the browser check is out of the way.
            $browser->script('document.querySelector("#name").removeAttribute("required");');
            $browser->press('[data-action="save"]')
                ->waitFor('[data-error]')
                ->assertPathIs('/admin/articles/create');
        });

        $this->assertSame(0, Article::query()->count());
    }
}
