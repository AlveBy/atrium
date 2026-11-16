<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            // Each article belongs to one site; the admin and the public
            // pages only see the current site's articles.
            $table->foreignId('site_id')->nullable()->index();
            // Translatable columns hold {"en": "…", "de": "…"}; the code is
            // unique per language, which the admin checks on save.
            $table->json('name');
            $table->json('code')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort')->default(500);
            $table->json('preview_text')->nullable();
            $table->json('detail_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
