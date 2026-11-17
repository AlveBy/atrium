<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * The size of a layer's picture in pixels: the space its areas are drawn in.
 *
 * The picture is a path on the uploads disk (as the image field keeps it)
 * or an absolute path under public/. SVG has no pixels: its width and height
 * attributes, or else its viewBox, are taken instead.
 */
final class ImageSize
{
    /**
     * @return array{0:int,1:int}|null
     */
    public static function of(?string $path): ?array
    {
        if ($path === null || trim($path) === '' || preg_match('#^(https?:)?//#i', $path) === 1) {
            return null;
        }

        $contents = self::read($path);

        if ($contents === null || $contents === '') {
            return null;
        }

        if (str_contains(substr($contents, 0, 512), '<svg') || str_ends_with(strtolower($path), '.svg')) {
            return self::svg($contents);
        }

        $size = @getimagesizefromstring($contents);

        return is_array($size) && $size[0] > 0 && $size[1] > 0 ? [(int) $size[0], (int) $size[1]] : null;
    }

    /**
     * @return array{0:int,1:int}|null
     */
    public static function svg(string $contents): ?array
    {
        if (preg_match('/<svg\b[^>]*>/is', $contents, $tag) !== 1) {
            return null;
        }

        $attribute = static function (string $name) use ($tag): ?float {
            if (preg_match('/\s'.$name.'\s*=\s*["\']\s*([0-9.]+)\s*(px)?\s*["\']/i', $tag[0], $match) === 1) {
                return (float) $match[1];
            }

            return null;
        };

        $width = $attribute('width');
        $height = $attribute('height');

        if (($width === null || $height === null)
            && preg_match('/\sviewBox\s*=\s*["\']\s*[-0-9.]+[\s,]+[-0-9.]+[\s,]+([0-9.]+)[\s,]+([0-9.]+)\s*["\']/i', $tag[0], $box) === 1) {
            $width = (float) $box[1];
            $height = (float) $box[2];
        }

        return $width !== null && $height !== null && $width >= 1 && $height >= 1
            ? [(int) round($width), (int) round($height)]
            : null;
    }

    private static function read(string $path): ?string
    {
        try {
            if (str_starts_with($path, '/')) {
                $file = public_path(ltrim($path, '/'));

                return is_file($file) ? (string) file_get_contents($file) : null;
            }

            $disk = Storage::disk((string) config('atrium.uploads.disk', 'public'));

            return $disk->exists($path) ? $disk->get($path) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
