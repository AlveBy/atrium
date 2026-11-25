<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Ci;

/**
 * The notes of the next release, one file per change in
 * changelog/{section}/{name}.md, so pull requests do not conflict over the
 * same lines of CHANGELOG.md. A release moves them into CHANGELOG.md
 * (scripts/changelog.php); the lint step of bin/ci checks them.
 */
final class Changelog
{
    /** The Keep a Changelog sections, folder => heading, in the order they are printed. */
    public const SECTIONS = [
        'added' => 'Added',
        'changed' => 'Changed',
        'deprecated' => 'Deprecated',
        'removed' => 'Removed',
        'fixed' => 'Fixed',
        'security' => 'Security',
    ];

    private const COMPARE = 'https://github.com/AlveBy/atrium/compare/';

    /**
     * Each section's entries, files in name order, the text of each trimmed.
     *
     * @return array<string, list<string>>
     */
    public static function entries(string $root): array
    {
        $entries = [];

        foreach (array_keys(self::SECTIONS) as $section) {
            foreach (glob("{$root}/changelog/{$section}/*.md") ?: [] as $file) {
                $entries[$section][] = trim((string) file_get_contents($file));
            }
        }

        return $entries;
    }

    /**
     * What is wrong with the entries and with the [Unreleased] section of
     * CHANGELOG.md, one line each; empty when nothing is.
     *
     * @return list<string>
     */
    public static function problems(string $root): array
    {
        $problems = [];

        foreach (glob("{$root}/changelog/*") ?: [] as $path) {
            $name = basename($path);

            if (! is_dir($path)) {
                if ($name !== 'README.md') {
                    $problems[] = "changelog/{$name}: an entry goes into a section folder, changelog/{section}/{$name}";
                }

                continue;
            }

            if (! isset(self::SECTIONS[$name])) {
                $problems[] = "changelog/{$name}: no such section, use ".implode(', ', array_keys(self::SECTIONS));

                continue;
            }

            foreach (glob("{$path}/*") ?: [] as $file) {
                $entry = "changelog/{$name}/".basename($file);

                if (! str_ends_with($file, '.md')) {
                    $problems[] = "{$entry}: an entry is a Markdown file, name.md";
                } elseif (! str_starts_with(trim((string) file_get_contents($file)), '- ')) {
                    $problems[] = "{$entry}: an entry is a list item, it starts with \"- \"";
                }
            }
        }

        $changelog = is_file("{$root}/CHANGELOG.md") ? (string) file_get_contents("{$root}/CHANGELOG.md") : '';

        if (preg_match('/^## \[Unreleased\]\n(.*?)^## \[/ms', $changelog, $match) === 1
            && preg_match('/^(- |### )/m', $match[1]) === 1) {
            $problems[] = 'CHANGELOG.md: [Unreleased] takes no entries, put each into changelog/{section}/{name}.md';
        }

        return $problems;
    }

    /**
     * CHANGELOG.md with the entries as the section of $version, dated $date,
     * and the compare links moved on to it.
     *
     * @param  array<string, list<string>>  $entries  section => entries
     */
    public static function release(string $changelog, array $entries, string $version, string $date): string
    {
        if (preg_match('/^\d+\.\d+\.\d+(-[0-9A-Za-z.]+)?$/', $version) !== 1) {
            throw new CiException("'{$version}' is not a version, such as 0.3.0");
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            throw new CiException("'{$date}' is not a date, such as 2026-12-01");
        }

        if ($entries === []) {
            throw new CiException('changelog/ has no entries to release');
        }

        if (str_contains($changelog, "\n## [{$version}]")) {
            throw new CiException("CHANGELOG.md already has {$version}");
        }

        $link = '/^\[Unreleased\]: '.preg_quote(self::COMPARE, '/').'v(\S+)\.\.\.HEAD$/m';

        if (preg_match($link, $changelog, $match) !== 1) {
            throw new CiException('CHANGELOG.md has no [Unreleased] compare link');
        }

        $start = strpos($changelog, "\n## [Unreleased]\n");
        $next = $start === false ? false : strpos($changelog, "\n## [", $start + 1);

        if ($next === false) {
            throw new CiException('CHANGELOG.md has no [Unreleased] section followed by a release');
        }

        $section = "\n## [{$version}] - {$date}\n";

        foreach (self::SECTIONS as $folder => $heading) {
            if (isset($entries[$folder])) {
                $section .= "\n### {$heading}\n".implode("\n", $entries[$folder])."\n";
            }
        }

        $links = '[Unreleased]: '.self::COMPARE."v{$version}...HEAD\n[{$version}]: ".self::COMPARE."v{$match[1]}...v{$version}";

        return str_replace($match[0], $links, substr($changelog, 0, $next).$section.substr($changelog, $next));
    }
}
