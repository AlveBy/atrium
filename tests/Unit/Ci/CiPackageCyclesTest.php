<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Unit\Ci;

use Atrium\Ci\Ci;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * bin/ci fails the lint step when packages require each other, so a cycle
 * cannot come back after it has been broken.
 */
final class CiPackageCyclesTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/atrium-ci-cycles-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->root));
    }

    /**
     * @param  array<string, list<string>>  $requires  folder => the folders it requires
     */
    private function repository(array $requires): void
    {
        foreach ($requires as $folder => $dependencies) {
            mkdir("{$this->root}/packages/{$folder}/tests", 0777, true);
            file_put_contents("{$this->root}/packages/{$folder}/composer.json", (string) json_encode([
                'name' => "alveby/atrium-{$folder}",
                'require' => array_fill_keys(array_map(static fn (string $dependency): string => "alveby/atrium-{$dependency}", $dependencies), '^0.1'),
            ]));
        }
    }

    /**
     * @return list<array{string, string, bool, float}>
     */
    private function results(): array
    {
        $ci = new Ci($this->root);
        (new ReflectionMethod($ci, 'packageCycles'))->invoke($ci);

        /** @var list<array{string, string, bool, float}> */
        return (new ReflectionProperty($ci, 'results'))->getValue($ci);
    }

    public function test_a_cycle_fails_the_lint_step_and_names_its_packages(): void
    {
        $this->repository(['news' => ['theme'], 'theme' => ['news'], 'pages' => ['theme']]);

        $this->assertSame(
            [['lint', 'packages require each other, so none can be installed alone: news, theme', false, 0.0]],
            $this->results(),
        );
    }

    public function test_every_cycle_is_a_failure_of_its_own(): void
    {
        $this->repository(['a' => ['b'], 'b' => ['a'], 'c' => ['d'], 'd' => ['c']]);

        $this->assertCount(2, $this->results());
    }

    public function test_packages_that_only_stack_up_pass(): void
    {
        $this->repository(['theme' => [], 'news' => ['theme'], 'pages' => ['theme', 'news']]);

        $this->assertSame([], $this->results());
    }
}
