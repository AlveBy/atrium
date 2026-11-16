<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Unit\Ci;

use Atrium\Ci\Baseline;
use Atrium\Ci\Ci;
use Atrium\Ci\CiException;
use Atrium\Ci\Clover;
use Atrium\Ci\Diff;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The pieces of the coverage gate: changed lines from git, hits from Clover,
 * and the baseline that only goes up.
 */
final class CoverageGateTest extends TestCase
{
    private const CLOVER = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <coverage generated="1">
          <project timestamp="1">
            <package name="Atrium\Faq">
              <file name="/repo/packages/faq/src/Question.php">
                <class name="Question"><metrics/></class>
                <line num="10" type="method" name="answer" count="1"/>
                <line num="12" type="stmt" count="3"/>
                <line num="13" type="stmt" count="0"/>
                <line num="14" type="stmt" count="1"/>
              </file>
            </package>
            <file name="/repo/packages/faq/src/Empty.php"/>
            <file name="/elsewhere/Other.php">
              <line num="1" type="stmt" count="0"/>
            </file>
          </project>
        </coverage>
        XML;

    public function test_diff_gives_the_added_and_rewritten_lines_of_the_new_version(): void
    {
        $diff = <<<'DIFF'
            diff --git a/packages/faq/src/Question.php b/packages/faq/src/Question.php
            index 1..2 100644
            --- a/packages/faq/src/Question.php
            +++ b/packages/faq/src/Question.php
            @@ -12 +12 @@ class Question
            -        return 1;
            +        return 2;
            @@ -20,0 +21,3 @@ class Question
            +    a
            +    b
            +    c
            @@ -30,2 +33,0 @@ class Question
            -    gone
            -    gone
            diff --git a/packages/faq/src/Gone.php b/packages/faq/src/Gone.php
            deleted file mode 100644
            --- a/packages/faq/src/Gone.php
            +++ /dev/null
            @@ -1,3 +0,0 @@
            -<?php
            diff --git a/app/New.php b/app/New.php
            new file mode 100644
            --- /dev/null
            +++ b/app/New.php
            @@ -0,0 +1,2 @@
            +<?php
            +// ü 日本 עברית
            DIFF;

        $this->assertSame([
            'packages/faq/src/Question.php' => [12, 21, 22, 23],
            'app/New.php' => [1, 2],
        ], Diff::addedLines($diff));
    }

    public function test_an_empty_diff_changes_nothing(): void
    {
        $this->assertSame([], Diff::addedLines(''));
    }

    public function test_clover_keeps_statements_relative_to_the_root(): void
    {
        $clover = Clover::fromXml(self::CLOVER, '/repo/');

        $this->assertSame([
            'packages/faq/src/Question.php' => [12 => 3, 13 => 0, 14 => 1],
            'packages/faq/src/Empty.php' => [],
            '/elsewhere/Other.php' => [1 => 0],
        ], $clover->files);
        $this->assertSame(['covered' => 2, 'total' => 4, 'percent' => 50.0], $clover->totals());
    }

    public function test_a_file_that_is_not_clover_is_refused(): void
    {
        $this->expectException(RuntimeException::class);

        Clover::fromXml('not xml at all', '/repo');
    }

    /**
     * @return array<string, array{array<string, list<int>>, array<string, list<int>>, int}>
     */
    public static function changedLines(): array
    {
        $file = 'packages/faq/src/Question.php';

        return [
            'nothing changed' => [[], [], 0],
            'a covered line' => [[$file => [12]], [], 1],
            'an uncovered line' => [[$file => [13]], [$file => [13]], 1],
            'comments and blank lines do not count' => [[$file => [1, 2, 3, 10, 11]], [], 0],
            'a mix' => [[$file => [11, 12, 13, 14, 15]], [$file => [13]], 3],
            'a file without statements' => [['packages/faq/src/Empty.php' => [1, 2]], [], 0],
            'a file outside the report' => [['packages/faq/src/Unknown.php' => [1]], [], 0],
        ];
    }

    /**
     * @param  array<string, list<int>>  $changed
     * @param  array<string, list<int>>  $uncovered
     */
    #[DataProvider('changedLines')]
    public function test_only_executable_changed_lines_must_be_covered(array $changed, array $uncovered, int $executable): void
    {
        $clover = Clover::fromXml(self::CLOVER, '/repo');

        $this->assertSame($uncovered, $clover->uncovered($changed));
        $this->assertSame($executable, $clover->executable($changed));
    }

    /**
     * @return array<string, array{int, int, float}>
     */
    public static function percentages(): array
    {
        return [
            'nothing to cover' => [0, 0, 100.0],
            'all' => [7, 7, 100.0],
            'none' => [0, 7, 0.0],
            'rounded down, never up' => [2, 3, 66.66],
            'one line short of all is not 100' => [99999, 100000, 99.99],
            'half' => [1, 2, 50.0],
        ];
    }

    #[DataProvider('percentages')]
    public function test_percent_is_rounded_down_to_two_decimals(int $covered, int $total, float $percent): void
    {
        $this->assertSame($percent, Clover::percent($covered, $total));
    }

    /**
     * @return array<string, array{array{float, int}|null, float, int, string}>
     */
    public static function ratchet(): array
    {
        return [
            'first measurement' => [null, 42.5, 10, 'new'],
            'one more line without a test' => [[80.0, 10], 80.5, 11, 'fell'],
            'a larger share but more lines without a test' => [[80.0, 10], 90.0, 11, 'fell'],
            'the same' => [[80.0, 10], 80.0, 10, 'same'],
            'covered code deleted' => [[80.0, 10], 79.0, 10, 'same'],
            'one line fewer without a test' => [[80.0, 10], 80.0, 9, 'rose'],
            'more covered code' => [[80.0, 10], 80.01, 10, 'rose'],
            'from nothing to cover' => [[100.0, 0], 100.0, 0, 'same'],
        ];
    }

    /**
     * @param  array{float, int}|null  $stored
     */
    #[DataProvider('ratchet')]
    public function test_the_lines_without_a_test_never_grow(?array $stored, float $percent, int $uncovered, string $verdict): void
    {
        $dir = sys_get_temp_dir().'/atrium-baseline-'.bin2hex(random_bytes(4));
        $baseline = new Baseline($dir);

        try {
            if ($stored !== null) {
                $baseline->set('faq', ...$stored);
            }

            $this->assertSame($stored === null ? null : ['percent' => $stored[0], 'uncovered' => $stored[1]], $baseline->get('faq'));
            $this->assertSame($verdict, $baseline->compare('faq', $percent, $uncovered));
            $this->assertNull($baseline->get('catalog'));
        } finally {
            shell_exec('rm -rf '.escapeshellarg($dir));
        }
    }

    public function test_the_baseline_file_reads_as_a_sentence(): void
    {
        $dir = sys_get_temp_dir().'/atrium-baseline-'.bin2hex(random_bytes(4));

        try {
            (new Baseline($dir))->set('faq', 87.5, 120);

            $this->assertSame("87.50% of lines, 120 not covered\n", file_get_contents("{$dir}/faq"));
            $this->assertSame(['percent' => 87.5, 'uncovered' => 120], (new Baseline($dir))->get('faq'));
        } finally {
            shell_exec('rm -rf '.escapeshellarg($dir));
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function brokenBaselines(): array
    {
        return [
            'empty' => [''],
            'only the percentage' => ['87.50'],
            'words' => ['high'],
        ];
    }

    #[DataProvider('brokenBaselines')]
    public function test_a_baseline_that_does_not_read_is_refused(string $content): void
    {
        $dir = sys_get_temp_dir().'/atrium-baseline-'.bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents("{$dir}/faq", $content);

        try {
            $this->expectException(CiException::class);

            (new Baseline($dir))->get('faq');
        } finally {
            shell_exec('rm -rf '.escapeshellarg($dir));
        }
    }

    /**
     * @return array<string, array{list<int>, string}>
     */
    public static function ranges(): array
    {
        return [
            'one' => [[5], '5'],
            'a run' => [[3, 4, 5], '3-5'],
            'runs and singles' => [[9, 3, 4, 5, 11, 12], '3-5, 9, 11-12'],
            'none' => [[], ''],
        ];
    }

    /**
     * @param  list<int>  $numbers
     */
    #[DataProvider('ranges')]
    public function test_uncovered_lines_are_reported_as_ranges(array $numbers, string $text): void
    {
        $this->assertSame($text, Ci::ranges($numbers));
    }
}
