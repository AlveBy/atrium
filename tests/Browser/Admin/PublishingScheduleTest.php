<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use App\Models\Article;
use Atrium\Admin\Models\ScheduledRecord;
use Illuminate\Support\Facades\Artisan;
use Laravel\Dusk\Browser;

/**
 * An article planned to come out: the list marks it, the scheduler
 * switches it on and the mark goes.
 */
final class PublishingScheduleTest extends AdminTestCase
{
    public function test_a_planned_article_is_marked_in_the_list_and_switched_on_by_the_scheduler(): void
    {
        $admin = $this->admin();
        $article = Article::query()->create(['name' => 'Spring sale', 'active' => false]);

        $this->browse(function (Browser $browser) use ($admin, $article): void {
            $browser->loginAs($admin, 'admin')
                ->visit("/admin/articles/{$article->id}/edit")
                ->waitFor('#publish_at')
                ->assertSee('Publish at')
                ->assertSee('Unpublish at');

            // A datetime-local box takes its value in the order of the
            // browser's language: set it the way a picker does.
            $browser->script(<<<'JS'
                const input = document.getElementById('publish_at');
                Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set.call(input, '2026-01-01T09:00');
                input.dispatchEvent(new Event('input', { bubbles: true }));
                JS);

            $browser->press('[data-action="save"]')
                ->waitForText('Record updated.')
                ->visit('/admin/articles')
                ->waitFor("[data-row-id=\"{$article->id}\"] [data-badge=\"scheduled\"]")
                ->assertSeeIn("[data-row-id=\"{$article->id}\"] [data-badge=\"scheduled\"]", 'Scheduled')
                ->assertAttributeContains("[data-row-id=\"{$article->id}\"] [data-badge=\"scheduled\"]", 'title', 'Switches on');

            $this->assertSame('2026-01-01 09:00:00', ScheduledRecord::query()->sole()->publish_at->format('Y-m-d H:i:s'));
            Artisan::call('atrium:publish');

            $browser->refresh()
                ->waitFor("[data-row-id=\"{$article->id}\"] [data-badge=\"on\"]")
                ->assertMissing('[data-badge="scheduled"]');
        });

        $this->assertTrue($article->fresh()->active);
        $this->assertSame(0, ScheduledRecord::query()->count());
    }
}
