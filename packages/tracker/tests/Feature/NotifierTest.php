<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Tracker\Mail\TaskMail;
use Atrium\Tracker\Models\Task;
use Atrium\Tracker\Tests\TestCase;
use Illuminate\Support\Facades\Mail;

/**
 * An administrator hears when a task is given to them or when the status of
 * their task changes — and not about their own changes.
 */
final class NotifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_the_assignee_is_mailed_about_a_new_task(): void
    {
        $alice = $this->admin(name: 'Alice');
        $bob = $this->admin(name: 'Bob');
        $this->actingAs($alice, 'admin');

        $task = $this->task(null, ['assignee_id' => $bob->id]);

        Mail::assertSent(TaskMail::class, 1);
        Mail::assertSent(TaskMail::class, function (TaskMail $mail) use ($bob, $task): bool {
            $this->assertTrue($mail->hasTo($bob->email));
            $this->assertSame('ATR-1 was given to you: Write the docs', $mail->envelope()->subject);
            $mail->assertSeeInText('ATR-1');
            $mail->assertSeeInText(route('admin.tracker_tasks.edit', $task->id));

            return true;
        });
    }

    public function test_the_assignee_is_mailed_when_the_task_is_given_to_them_later(): void
    {
        $bob = $this->admin(name: 'Bob');
        $task = $this->task();

        $task->update(['assignee_id' => $bob->id]);
        $task->update(['title' => 'Renamed']);

        Mail::assertSent(TaskMail::class, 1);
    }

    public function test_the_assignee_is_mailed_when_the_status_changes(): void
    {
        $alice = $this->admin(name: 'Alice');
        $bob = $this->admin(name: 'Bob');
        $task = $this->task(null, ['assignee_id' => $bob->id]);
        $this->actingAs($alice, 'admin');

        $task->update(['status' => 'review']);

        Mail::assertSent(TaskMail::class, fn (TaskMail $mail): bool => $mail->kind === TaskMail::STATUS
            && $mail->envelope()->subject === 'ATR-1 is now “In review”'
            && str_contains($mail->render(), 'To do → In review'));
    }

    public function test_nobody_is_mailed_when_nobody_is_assigned(): void
    {
        $task = $this->task();
        $task->update(['status' => 'done']);

        Mail::assertNothingSent();
    }

    public function test_a_change_made_by_the_assignee_is_not_mailed_to_them(): void
    {
        $bob = $this->admin(name: 'Bob');
        $this->actingAs($bob, 'admin');

        $task = $this->task(null, ['assignee_id' => $bob->id]);
        $task->update(['status' => 'done']);

        Mail::assertNothingSent();
    }

    public function test_a_deactivated_administrator_is_not_mailed(): void
    {
        $gone = $this->admin(active: false, name: 'Gone');

        $this->task(null, ['assignee_id' => $gone->id]);

        Mail::assertNothingSent();
    }

    public function test_the_letters_can_be_switched_off_one_by_one(): void
    {
        $bob = $this->admin(name: 'Bob');
        $this->settings(['notify_assigned' => false]);
        $task = $this->task(null, ['assignee_id' => $bob->id]);
        Mail::assertNothingSent();

        $task->update(['status' => 'done']);
        Mail::assertSent(TaskMail::class, 1);

        $this->settings(['notify_assigned' => true, 'notify_status' => false]);
        $task->update(['status' => 'todo']);
        Mail::assertSent(TaskMail::class, 1);

        $this->task(null, ['assignee_id' => $bob->id]);
        Mail::assertSent(TaskMail::class, 2);
    }

    public function test_the_letter_speaks_the_language_of_its_reader(): void
    {
        $bob = $this->admin(name: 'Bob');
        $bob->forceFill(['locale' => 'ru'])->save();

        $this->task(null, ['assignee_id' => $bob->id]);

        Mail::assertSent(TaskMail::class, fn (TaskMail $mail): bool => $mail->locale === 'ru' && str_contains($mail->envelope()->subject, 'ATR-1'));
        $this->assertSame('en', app()->getLocale());
    }

    public function test_a_status_the_list_no_longer_has_is_written_as_it_was_stored(): void
    {
        $task = $this->task(null, ['status' => 'Old name']);
        $task->forceFill(['assignee_id' => $this->admin(name: 'Bob')->id])->save();
        Mail::fake();

        $task->update(['status' => 'done']);

        Mail::assertSent(TaskMail::class, fn (TaskMail $mail): bool => str_contains($mail->render(), 'Old name → Done'));
    }
}
