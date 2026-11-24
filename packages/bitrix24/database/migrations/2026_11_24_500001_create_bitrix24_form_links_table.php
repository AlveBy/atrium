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
        Schema::create('bitrix24_form_links', function (Blueprint $table): void {
            $table->id();
            // One link per form; the form's deletion takes it along.
            $table->foreignId('form_id')->unique()->constrained('forms')->cascadeOnDelete();
            // lead: one lead. deal: a contact and a deal that points to it.
            $table->string('mode', 4)->default('lead');
            $table->boolean('active')->default(true);
            // One "form field = Bitrix24 field" per line.
            $table->text('mapping')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitrix24_form_links');
    }
};
