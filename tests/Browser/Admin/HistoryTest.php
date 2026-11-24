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
 * A change made in the form shows up in the record's history and in the
 * activity log, from what to what and by whom.
 */
final class HistoryTest extends AdminTestCase
{
    public function test_a_change_is_in_the_history_of_the_record_and_in_the_journal(): void
    {
        $admin = $this->admin();
        $article = Article::query()->create(['name' => 'Hello', 'active' => true]);

        $this->browse(function (Browser $browser) use ($admin, $article): void {
            $browser->loginAs($admin, 'admin')
                ->visit("/admin/articles/{$article->id}/edit")
                ->waitFor('#name')
                ->clear('#name')
                ->type('#name', 'Hello, Atrium')
                ->press('[data-action="save"]')
                ->waitForText('Hello, Atrium')
                ->visit("/admin/articles/{$article->id}/edit")
                ->waitFor('[data-action="history"]')
                ->click('[data-action="history"]')
                ->waitForLocation("/admin/articles/{$article->id}/history")
                ->waitFor('[data-history-entry]')
                ->assertSeeIn('[data-history-entry] [data-history-what]', 'Changed')
                ->assertSeeIn('[data-history-entry] [data-history-who]', $admin->name)
                ->assertSeeIn('[data-history-field="name"] [data-history-old]', 'Hello')
                ->assertSeeIn('[data-history-field="name"] [data-history-new]', 'Hello, Atrium')
                ->assertMissing('[data-history-field="active"]')
                ->click('[data-action="back-to-record"]')
                ->waitForLocation("/admin/articles/{$article->id}/edit");

            $browser->visit('/admin/activity')
                ->waitFor('[data-history-entries]')
                ->assertSeeIn('[data-history-record]', 'Hello, Atrium')
                ->select('#history-module', 'users')
                ->click('[dusk="history-apply"]')
                ->waitFor('[data-history-empty]')
                ->assertQueryStringHas('module', 'users')
                ->click('[dusk="history-reset"]')
                ->waitFor('[data-history-entries]')
                ->assertQueryStringMissing('module')
                ->click('[data-history-record]')
                ->waitForLocation("/admin/articles/{$article->id}/edit");
        });
    }
}
