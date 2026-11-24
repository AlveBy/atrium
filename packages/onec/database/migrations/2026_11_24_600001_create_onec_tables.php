<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * onec_links remembers which catalogue row a 1C identifier (a GUID) is, so a
 * repeated exchange updates the row instead of making another one.
 * onec_runs is the exchange screen: one row per file 1C sent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onec_links', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 16);
            $table->string('guid', 100);
            $table->unsignedBigInteger('model_id');
            $table->timestamps();

            $table->unique(['kind', 'guid']);
        });

        Schema::create('onec_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('file');
            $table->string('status', 16);
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->unsignedInteger('sections_created')->default(0);
            $table->unsignedInteger('sections_updated')->default(0);
            $table->unsignedInteger('products_created')->default(0);
            $table->unsignedInteger('products_updated')->default(0);
            $table->unsignedInteger('offers_updated')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('errors')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onec_runs');
        Schema::dropIfExists('onec_links');
    }
};
