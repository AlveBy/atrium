<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use App\Models\Article;
use Atrium\Admin\History\Revisions;
use Laravel\Dusk\Browser;

/**
 * A record changed twice: its two versions compared, and the first one put
 * back from the record's history.
 */
final class VersionsTest extends AdminTestCase
{
    public function test_an_older_version_is_compared_and_put_back(): void
    {
        $admin = $this->admin();
        $article = Article::query()->create(['name' => 'First', 'active' => true]);

        $this->browse(function (Browser $browser) use ($admin, $article): void {
            $browser->loginAs($admin, 'admin');

            foreach (['Second', 'Third'] as $name) {
                $browser->visit("/admin/articles/{$article->id}/edit")
                    ->waitFor('#name')
                    ->clear('#name')
                    ->type('#name', $name)
                    ->press('[data-action="save"]')
                    ->waitForText($name);
            }

            [$third, , $first] = app(Revisions::class)->of($article)->pluck('id')->all();

            $browser->visit("/admin/articles/{$article->id}/history")
                ->waitFor('[data-history-versions]')
                ->assertCount('[data-history-version]', 3)
                ->assertSeeIn('[data-history-version]:last-child', 'Before the history began')
                ->select('#history-compare-from', (string) $first)
                ->select('#history-compare-to', (string) $third)
                ->click('[dusk="history-compare"]')
                ->waitFor('[data-history-comparison]')
                ->assertSeeIn('[data-history-comparison] [data-history-field="name"] [data-history-old]', 'First')
                ->assertSeeIn('[data-history-comparison] [data-history-field="name"] [data-history-new]', 'Third')
                ->click('[data-history-version]:last-child [data-action="restore-version"]')
                ->waitFor('[data-action="restore-confirm"]')
                ->click('[data-action="restore-confirm"]')
                ->waitForText('The version is restored.')
                ->assertCount('[data-history-version]', 4)
                ->visit("/admin/articles/{$article->id}/edit")
                ->waitFor('#name')
                ->assertInputValue('#name', 'First');
        });
    }
}
