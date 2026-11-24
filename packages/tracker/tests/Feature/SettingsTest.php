<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Modules\Settings;
use Atrium\Tracker\Models\Comment;
use Atrium\Tracker\Models\Project;
use Atrium\Tracker\Models\Task;
use Atrium\Tracker\Support\Admins;
use Atrium\Tracker\Tests\TestCase;
use Atrium\Tracker\TrackerServiceProvider;
use Inertia\Testing\AssertableInertia;

/**
 * The module's own page of settings, the comments the tasks keep and what the
 * package says about itself.
 */
final class SettingsTest extends TestCase
{
    public function test_the_settings_page_shows_the_three_settings(): void
    {
        $this->actingAs($this->admin(), 'admin')->get('/admin/modules/tracker/settings')
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page): void {
                $fields = collect($page->toArray()['props']['fields'])->keyBy('name');

                $this->assertSame(['statuses', 'notify_assigned', 'notify_status'], $fields->keys()->all());
                $this->assertStringContainsString('To do / In progress / In review / Done', $fields['statuses']['hint']);
            });
    }

    public function test_the_settings_are_saved_and_read_back(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/modules/tracker/settings', ['statuses' => ['Open', 'Closed'], 'notify_assigned' => false, 'notify_status' => true])
            ->assertSessionDoesntHaveErrors();
        $this->app->make(Settings::class)->flush();

        $this->assertSame(['Open', 'Closed'], Setting::get('tracker.statuses'));
        $this->assertFalse(Setting::get('tracker.notify_assigned'));
        $this->assertTrue(Setting::get('tracker.notify_status'));
    }

    public function test_more_than_fifteen_statuses_are_refused(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/modules/tracker/settings', ['statuses' => array_map(static fn (int $i): string => "S$i", range(1, 16))])
            ->assertSessionHasErrors(['statuses']);
    }

    public function test_the_letters_are_on_until_someone_switches_them_off(): void
    {
        $this->assertTrue(Setting::get('tracker.notify_assigned'));
        $this->assertTrue(Setting::get('tracker.notify_status'));
    }

    public function test_the_comments_are_a_read_only_list(): void
    {
        $task = $this->task();
        $author = $this->admin(name: 'Alice');
        Comment::query()->create(['task_id' => $task->id, 'author_id' => $author->id, 'body' => str_repeat('long ', 40)]);
        $comment = Comment::query()->sole();
        $this->actingAs($author, 'admin');

        $this->get('/admin/tracker_comments')->assertOk()->assertInertia(function (AssertableInertia $page): void {
            $props = $page->toArray()['props'];

            $this->assertSame(['task.title', 'author.name', 'body', 'created_at'], array_column($props['columns'], 'name'));
            $this->assertSame('Write the docs', $props['items']['data'][0]['task']['title']);
        });
        $this->get('/admin/tracker_comments/create')->assertForbidden();
        $this->get("/admin/tracker_comments/{$comment->id}/edit")->assertOk()->assertInertia(function (AssertableInertia $page): void {
            $this->assertTrue($page->toArray()['props']['fields'][0]['readonly']);
        });
        $this->put("/admin/tracker_comments/{$comment->id}", ['body' => 'Rewritten'])->assertForbidden();
        $this->assertStringStartsWith('long', $comment->fresh()->body);
        $this->assertSame('#'.$comment->id, $comment->getAdminTitle());
        $this->assertFalse(Comment::isImportableInAdmin());
        $this->assertFalse(Comment::isContentModule());
        $this->assertSame($task->id, $comment->task->id);
        $this->assertSame('Alice', $comment->author->name);
        $this->assertCount(1, Comment::getAdministrationFields());
    }

    public function test_the_package_names_its_resources(): void
    {
        $this->assertSame('Projects', Project::getAdminLabel());
        $this->assertSame('Tasks', Task::getAdminLabel());
        $this->assertSame('Task comments', Comment::getAdminLabel());
        $this->assertSame('Add project', Project::getAdminCreateLabel());
        $this->assertSame('Add task', Task::getAdminCreateLabel());
        $this->assertSame('name', Project::getDefaultSortColumn());
        $this->assertSame('asc', Project::getDefaultSortDirection());
        $this->assertSame('alveby/atrium-tracker', TrackerServiceProvider::MODULE);
    }

    public function test_the_current_administrator_is_known_only_inside_the_panel(): void
    {
        $this->assertNull(Admins::current());
        $this->assertNull(Admins::currentId());

        $admin = $this->admin(name: 'Alice');
        $this->actingAs($admin, 'admin');
        $this->assertSame($admin->id, Admins::currentId());
        $this->assertSame([$admin->id => 'Alice'], Admins::names());
    }
}
