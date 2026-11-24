<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Blocks;

use Atrium\Admin\Embeds\Embeds;
use Atrium\Admin\Modules\Modules;
use Atrium\Admin\Support\Sites;
use Atrium\Blocks\Models\Block;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Str;

/**
 * The blocks of the current site as a template or a text draws them.
 *
 * A block is looked up once per site, language and code and kept in the
 * cache; saving or deleting any block changes a version key that every cache
 * entry carries, so the next page reads the new text. This works on every
 * cache store, also those without tags (file, database).
 *
 * What is cached is the block as written. A form or another block inside it
 * is put in its place on every request, after the cache.
 */
final class Blocks
{
    private const VERSION_KEY = 'atrium-blocks:version';

    private const TTL = 3600;

    /** @var array<int,string> codes being drawn now, to stop a block that holds itself */
    private array $drawing = [];

    public function __construct(
        private readonly Cache $cache,
        private readonly Sites $sites,
        private readonly Modules $modules,
        private readonly Embeds $embeds,
    ) {}

    /**
     * The block with this code, in the current language and ready to print;
     * the empty string when the site has no such block, it is switched off,
     * the module is not installed, or it holds itself.
     */
    public function html(string $code): string
    {
        if (in_array($code, $this->drawing, true)) {
            return '';
        }

        $this->drawing[] = $code;

        try {
            return $this->embeds->render($this->written($code));
        } finally {
            array_pop($this->drawing);
        }
    }

    /**
     * Forget every block read so far: one has been saved or deleted.
     */
    public function flush(): void
    {
        $this->cache->forever(self::VERSION_KEY, Str::random(12));
    }

    private function written(string $code): string
    {
        if (! $this->modules->isInstalled('alveby/atrium-blocks')) {
            return '';
        }

        $version = $this->cache->rememberForever(self::VERSION_KEY, static fn (): string => Str::random(12));
        $key = implode(':', ['atrium-blocks', $this->sites->current()->id, app()->getLocale(), md5($code), $version]);

        // An array: a missing block would not be told apart from a miss.
        $block = $this->cache->remember($key, self::TTL, static fn (): array => ['html' => (string) Block::query()->where('code', $code)->where('active', true)->first()?->content]);

        return $block['html'];
    }
}
