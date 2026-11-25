<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * Moves the notes in changelog/ into CHANGELOG.md for a release:
 *
 *   php scripts/changelog.php 0.3.0             dated today
 *   php scripts/changelog.php 0.3.0 2026-12-01
 *
 * It writes the section of the version, moves the compare links on and
 * deletes the entry files; commit what it changed (CONTRIBUTING.md).
 */

use Atrium\Ci\Changelog;
use Atrium\Ci\CiException;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$problems = Changelog::problems($root);

if ($problems !== []) {
    fwrite(STDERR, implode("\n", $problems)."\n");
    exit(1);
}

try {
    $changelog = Changelog::release(
        (string) file_get_contents($root.'/CHANGELOG.md'),
        Changelog::entries($root),
        $argv[1] ?? '',
        $argv[2] ?? date('Y-m-d'),
    );
} catch (CiException $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    exit(1);
}

file_put_contents($root.'/CHANGELOG.md', $changelog);

foreach (array_keys(Changelog::SECTIONS) as $section) {
    array_map('unlink', glob("{$root}/changelog/{$section}/*.md") ?: []);

    if (is_dir("{$root}/changelog/{$section}")) {
        rmdir("{$root}/changelog/{$section}");
    }
}

echo "CHANGELOG.md has {$argv[1]} now; the entries are deleted.\n";
