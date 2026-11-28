<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use App\Models\Article;
use Atrium\Admin\Models\TrashedRecord;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

/**
 * An article deleted from its list waits in the trash: restored from there
 * and back in the list, deleted again and then deleted for good.
 */
final class TrashTest extends AdminTestCase
{
    public function test_a_deleted_article_is_restored_and_then_deleted_for_good(): void
    {
        $admin = $this->admin();
        $article = Article::query()->create(['name' => 'Binned news', 'active' => true]);

        $this->browse(function (Browser $browser) use ($admin, $article): void {
            $delete = static fn (Browser $browser) => $browser->visit('/admin/articles')
                ->waitForText('Binned news')
                ->click('[data-action="delete"]')
                ->waitFor('[data-action="delete-confirm"]')
                ->click('[data-action="delete-confirm"]')
                ->waitUntilMissingText('Binned news');

            $delete($browser->loginAs($admin, 'admin'));

            $browser->visit('/admin/trash')
                ->waitFor('[data-trash-entries]')
                ->assertSeeIn('[data-trash-keeps]', '30 days')
                ->assertSeeIn('[data-trash-entry] [data-trash-record]', 'Binned news')
                ->click('[data-trash-entry] [data-action="trash-restore"]')
                ->waitForLocation("/admin/articles/{$article->id}/edit")
                ->waitForText('The record is restored.')
                ->assertInputValue('#name', 'Binned news');
            $this->assertSame(0, TrashedRecord::query()->count());

            $delete($browser);

            $browser->visit('/admin/trash')
                ->waitFor('[data-trash-entries]')
                ->click('[data-trash-entry] [data-action="trash-delete"]')
                ->waitFor('[data-action="trash-delete-confirm"]')
                ->click('[data-action="trash-delete-confirm"]')
                ->waitForText('The record is deleted for good.')
                ->waitFor('[data-trash-empty]');
        });

        $this->assertSame(0, DB::table('articles')->where('id', $article->id)->count());
    }
}
