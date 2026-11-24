<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Blocks;

use Atrium\Admin\Embeds\Embeds;
use Atrium\Admin\Hooks\Hooks;
use Atrium\Admin\Modules\Modules;
use Atrium\Blocks\Embeds\BlockEmbed;
use Atrium\Blocks\Models\Block;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

final class BlocksServiceProvider extends ServiceProvider
{
    public const MODULE = 'alveby/atrium-blocks';

    /**
     * The model and the migrations come from extra.atrium in composer.json,
     * once `atrium:module:install alveby/atrium-blocks` has run.
     */
    public function register(): void
    {
        $this->app->singleton(Blocks::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-blocks');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/atrium-blocks'),
            ], 'atrium-blocks-lang');
        }

        // In every template, installed or not: a site that switches the
        // module off gets an empty place, not the text of the directive.
        Blade::directive('block', static fn (string $expression): string => "<?php echo app(\\Atrium\\Blocks\\Blocks::class)->html({$expression}); ?>");

        $this->app->make(Hooks::class)->register(Embeds::REGISTRY, 'block', new BlockEmbed);

        // Required but not installed: no table, so nothing to forget.
        if (! $this->app->make(Modules::class)->isInstalled(self::MODULE)) {
            return;
        }

        $flush = fn () => $this->app->make(Blocks::class)->flush();

        Block::saved($flush);
        Block::deleted($flush);
    }
}
