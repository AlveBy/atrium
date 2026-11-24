<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table): void {
            $table->id();
            // Which connector: bitrix24, 1c, ...
            $table->string('integration', 40);
            // out: the site called someone; in: someone called the site.
            $table->string('direction', 3)->default('out');
            // What was done: lead.add, catalog.import, ...
            $table->string('action', 80);
            $table->string('status', 6);
            // What it was done to, in words: "Form submission #12".
            $table->string('subject')->nullable();
            // What came or went, in words.
            $table->text('message')->nullable();
            $table->text('error')->nullable();
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['integration', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
