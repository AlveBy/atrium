<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Unit\Ci;

use Atrium\Ci\Packages;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PackagesTest extends TestCase
{
    private Packages $packages;

    protected function setUp(): void
    {
        // admin ← catalog ← shop, admin ← theme ← pages, admin ← faq
        $this->packages = new Packages([
            'admin' => [],
            'catalog' => ['admin'],
            'faq' => ['admin'],
            'pages' => ['admin', 'theme'],
            'shop' => ['catalog'],
            'theme' => ['admin'],
        ]);
    }

    public function test_every_target_is_listed_with_the_app_last(): void
    {
        $this->assertSame(['admin', 'catalog', 'faq', 'pages', 'shop', 'theme', 'app'], $this->packages->all());
    }

    /**
     * @return array<string, array{list<string>, list<string>}>
     */
    public static function changes(): array
    {
        return [
            'nothing' => [[], []],
            'documentation only' => [['README.md', 'docs/testing.md', 'packages/faq/README.md', 'CHANGELOG.md', 'PLAN.ru.md'], []],
            'a leaf package' => [['packages/faq/src/Models/Question.php'], ['faq', 'app']],
            'a package and its dependants' => [['packages/catalog/src/Models/Product.php'], ['catalog', 'shop', 'app']],
            'a dependency two levels down' => [['packages/theme/resources/views/layout.blade.php'], ['pages', 'theme', 'app']],
            'the admin breaks everything' => [['packages/admin/src/Fields/Field.php'], ['admin', 'catalog', 'faq', 'pages', 'shop', 'theme', 'app']],
            'a test of a package' => [['packages/shop/tests/Feature/CartTest.php'], ['shop', 'app']],
            'the demo application' => [['app/Models/User.php', 'tests/Unit/ExampleTest.php', 'routes/web.php'], ['app']],
            'a folder that is not a package' => [['packages/notes/todo.txt'], []],
            'the lock file breaks everything' => [['composer.lock'], ['admin', 'catalog', 'faq', 'pages', 'shop', 'theme', 'app']],
            'the CI itself' => [['.github/workflows/tests.yml'], ['admin', 'catalog', 'faq', 'pages', 'shop', 'theme', 'app']],
            'bin/ci' => [['bin/ci'], ['admin', 'catalog', 'faq', 'pages', 'shop', 'theme', 'app']],
            'unicode file name' => [['packages/faq/lang/ja/ファイル.php'], ['faq', 'app']],
        ];
    }

    /**
     * @param  list<string>  $files
     * @param  list<string>  $expected
     */
    #[DataProvider('changes')]
    public function test_a_change_runs_the_targets_it_can_break(array $files, array $expected): void
    {
        $this->assertSame($expected, $this->packages->affectedBy($files));
    }

    public function test_a_cycle_in_the_requirements_does_not_loop(): void
    {
        $packages = new Packages(['a' => ['b'], 'b' => ['a'], 'c' => []]);

        $this->assertSame(['a', 'b', 'app'], $packages->affectedBy(['packages/a/src/X.php']));
        $this->assertSame(['c', 'app'], $packages->affectedBy(['packages/c/src/X.php']));
    }

    public function test_packages_that_all_require_each_other_are_walked_once(): void
    {
        // Twelve packages in a ring of requirements and a thirteenth that nobody
        // requires: walking every path of the ring takes hours.
        $names = array_map(static fn (int $i): string => 'p'.$i, range(1, 12));
        $requires = ['solo' => []];

        foreach ($names as $name) {
            $requires[$name] = array_values(array_diff($names, [$name]));
        }

        $packages = new Packages($requires);

        $this->assertSame(['solo', 'app'], $packages->affectedBy(['packages/solo/src/X.php']));
        $this->assertSame([...$names, 'app'], $packages->affectedBy(['packages/p7/src/X.php']));
    }

    public function test_packages_that_require_nothing_in_a_circle_have_no_cycles(): void
    {
        $this->assertSame([], $this->packages->cycles());
        $this->assertSame([], (new Packages([]))->cycles());
    }

    /**
     * @return array<string, array{array<string, list<string>>, list<list<string>>}>
     */
    public static function cycles(): array
    {
        return [
            'two packages that require each other, sorted' => [['b' => ['a'], 'a' => ['b']], [['a', 'b']]],
            'a ring of four, found through the closure' => [['a' => ['b'], 'b' => ['c'], 'c' => ['d'], 'd' => ['a']], [['a', 'b', 'c', 'd']]],
            'a package that only needs the ring is not in it' => [['a' => ['b'], 'b' => ['a'], 'tail' => ['a']], [['a', 'b']]],
            'a package that the ring only needs is not in it' => [['a' => ['b', 'leaf'], 'b' => ['a'], 'leaf' => []], [['a', 'b']]],
            'two rings, in the order of their names' => [['z' => ['y'], 'y' => ['z'], 'b' => ['a'], 'a' => ['b']], [['a', 'b'], ['y', 'z']]],
            'two rings joined by one way are still two' => [['a' => ['b', 'c'], 'b' => ['a'], 'c' => ['d'], 'd' => ['c']], [['a', 'b'], ['c', 'd']]],
            'a package that requires itself' => [['a' => ['a']], [['a']]],
            'a requirement that is not a package of its own' => [['a' => ['ghost'], 'b' => ['a']], []],
            'a diamond is not a cycle' => [['top' => ['left', 'right'], 'left' => ['base'], 'right' => ['base'], 'base' => []], []],
        ];
    }

    /**
     * @param  array<string, list<string>>  $requires
     * @param  list<list<string>>  $expected
     */
    #[DataProvider('cycles')]
    public function test_a_cycle_is_a_group_of_packages_that_reach_each_other(array $requires, array $expected): void
    {
        $this->assertSame($expected, (new Packages($requires))->cycles());
    }

    public function test_no_package_of_the_repository_requires_itself_back(): void
    {
        $this->assertSame([], Packages::discover(dirname(__DIR__, 3))->cycles());
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function sources(): array
    {
        return [
            'package class' => ['packages/catalog/src/Models/Product.php', 'catalog'],
            'nested folder' => ['packages/admin/src/Http/Controllers/AdminController.php', 'admin'],
            'app class' => ['app/Providers/AppServiceProvider.php', 'app'],
            'package test' => ['packages/catalog/tests/TestCase.php', null],
            'package view' => ['packages/theme/src/views/a.blade.txt', null],
            'migration' => ['packages/catalog/database/migrations/x.php', null],
            'root file' => ['bin/ci', null],
            'similar prefix' => ['application/x.php', null],
        ];
    }

    #[DataProvider('sources')]
    public function test_a_source_file_belongs_to_its_target(string $file, ?string $target): void
    {
        $this->assertSame($target, Packages::targetOf($file));
    }

    public function test_the_paths_of_a_package_and_of_the_app(): void
    {
        $this->assertSame(['tests' => ['packages/faq/tests'], 'source' => ['packages/faq/src']], Packages::paths('faq'));
        $this->assertSame(['tests' => ['tests/Unit', 'tests/Feature'], 'source' => ['app']], Packages::paths('app'));
    }

    public function test_discover_reads_the_packages_and_their_requirements(): void
    {
        $root = sys_get_temp_dir().'/atrium-ci-'.bin2hex(random_bytes(4));
        $make = static function (string $folder, array $json, bool $tests = true) use ($root): void {
            mkdir("{$root}/packages/{$folder}".($tests ? '/tests' : ''), 0777, true);
            file_put_contents("{$root}/packages/{$folder}/composer.json", (string) json_encode($json));
        };

        $make('admin', ['name' => 'alveby/atrium', 'require' => ['php' => '^8.3']]);
        $make('catalog', ['name' => 'alveby/atrium-catalog', 'require' => ['alveby/atrium' => '^0.1'], 'require-dev' => ['alveby/atrium-brands' => '^0.1']]);
        $make('brands', ['name' => 'alveby/atrium-brands', 'require' => ['alveby/atrium' => '^0.1', 'vendor/other' => '^1']]);
        $make('untested', ['name' => 'alveby/atrium-untested'], tests: false);
        $make('broken', ['description' => 'no name']);

        try {
            $packages = Packages::discover($root);

            $this->assertSame(['admin', 'brands', 'catalog', 'app'], $packages->all());
            $this->assertSame(['brands', 'catalog', 'app'], $packages->affectedBy(['packages/brands/src/Brand.php']));
        } finally {
            shell_exec('rm -rf '.escapeshellarg($root));
        }
    }
}
