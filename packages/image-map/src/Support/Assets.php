<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Support;

/**
 * The script and the styles of the map, served from the package (no build
 * step on the site, no publishing). Each address carries a hash of the
 * file, so browsers keep it for a year and fetch it again after an update.
 */
final class Assets
{
    public const FILES = ['image-map.js', 'image-map.css', 'editor.js', 'editor.css'];

    private bool $printed = false;

    public static function path(string $file): string
    {
        return dirname(__DIR__, 2).'/resources/dist/'.$file;
    }

    public static function version(string $file): string
    {
        $path = self::path($file);

        return is_file($path) ? substr((string) md5_file($path), 0, 10) : '0';
    }

    public static function url(string $file): string
    {
        return route('atrium-image-map.asset', ['version' => self::version($file), 'file' => $file]);
    }

    /**
     * True the first time in a request: only the first map on a page brings
     * the tags.
     */
    public function once(): bool
    {
        if ($this->printed) {
            return false;
        }

        return $this->printed = true;
    }
}
