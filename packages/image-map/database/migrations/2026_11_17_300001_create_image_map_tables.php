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
 * Image maps: a map has layers (floors), a layer has a picture and the
 * areas drawn on it; an area may have a status (free, booked, sold).
 * Texts are JSON, one value per content language.
 *
 * No foreign keys to other modules' tables: an area may point to a record
 * of any module (linkable_type, linkable_id), and that module may go.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_maps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->json('name');
            $table->string('code')->index();
            $table->json('description')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('show_search')->default(true);
            $table->boolean('show_legend')->default(true);
            $table->boolean('show_list')->default(true);
            $table->integer('sort')->default(500);
            $table->timestamps();
        });

        Schema::create('image_map_statuses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->json('name');
            $table->string('code')->index();
            $table->string('color', 7)->default('#0072B2');
            $table->string('pattern', 16)->default('solid');
            $table->string('outline', 16)->default('solid');
            $table->integer('sort')->default(500);
            $table->timestamps();
        });

        Schema::create('image_map_layers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->foreignId('map_id')->index();
            $table->json('name');
            $table->string('image')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->integer('sort')->default(500);
            $table->timestamps();
        });

        Schema::create('image_map_areas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->foreignId('layer_id')->index();
            $table->foreignId('status_id')->nullable()->index();
            $table->string('shape', 16);
            $table->json('geometry');
            $table->json('title');
            $table->json('description')->nullable();
            $table->string('action', 16)->default('popover');
            $table->string('content_type', 16)->default('text');
            $table->json('content')->nullable();
            $table->json('html')->nullable();
            $table->string('url')->nullable();
            $table->string('linkable_type')->nullable();
            $table->unsignedBigInteger('linkable_id')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort')->default(500);
            $table->timestamps();

            $table->index(['linkable_type', 'linkable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_map_areas');
        Schema::dropIfExists('image_map_layers');
        Schema::dropIfExists('image_map_statuses');
        Schema::dropIfExists('image_maps');
    }
};
