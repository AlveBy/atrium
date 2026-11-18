<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Unit\Ci;

use Atrium\Ci\Ci;
use Atrium\Ci\CiException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * The --shard option of bin/ci: what it accepts, what it refuses, how it says so.
 */
final class CiShardOptionTest extends TestCase
{
    /**
     * @param  list<string>  $argv
     */
    private function parse(array $argv): Ci
    {
        $ci = new Ci(dirname(__DIR__, 3));
        (new ReflectionMethod($ci, 'parse'))->invoke($ci, $argv);

        return $ci;
    }

    /**
     * Runs bin/ci. A package that does not exist stops it right after the
     * arguments, so a parser that wrongly accepts them cannot start a real run.
     *
     * @return array{int, string, string} exit code, stdout, stderr
     */
    private function bin(string ...$args): array
    {
        $process = proc_open(
            [PHP_BINARY, dirname(__DIR__, 3).'/bin/ci', '--package=no-such-package', ...$args],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        $this->assertIsResource($process);

        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $out, $err];
    }

    public function test_shard_is_not_set_without_the_option(): void
    {
        $shard = new ReflectionProperty(Ci::class, 'shard');

        $this->assertNull($shard->getValue($this->parse(['coverage'])));
    }

    public function test_shard_option_is_parsed_into_this_shard_and_how_many_there_are(): void
    {
        $shard = new ReflectionProperty(Ci::class, 'shard');

        $this->assertSame([2, 4], $shard->getValue($this->parse(['--shard=2/4', 'mutation'])));
        $this->assertSame([10, 12], $shard->getValue($this->parse(['mutation', '--shard=10/12'])));
    }

    public function test_shard_goes_with_the_changed_lines_and_the_other_options(): void
    {
        $this->parse(['--shard=1/2', '--changed', '--jobs=2', '--base=main', 'coverage', 'mutation']);
        $this->parse(['--all-lines', 'mutation']);

        $this->addToAssertionCount(1);
    }

    /**
     * @return array<string, array{list<string>}>
     */
    public static function badShards(): array
    {
        return [
            'zero' => [['--shard=0/4']],
            'past the count' => [['--shard=5/4']],
            'no count' => [['--shard=1/']],
            'no value' => [['--shard=']],
            'a space' => [['--shard=1 /4']],
            'a leading zero' => [['--shard=01/4']],
            'a name of the option without the value' => [['--shard']],
        ];
    }

    /**
     * @param  list<string>  $argv
     */
    #[DataProvider('badShards')]
    public function test_a_bad_shard_is_refused_while_parsing(array $argv): void
    {
        $this->expectException(CiException::class);

        $this->parse($argv);
    }

    public function test_shard_does_not_go_with_all_lines_in_either_order(): void
    {
        foreach ([['--shard=1/2', '--all-lines'], ['--all-lines', '--shard=1/2']] as $argv) {
            try {
                $this->parse([...$argv, 'mutation']);
                $this->fail('--shard with --all-lines was accepted: '.implode(' ', $argv));
            } catch (CiException $e) {
                $this->assertSame('--shard splits the changed lines; it does not go with --all-lines', $e->getMessage());
            }
        }
    }

    public function test_bin_ci_exits_with_2_and_prints_the_usage_on_a_bad_shard(): void
    {
        foreach (['0/4', '5/4', 'x', ''] as $value) {
            [$code, $out, $err] = $this->bin("--shard={$value}", 'mutation');

            $this->assertSame(2, $code, "--shard={$value}");
            $this->assertSame('', $out);
            $this->assertStringContainsString("--shard takes I/N with 1 ≤ I ≤ N, got {$value}", $err);
            $this->assertStringContainsString('Usage: bin/ci', $err);
        }
    }

    public function test_bin_ci_exits_with_2_and_prints_the_usage_on_shard_with_all_lines(): void
    {
        [$code, $out, $err] = $this->bin('--shard=1/2', '--all-lines', 'mutation');

        $this->assertSame(2, $code);
        $this->assertSame('', $out);
        $this->assertStringContainsString('--shard splits the changed lines; it does not go with --all-lines', $err);
        $this->assertStringContainsString('Usage: bin/ci', $err);
    }

    public function test_help_lists_the_shard_option(): void
    {
        [$code, $out, $err] = $this->bin('--help');

        $this->assertSame(0, $code);
        $this->assertSame('', $err);
        $this->assertStringContainsString('--shard=I/N', $out);
        $this->assertStringContainsString("this machine's part of N", $out);
    }

    public function test_help_with_a_good_shard_is_still_help(): void
    {
        [$code, $out, $err] = $this->bin('--shard=1/2', '--help');

        $this->assertSame(0, $code);
        $this->assertSame('', $err);
        $this->assertStringContainsString('Usage: bin/ci', $out);
    }
}
