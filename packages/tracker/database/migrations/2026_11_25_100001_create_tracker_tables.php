<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// No foreign keys to admin_users: an administrator may be deleted, and what
// they did stays. Their id then points at nobody and is shown as a dash.
return new class extends Migration
{
    public function up(): void
    {
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
            $table->string('type', 20)->default('task');
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('title', 255);
            $table->longText('description')->nullable();
            $table->string('status', 100)->index();
            $table->string('priority', 20)->default('medium');
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
        Schema::dropIfExists('tracker_events');
        Schema::dropIfExists('tracker_comments');
        Schema::dropIfExists('tracker_tasks');
        Schema::dropIfExists('tracker_project_members');
        Schema::dropIfExists('tracker_projects');
    }
};
