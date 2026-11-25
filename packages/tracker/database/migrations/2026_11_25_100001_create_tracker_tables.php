<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// No foreign keys to admin_users: an administrator may be deleted, and what
// they did stays. Their id then points at nobody and is shown as a dash.
return new class extends Migration
{
    public function up(): void
    {
        // The words tasks are sorted by: what an administrator edits, with the
        // built-in ones put in below. A task keeps the code.
        foreach (['tracker_statuses', 'tracker_issue_types', 'tracker_priorities', 'tracker_resolutions'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name): void {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name', 100)->nullable();
                $table->unsignedInteger('sort')->default(500);
                $table->boolean('is_default')->default(false);

                if ($name === 'tracker_statuses') {
                    $table->string('category', 10)->default('active');
                }

                if ($name === 'tracker_priorities') {
                    $table->string('symbol', 4)->nullable();
                }

                if ($name === 'tracker_issue_types') {
                    $table->boolean('top_level')->default(false);
                    $table->boolean('needs_parent')->default(false);
                }
            });
        }

        $this->seed();

        Schema::create('tracker_projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('key', 10)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->boolean('is_archived')->default(false)->index();
            // The last task number given in the project: ATR-12 is the 12th.
            $table->unsignedInteger('task_counter')->default(0);
            $table->timestamps();
        });

        Schema::create('tracker_project_members', function (Blueprint $table): void {
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('admin_id');
            $table->primary(['project_id', 'admin_id']);
        });

        Schema::create('tracker_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->unsignedInteger('number');
            $table->string('type', 40)->default('task');
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('title', 255);
            $table->longText('description')->nullable();
            $table->string('status', 40)->index();
            $table->string('priority', 40)->default('medium');
            $table->string('resolution', 40)->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable()->index();
            $table->unsignedBigInteger('author_id')->nullable()->index();
            $table->json('labels')->nullable();
            $table->date('due_date')->nullable()->index();
            $table->decimal('estimate_hours', 8, 2)->nullable();
            $table->json('attachments')->nullable();
            $table->unsignedInteger('sort')->default(500);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'number']);
        });

        Schema::create('tracker_comments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('task_id')->index();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('tracker_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('task_id')->index();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('kind', 30);
            $table->string('old_value', 255)->nullable();
            $table->string('new_value', 255)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['tracker_resolutions', 'tracker_priorities', 'tracker_issue_types', 'tracker_statuses'] as $name) {
            Schema::dropIfExists($name);
        }

        Schema::dropIfExists('tracker_events');
        Schema::dropIfExists('tracker_comments');
        Schema::dropIfExists('tracker_tasks');
        Schema::dropIfExists('tracker_project_members');
        Schema::dropIfExists('tracker_projects');
    }

    private function seed(): void
    {
        $statuses = [['todo', 'todo', true], ['in_progress', 'active', false], ['review', 'active', false], ['done', 'done', false]];
        $priorities = [['lowest', '⇣'], ['low', '↓'], ['medium', '→'], ['high', '↑'], ['highest', '⇡']];

        DB::table('tracker_statuses')->insert(array_map(static fn (array $row, int $i): array => ['code' => $row[0], 'category' => $row[1], 'sort' => ($i + 1) * 100, 'is_default' => $row[2]], $statuses, array_keys($statuses)));
        DB::table('tracker_priorities')->insert(array_map(static fn (array $row, int $i): array => ['code' => $row[0], 'symbol' => $row[1], 'sort' => ($i + 1) * 100, 'is_default' => $row[0] === 'medium'], $priorities, array_keys($priorities)));
        DB::table('tracker_issue_types')->insert([
            ['code' => 'task', 'sort' => 100, 'is_default' => true, 'top_level' => false, 'needs_parent' => false],
            ['code' => 'bug', 'sort' => 200, 'is_default' => false, 'top_level' => false, 'needs_parent' => false],
            ['code' => 'story', 'sort' => 300, 'is_default' => false, 'top_level' => false, 'needs_parent' => false],
            ['code' => 'epic', 'sort' => 400, 'is_default' => false, 'top_level' => true, 'needs_parent' => false],
            ['code' => 'subtask', 'sort' => 500, 'is_default' => false, 'top_level' => false, 'needs_parent' => true],
        ]);
        DB::table('tracker_resolutions')->insert(array_map(static fn (string $code, int $i): array => ['code' => $code, 'sort' => ($i + 1) * 100, 'is_default' => $i === 0], ['done', 'wont_do', 'duplicate', 'cannot_reproduce'], array_keys(['done', 'wont_do', 'duplicate', 'cannot_reproduce'])));
    }
};
