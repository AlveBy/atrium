<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Ci;

/**
 * The test targets of the monorepo: every packages/<name> with a tests folder,
 * and "app", the demo application with tests/Unit and tests/Feature.
 *
 * A change to a package is tested in the package and in every package that
 * requires it (require or require-dev), so a change to the admin runs all of
 * them. A change outside packages/ — composer.lock, phpunit.xml, the CI itself —
 * may break anything and runs everything.
 */
final class Packages
{
    public const APP = 'app';

    /**
     * Files outside packages/ that only the "app" target depends on.
     */
    private const APP_PATHS = '#^(app|bootstrap|config|database|resources|routes|tests)/#';

    /**
     * Files that change nothing a test can see.
     */
    private const INERT = '#(\.md$|^docs/|^LICENSE$|^\.gitignore$|^\.gitattributes$|^\.editorconfig$|^PLAN\.ru\.md$)#';

    /**
     * @param  array<string, list<string>>  $requires  package => the packages it requires
     */
    public function __construct(private readonly array $requires) {}

    public static function discover(string $root): self
    {
        $names = [];

        foreach (glob($root.'/packages/*/composer.json') ?: [] as $file) {
            $folder = basename(dirname($file));
            $json = json_decode((string) file_get_contents($file), true);

            if (is_dir(dirname($file).'/tests') && is_array($json) && isset($json['name'])) {
                $names[(string) $json['name']] = ['folder' => $folder, 'json' => $json];
            }
        }

        $requires = [];

        foreach ($names as $package) {
            $deps = array_keys(($package['json']['require'] ?? []) + ($package['json']['require-dev'] ?? []));
            $requires[$package['folder']] = array_values(array_map(
                static fn (string $name): string => $names[$name]['folder'],
                array_filter($deps, static fn (string $name): bool => isset($names[$name])),
            ));
        }

        ksort($requires);

        return new self($requires);
    }

    /**
     * Every target, packages first in alphabetical order, then "app".
     *
     * @return list<string>
     */
    public function all(): array
    {
        return [...array_keys($this->requires), self::APP];
    }

    /**
     * The targets a set of changed files can break.
     *
     * @param  list<string>  $files  paths relative to the repository root
     * @return list<string>
     */
    public function affectedBy(array $files): array
    {
        $hit = [];

        foreach ($files as $file) {
            if (preg_match(self::INERT, $file) === 1) {
                continue;
            }

            if (preg_match('#^packages/([^/]+)/#', $file, $match) === 1) {
                if (isset($this->requires[$match[1]])) {
                    $hit[$match[1]] = true;
                    // The demo application installs every package.
                    $hit[self::APP] = true;
                }

                continue;
            }

            if (preg_match(self::APP_PATHS, $file) === 1) {
                $hit[self::APP] = true;

                continue;
            }

            return $this->all();
        }

        // A package that requires an affected one is affected too; repeat until
        // no new package joins, so a cycle in the requirements ends the loop.
        do {
            $before = count($hit);

            foreach ($this->requires as $target => $dependencies) {
                if (! isset($hit[$target]) && array_intersect($dependencies, array_keys($hit)) !== []) {
                    $hit[$target] = true;
                }
            }
        } while (count($hit) > $before);

        return array_values(array_filter($this->all(), static fn (string $target): bool => isset($hit[$target])));
    }

    /**
     * The folder whose tests the target runs and the source it covers.
     *
     * @return array{tests: list<string>, source: list<string>}
     */
    public static function paths(string $target): array
    {
        return $target === self::APP
            ? ['tests' => ['tests/Unit', 'tests/Feature'], 'source' => ['app']]
            : ['tests' => ["packages/{$target}/tests"], 'source' => ["packages/{$target}/src"]];
    }

    /**
     * The target a source file belongs to, or null for files no target covers.
     */
    public static function targetOf(string $file): ?string
    {
        if (preg_match('#^packages/([^/]+)/src/.+\.php$#', $file, $match) === 1) {
            return $match[1];
        }

        return preg_match('#^app/.+\.php$#', $file) === 1 ? self::APP : null;
    }
}
