<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Where what 1C sends is kept until it is imported: files of its names (and
 * folders, import_files/…) under one folder of the private disk. A big file
 * arrives in chunks that are appended; a zip is opened when the import asks.
 */
final class Inbox
{
    private const DIR = 'atrium-1c';

    /**
     * A name 1C may use: segments of plain characters, no way out of the folder.
     */
    public function valid(string $name): bool
    {
        foreach (explode('/', $name) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || preg_match('/[\x00-\x1f\\\\:*?"<>|]/u', $segment) !== 0) {
                return false;
            }
        }

        return true;
    }

    public function reset(): void
    {
        Storage::disk('local')->deleteDirectory(self::DIR);
    }

    public function append(string $name, string $content): void
    {
        $path = $this->path($name);

        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, $content, FILE_APPEND | LOCK_EX);
    }

    public function has(string $name): bool
    {
        return $this->valid($name) && is_file($this->path($name));
    }

    public function path(string $name): string
    {
        return Storage::disk('local')->path(self::DIR.'/'.$name);
    }

    /**
     * Opens every zip that came and deletes it, so a repeated call finds nothing to do.
     */
    public function extract(): void
    {
        foreach (Storage::disk('local')->allFiles(self::DIR) as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'zip') {
                continue;
            }

            $this->unzip($file);
            Storage::disk('local')->delete($file);
        }
    }

    /** The zip is closed when this ends, before the file is deleted. */
    private function unzip(string $file): void
    {
        $zip = new ZipArchive;

        if ($zip->open(Storage::disk('local')->path($file)) !== true) {
            throw new RuntimeException('The zip archive '.basename($file).' cannot be opened.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $this->unpack($zip, $i);
        }
    }

    private function unpack(ZipArchive $zip, int $index): void
    {
        $name = $zip->getNameIndex($index) ?: '';

        if (str_ends_with($name, '/')) {
            return;
        }

        if (! $this->valid($name)) {
            throw new RuntimeException('The zip archive holds a file with a forbidden name: '.$name);
        }

        $path = $this->path($name);

        File::ensureDirectoryExists(dirname($path));
        $stream = $zip->getStream($zip->getNameIndex($index));
        file_put_contents($path, $stream);
    }
}
