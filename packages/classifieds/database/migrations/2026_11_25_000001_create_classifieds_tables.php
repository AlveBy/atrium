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
 * The board: places, a category tree, ads and what visitors do
 * with them. Users live in the application (or the cabinet module), so
 * their ids carry no foreign key; contacts and messages are stored
 * encrypted and never searched by their text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classified_regions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->json('name');
            $table->string('code', 100);
            $table->integer('sort')->default(500);
            $table->timestamps();

            $table->unique(['site_id', 'code']);
        });

        Schema::create('classified_cities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->foreignId('region_id')->nullable()->index();
            $table->json('name');
            $table->string('code', 100);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->integer('sort')->default(500);
            $table->timestamps();

            $table->unique(['site_id', 'code']);
        });

        Schema::create('classified_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->foreignId('parent_id')->nullable()->index();
            $table->json('name');
            $table->string('code', 100);
            $table->json('description')->nullable();
            // Codes of the custom fields an ad of this category fills in;
            // subcategories add theirs to the parent's.
            $table->json('fields')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort')->default(500);
            $table->timestamps();

            $table->unique(['site_id', 'parent_id', 'code']);
        });

        Schema::create('classified_ads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->foreignId('category_id')->index();
            $table->foreignId('city_id')->nullable()->index();
            // Who posted it, as the personal cabinet (alveby/atrium-account)
            // keeps it: a person's ad has no company, a company's ad is
            // shared by its members.
            $table->unsignedBigInteger('account_user_id')->index();
            $table->unsignedBigInteger('account_company_id')->nullable()->index();
            $table->string('title');
            $table->string('code', 191)->index();
            $table->text('text')->nullable();
            $table->decimal('price', 14, 2)->nullable();
            $table->boolean('negotiable')->default(false);
            $table->string('currency', 3)->nullable();
            $table->json('photos')->nullable();
            $table->json('location')->nullable();
            $table->string('contact_name')->nullable();
            $table->text('contact_phone')->nullable();
            $table->text('contact_email')->nullable();
            $table->string('status', 16)->default('draft');
            $table->text('reject_reason')->nullable();
            // Stop words that sent it to the moderators.
            $table->json('flags')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedSmallInteger('renewals')->default(0);
            // Paid placement: raised_at orders the list, the others end.
            $table->timestamp('raised_at')->nullable();
            $table->timestamp('highlighted_until')->nullable();
            $table->timestamp('vip_until')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('phone_views')->default(0);
            $table->json('custom')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status', 'raised_at'], 'classified_ads_list');
            $table->index(['status', 'expires_at'], 'classified_ads_expiry');
            $table->index(['status', 'price'], 'classified_ads_price');
        });

        Schema::create('classified_favourites', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreignId('ad_id')->index();
            $table->timestamp('created_at')->nullable();

            $table->unique(['user_id', 'ad_id']);
        });

        Schema::create('classified_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ad_id')->index();
            // One thread per ad and buyer; the seller side is whoever owns
            // the ad (a person, or any member of the company).
            $table->unsignedBigInteger('buyer_id')->index();
            $table->unsignedBigInteger('sender_id');
            $table->boolean('from_seller')->default(false);
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['ad_id', 'buyer_id']);
        });

        Schema::create('classified_saved_searches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name');
            $table->string('locale', 12)->nullable();
            $table->json('filters');
            $table->boolean('notify')->default(true);
            $table->unsignedBigInteger('last_ad_id')->default(0);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('classified_complaints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ad_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            // A hash of the address, to count one person once.
            $table->string('fingerprint', 64);
            $table->string('reason', 32);
            $table->text('text')->nullable();
            $table->string('status', 16)->default('new');
            $table->timestamps();

            $table->unique(['ad_id', 'fingerprint']);
        });

        Schema::create('classified_stop_words', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('word', 100);
            $table->timestamps();
        });

        Schema::create('classified_boosts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ad_id')->index();
            $table->unsignedBigInteger('account_user_id');
            $table->string('type', 16);
            $table->unsignedSmallInteger('days');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status', 16)->default('pending');
            // The order that paid for it, in the shop or elsewhere.
            $table->string('reference', 100)->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['boosts', 'stop_words', 'complaints', 'saved_searches', 'messages', 'favourites', 'ads', 'categories', 'cities', 'regions'] as $table) {
            Schema::dropIfExists('classified_'.$table);
        }
    }
};
