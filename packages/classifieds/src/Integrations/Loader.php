<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Integrations;

use Atrium\Admin\Modules\Modules;
use Atrium\Classifieds\Support\Accounts;
use Atrium\Classifieds\Support\Checkout;
use Atrium\Classifieds\Support\Properties;
use Atrium\Classifieds\Support\PropertySource;
use Atrium\Classifieds\Support\Schema;
use Atrium\Shop\Cart\Purchasables;
use Illuminate\Contracts\Foundation\Application;

/**
 * Wires the board to the modules that are installed next to it: category
 * properties from custom fields, payment through the shop's checkout, the
 * sections of the personal cabinet, schema.org through the SEO module.
 * Each bridge is touched only when its module's classes are there.
 */
final class Loader
{
    public static function boot(Application $app): void
    {
        $modules = $app->make(Modules::class);

        if (class_exists('Atrium\\CustomFields\\CustomFields') && $modules->isInstalled(Properties::MODULE)) {
            $app->singleton(PropertySource::class, CustomFieldsBridge::class);
        }

        if (class_exists('Atrium\\Shop\\Cart\\Purchasables') && $modules->isInstalled('alveby/atrium-shop')) {
            $app->singleton(Checkout::class, ShopCheckout::class);
            $app->make(Purchasables::class)->register('classifieds', ShopCheckout::class);
        }

        if ($app->make(Accounts::class)->hasCabinet()) {
            $app->make(AccountBridge::class)->registerSections();
        }

        if (class_exists('Atrium\\Seo\\SeoPage') && $modules->isInstalled(Schema::SEO)) {
            $app->singleton(SeoBridge::class);
            $app->make(Schema::class)->useSeo($app->make(SeoBridge::class));
        }
    }
}
