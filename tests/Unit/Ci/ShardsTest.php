<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Unit\Ci;

use Atrium\Ci\CiException;
use Atrium\Ci\Shards;
use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ShardsTest extends TestCase
{
    /**
     * @return array<string, array{array<string, int>, int, list<list<string>>}>
     */
    public static function splits(): array
    {
        return [
            'an even split' => [['a' => 4, 'b' => 4, 'c' => 4, 'd' => 4], 2, [['a', 'c'], ['b', 'd']]],
            'the heaviest file goes first and alone, the small ones fill the other shard' => [
                ['a' => 1, 'b' => 1, 'c' => 1, 'd' => 1, 'e' => 4], 2, [['e'], ['a', 'b', 'c', 'd']],
            ],
            'each file goes to the lightest shard so far' => [
                ['f5' => 5, 'f4' => 4, 'f3a' => 3, 'f3b' => 3, 'f3c' => 3], 2, [['f3b', 'f5'], ['f3a', 'f3c', 'f4']],
            ],
            'three shards' => [
                ['a' => 9, 'b' => 8, 'c' => 7, 'd' => 6, 'e' => 5, 'f' => 4], 3, [['a', 'f'], ['b', 'e'], ['c', 'd']],
            ],
            'equal weights go to the lowest shard first, alphabetically' => [
                ['c' => 1, 'a' => 1, 'b' => 1], 3, [['a'], ['b'], ['c']],
            ],
            'a tie between shards goes to the lower one' => [
                ['a' => 3, 'b' => 1, 'c' => 1, 'd' => 1], 2, [['a'], ['b', 'c', 'd']],
            ],
            'a tie of the weights goes alphabetically' => [
                ['y' => 2, 'x' => 2, 'z' => 1], 2, [['x', 'z'], ['y']],
            ],
            'more shards than files leaves the last ones empty' => [
                ['a' => 2, 'b' => 1], 4, [['a'], ['b'], [], []],
            ],
            'one file and one shard' => [['only' => 7], 1, [['only']]],
            'no files leaves every shard empty' => [[], 3, [[], [], []]],
            'one shard gets everything' => [['b' => 1, 'c' => 5, 'a' => 3], 1, [['a', 'b', 'c']]],
            'files without lines still land somewhere' => [['a' => 0, 'b' => 0, 'c' => 2], 2, [['c'], ['a', 'b']]],
        ];
    }

    /**
     * @param  array<string, int>  $weights
     * @param  list<list<string>>  $expected
     */
    #[DataProvider('splits')]
    public function test_split_gives_the_files_of_each_shard(array $weights, int $count, array $expected): void
    {
        $this->assertSame($expected, Shards::split($weights, $count));
    }

    public function test_split_sorts_the_files_inside_each_shard_not_in_the_order_they_were_taken(): void
    {
        // z is taken first (heaviest), a and b after it.
        $this->assertSame([['a', 'b', 'z']], Shards::split(['z' => 5, 'a' => 1, 'b' => 1], 1));
        $this->assertSame([['z'], ['a', 'b']], Shards::split(['z' => 9, 'b' => 3, 'a' => 2], 2));
    }

    public function test_split_keeps_the_number_of_shards(): void
    {
        foreach ([1, 2, 5, 8] as $count) {
            $this->assertCount($count, Shards::split(['a' => 1, 'b' => 2, 'c' => 3], $count));
        }
    }

    /**
     * @return array<string, array{array<string, int>}>
     */
    public static function sameFilesInAnyOrder(): array
    {
        $files = [
            'packages/admin/src/Fields/Field.php' => 12,
            'packages/admin/src/Panel.php' => 7,
            'packages/catalog/src/Models/Product.php' => 7,
            'packages/catalog/src/Models/Category.php' => 3,
            'packages/faq/src/Question.php' => 7,
            'packages/shop/src/Cart.php' => 3,
            'packages/shop/src/Order.php' => 1,
            'packages/theme/src/Layout.php' => 3,
            'app/Models/User.php' => 12,
            'app/Http/Kernel.php' => 0,
        ];

        $byWeight = $files;
        asort($byWeight);

        return [
            'as written' => [$files],
            'reversed' => [array_reverse($files, true)],
            'lightest first' => [$byWeight],
            'heaviest first' => [array_reverse($byWeight, true)],
            'rotated' => [array_slice($files, 4, null, true) + array_slice($files, 0, 4, true)],
        ];
    }

    /**
     * @param  array<string, int>  $weights
     */
    #[DataProvider('sameFilesInAnyOrder')]
    public function test_split_does_not_depend_on_the_order_the_files_come_in(array $weights): void
    {
        $sorted = $weights;
        ksort($sorted);

        foreach ([2, 3, 4] as $count) {
            $this->assertSame(Shards::split($sorted, $count), Shards::split($weights, $count), "{$count} shards");
        }
    }

    public function test_split_puts_every_file_in_exactly_one_shard_and_sorts_each_shard(): void
    {
        $weights = [];
        foreach (range(1, 25) as $n) {
            $weights[sprintf('file%02d.php', $n)] = ($n * 7) % 11;
        }

        $shards = Shards::split($weights, 4);
        $all = array_merge(...$shards);

        $this->assertCount(25, $all);
        $this->assertSame(array_keys($weights), (function () use ($all): array {
            sort($all);

            return $all;
        })());

        foreach ($shards as $shard) {
            $sorted = $shard;
            sort($sorted);
            $this->assertSame($sorted, $shard);
        }
    }

    public function test_split_balances_the_lines_so_no_shard_is_far_above_the_average(): void
    {
        $weights = ['a' => 10, 'b' => 9, 'c' => 8, 'd' => 7, 'e' => 6, 'f' => 5, 'g' => 4, 'h' => 3, 'i' => 2, 'j' => 1];

        $loads = array_map(
            static fn (array $shard): int => array_sum(array_map(static fn (string $file): int => $weights[$file], $shard)),
            Shards::split($weights, 3),
        );

        // 55 lines over 3 shards: 18 or 19 each when the heaviest go first.
        $this->assertSame(55, array_sum($loads));
        $this->assertLessThanOrEqual(1, max($loads) - min($loads));
    }

    /**
     * @return array<string, array{string, array{int, int}}>
     */
    public static function validShards(): array
    {
        return [
            'the only shard' => ['1/1', [1, 1]],
            'the second of four' => ['2/4', [2, 4]],
            'the first of two' => ['1/2', [1, 2]],
            'the last of four' => ['4/4', [4, 4]],
            'two digits' => ['10/12', [10, 12]],
            'the last of twelve' => ['12/12', [12, 12]],
            'compared as numbers, not as text' => ['9/10', [9, 10]],
        ];
    }

    /**
     * @param  array{int, int}  $expected
     */
    #[DataProvider('validShards')]
    public function test_parse_gives_the_shard_and_the_count_as_numbers(string $value, array $expected): void
    {
        $this->assertSame($expected, Shards::parse($value));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidShards(): array
    {
        return [
            'the shard is zero' => ['0/4'],
            'the shard is past the count' => ['5/4'],
            'the shard is past the count by two digits' => ['10/9'],
            'the count is zero' => ['1/0'],
            'both are zero' => ['0/0'],
            'a leading zero on the shard' => ['01/4'],
            'a leading zero on the count' => ['1/04'],
            'a space before the slash' => ['1 /4'],
            'a space after the slash' => ['1/ 4'],
            'a space in front' => [' 1/4'],
            'a space at the end' => ['1/4 '],
            'letters' => ['a/b'],
            'nothing' => [''],
            'only a slash' => ['/'],
            'no count' => ['1/'],
            'no shard' => ['/4'],
            'no slash' => ['14'],
            'a third part' => ['1/4/2'],
            'a negative shard' => ['-1/4'],
            'a negative count' => ['1/-4'],
            'a plus sign' => ['+1/4'],
            'a fraction' => ['1.5/4'],
            'a colon instead of the slash' => ['1:4'],
            'text before' => ['x1/4'],
            'text after' => ['1/4x'],
            'a newline after' => ["1/4\n"],
            'the word of' => ['1 of 4'],
        ];
    }

    #[DataProvider('invalidShards')]
    public function test_parse_refuses_anything_but_i_slash_n_with_i_up_to_n(string $value): void
    {
        $this->expectException(CiException::class);
        $this->expectExceptionMessage("--shard takes I/N with 1 ≤ I ≤ N, got {$value}");

        Shards::parse($value);
    }

    /**
     * @return array<string, array{list<string>, list<string>, int, int, list<string>}>
     */
    public static function coverageShares(): array
    {
        $targets = ['admin', 'catalog', 'faq', 'pages', 'shop', 'theme', 'app'];

        return [
            'every third from the first' => [$targets, [], 1, 3, ['admin', 'pages', 'app']],
            'every third from the second' => [$targets, [], 2, 3, ['catalog', 'shop']],
            'every third from the third' => [$targets, [], 3, 3, ['faq', 'theme']],
            'one machine measures everything' => [$targets, [], 1, 1, $targets],
            'a mutated target comes along when it is not its turn, in the order of the targets' => [
                $targets, ['admin'], 2, 3, ['admin', 'catalog', 'shop'],
            ],
            'several mutated targets, one of them already its turn' => [
                $targets, ['theme', 'catalog'], 2, 3, ['catalog', 'shop', 'theme'],
            ],
            'a mutated target that is not a target is ignored' => [$targets, ['nothing'], 3, 3, ['faq', 'theme']],
            'more machines than targets: the last ones measure nothing of their own' => [
                ['admin', 'faq', 'app'], [], 5, 5, [],
            ],
            'more machines than targets: the first ones take one each' => [
                ['admin', 'faq', 'app'], [], 2, 5, ['faq'],
            ],
            'a mutated target on a machine with no turn' => [['admin', 'faq', 'app'], ['app'], 5, 5, ['app']],
            'no targets' => [[], ['admin'], 1, 2, []],
            'numeric strings are compared as text, not as numbers' => [['1e1', '10'], ['10'], 5, 5, ['10']],
        ];
    }

    /**
     * @param  list<string>  $targets
     * @param  list<string>  $mutated
     * @param  list<string>  $expected
     */
    #[DataProvider('coverageShares')]
    public function test_coverage_targets_are_the_mutated_ones_and_every_nth_of_the_rest(array $targets, array $mutated, int $index, int $count, array $expected): void
    {
        $this->assertSame($expected, Shards::coverageTargets($targets, $mutated, $index, $count));
    }

    public function test_coverage_targets_are_measured_by_at_least_one_shard_and_never_twice_by_one(): void
    {
        $targets = ['admin', 'catalog', 'faq', 'pages', 'shop', 'theme', 'app'];

        foreach ([1, 2, 3, 4, 7, 8, 12] as $count) {
            $seen = [];

            foreach (range(1, $count) as $index) {
                $mine = Shards::coverageTargets($targets, [], $index, $count);

                $this->assertSame(array_values(array_unique($mine)), $mine, "no duplicates, shard {$index}/{$count}");

                foreach ($mine as $target) {
                    $seen[$target][] = $index;
                }
            }

            // Nothing mutated: every target is measured by exactly one shard.
            $found = array_keys($seen);
            sort($found);
            $expected = $targets;
            sort($expected);
            $this->assertSame($expected, $found, "{$count} shards");
            foreach ($seen as $target => $shards) {
                $this->assertCount(1, $shards, "{$target} of {$count} shards");
            }
        }
    }

    public function test_coverage_targets_are_measured_by_every_shard_that_mutates_them_and_still_by_someone(): void
    {
        $targets = ['admin', 'catalog', 'faq', 'pages', 'shop', 'theme', 'app'];

        foreach ([2, 3, 5, 9] as $count) {
            // Each shard mutates the targets whose turn is the next shard's.
            $measured = [];

            foreach (range(1, $count) as $index) {
                $mutated = array_values(array_filter($targets, static fn (string $t, int $pos): bool => $pos % $count === $index % $count, ARRAY_FILTER_USE_BOTH));
                $mine = Shards::coverageTargets($targets, $mutated, $index, $count);

                $this->assertSame(array_values(array_unique($mine)), $mine);
                $this->assertSame([], array_diff($mutated, $mine), "mutated targets of {$index}/{$count}");
                array_push($measured, ...$mine);
            }

            $this->assertSame([], array_diff($targets, $measured), "{$count} shards");
        }
    }

    public function test_coverage_targets_come_back_as_a_list(): void
    {
        $result = Shards::coverageTargets(['admin', 'catalog', 'faq', 'pages'], [], 2, 2);

        $this->assertSame(['catalog', 'pages'], $result);
        $this->assertSame([0, 1], array_keys($result));
    }

    /**
     * @return array<string, array{string, list<string>, list<string>}>
     */
    public static function excludedFiles(): array
    {
        return [
            'a file of a package' => ['packages/admin/src', ['packages/admin/src/Http/Foo.php'], ['#^Http/Foo\.php$#D']],
            'a file at the top of the source folder' => ['packages/faq/src', ['packages/faq/src/Question.php'], ['#^Question\.php$#D']],
            'a file of the demo application' => ['app', ['app/Models/User.php'], ['#^Models/User\.php$#D']],
            'several files keep their order' => [
                'packages/admin/src', ['packages/admin/src/B.php', 'packages/admin/src/A/C.php'], ['#^B\.php$#D', '#^A/C\.php$#D'],
            ],
            'a hash is quoted for the delimiter' => ['app', ['app/A#B.php'], ['#^A\#B\.php$#D']],
            'regular expression characters are quoted' => ['app', ['app/Foo+Bar(1)[x].php'], ['#^Foo\+Bar\(1\)\[x\]\.php$#D']],
            'no files' => ['app', [], []],
        ];
    }

    /**
     * @param  list<string>  $files
     * @param  list<string>  $expected
     */
    #[DataProvider('excludedFiles')]
    public function test_excludes_are_patterns_of_the_paths_relative_to_the_source_folder(string $source, array $files, array $expected): void
    {
        $this->assertSame($expected, Shards::excludes($source, $files));
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function excludeMatches(): array
    {
        return [
            'the file itself' => ['packages/admin/src', 'Http/Foo.php', true],
            'the same name in another folder' => ['packages/admin/src', 'Other/Http/Foo.php', false],
            'a longer name' => ['packages/admin/src', 'Http/Foo.php.bak', false],
            'a dot is a dot, not any character' => ['packages/admin/src', 'Http/FooXphp', false],
            'a newline after the name' => ['packages/admin/src', "Http/Foo.php\n", false],
            'the name with a prefix' => ['packages/admin/src', 'XHttp/Foo.php', false],
            'the full path from the repository root' => ['packages/admin/src', 'packages/admin/src/Http/Foo.php', false],
            'the slash left in front' => ['packages/admin/src', '/Http/Foo.php', false],
            'another file' => ['packages/admin/src', 'Http/Bar.php', false],
        ];
    }

    #[DataProvider('excludeMatches')]
    public function test_an_exclude_matches_exactly_the_one_file(string $source, string $path, bool $matches): void
    {
        [$pattern] = Shards::excludes($source, ['packages/admin/src/Http/Foo.php']);

        $this->assertSame($matches ? 1 : 0, preg_match($pattern, $path), $path);
    }

    public function test_an_exclude_of_the_demo_application_matches_exactly_the_one_file(): void
    {
        [$pattern] = Shards::excludes('app', ['app/Models/User.php']);

        $this->assertSame(1, preg_match($pattern, 'Models/User.php'));
        $this->assertSame(0, preg_match($pattern, 'app/Models/User.php'));
        $this->assertSame(0, preg_match($pattern, 'Models/Users.php'));
        $this->assertSame(0, preg_match($pattern, 'Other/Models/User.php'));
    }

    public function test_an_exclude_of_a_file_with_a_hash_is_a_valid_pattern_that_matches_only_it(): void
    {
        [$pattern] = Shards::excludes('app', ['app/A#B.php']);

        $this->assertSame(1, preg_match($pattern, 'A#B.php'));
        $this->assertSame(0, preg_match($pattern, 'AXB.php'));
        $this->assertSame(0, preg_match($pattern, 'AB.php'));
    }

    public function test_an_exclude_of_a_file_with_regular_expression_characters_matches_only_it(): void
    {
        [$pattern] = Shards::excludes('app', ['app/Foo+Bar(1).php']);

        $this->assertSame(1, preg_match($pattern, 'Foo+Bar(1).php'));
        $this->assertSame(0, preg_match($pattern, 'FooBar1.php'));
        $this->assertSame(0, preg_match($pattern, 'FooooBar(1).php'));
    }

    /**
     * @return array<string, array{string, string, list<string>, array<string, list<string>>}>
     */
    public static function infectionSources(): array
    {
        return [
            'the demo application, nothing excluded' => [
                '/repo', 'app', [], ['directories' => ['/repo/app']],
            ],
            'a package, nothing excluded' => [
                '/repo', 'packages/x/src', [], ['directories' => ['/repo/packages/x/src']],
            ],
            'a package, one file excluded' => [
                '/repo', 'packages/x/src', ['packages/x/src/Http/Foo.php'],
                ['directories' => ['/repo/packages/x/src'], 'excludes' => ['#^Http/Foo\.php$#D']],
            ],
            'a package, several files excluded in their order' => [
                '/repo', 'packages/x/src', ['packages/x/src/B.php', 'packages/x/src/A/C.php'],
                ['directories' => ['/repo/packages/x/src'], 'excludes' => ['#^B\.php$#D', '#^A/C\.php$#D']],
            ],
            'the demo application, one file excluded' => [
                '/work/atrium', 'app', ['app/Models/User.php'],
                ['directories' => ['/work/atrium/app'], 'excludes' => ['#^Models/User\.php$#D']],
            ],
        ];
    }

    /**
     * @param  list<string>  $excluded
     * @param  array<string, list<string>>  $expected
     */
    #[DataProvider('infectionSources')]
    public function test_source_is_the_directory_of_the_target_and_the_files_another_shard_mutates(string $root, string $source, array $excluded, array $expected): void
    {
        $this->assertSame($expected, Shards::source($root, $source, $excluded));
    }

    public function test_source_has_no_excludes_at_all_when_nothing_is_excluded(): void
    {
        $this->assertArrayNotHasKey('excludes', Shards::source('/repo', 'app', []));
        $this->assertSame(['directories' => ['/repo/app']], Shards::source('/repo', 'app', []));
    }

    public function test_source_has_the_excludes_when_a_file_is_excluded(): void
    {
        $this->assertArrayHasKey('excludes', Shards::source('/repo', 'app', ['app/A.php']));
    }

    /**
     * @return array<string, array{list<string>}>
     */
    public static function infectionConfigs(): array
    {
        return [
            'nothing excluded' => [[]],
            'one file excluded' => [['packages/x/src/Http/Foo.php']],
            'several files excluded' => [['packages/x/src/A.php', 'packages/x/src/B/C.php']],
        ];
    }

    /**
     * @param  list<string>  $excluded
     */
    #[DataProvider('infectionConfigs')]
    public function test_source_passes_the_json_schema_of_infection(array $excluded): void
    {
        $config = ['source' => Shards::source('/repo', 'packages/x/src', $excluded)];

        $this->assertSame([], self::schemaErrors($config));
    }

    public function test_the_json_schema_of_infection_refuses_an_empty_excludes_so_the_key_has_to_be_absent(): void
    {
        $config = ['source' => ['directories' => ['/repo/app'], 'excludes' => []]];

        $errors = self::schemaErrors($config);

        $this->assertNotSame([], $errors);
        $this->assertStringContainsString('minimum of 1 items', implode("\n", $errors));
    }

    /**
     * What Infection's SchemaValidator does with the config file.
     *
     * @param  array<string, mixed>  $config
     * @return list<string>
     */
    private static function schemaErrors(array $config): array
    {
        $contents = json_decode((string) json_encode($config));
        $validator = new Validator;
        $validator->validate(
            $contents,
            (object) ['$ref' => 'file://'.dirname(__DIR__, 3).'/vendor/infection/infection/resources/schema.json'],
        );

        return array_map(
            static fn (array $error): string => sprintf('[%s] %s', $error['property'], $error['message']),
            $validator->getErrors(),
        );
    }
}
