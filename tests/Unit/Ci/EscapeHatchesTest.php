<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Unit\Ci;

use Atrium\Ci\EscapeHatches;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EscapeHatchesTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/atrium-escape-hatches-'.bin2hex(random_bytes(4));
        mkdir($this->root.'/packages/shop/src/Models', 0777, true);
        mkdir($this->root.'/packages/shop/tests', 0777, true);
        mkdir($this->root.'/app', 0777, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->root));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function annotations(): array
    {
        return [
            'a line out of coverage' => ['    // @codeCoverageIgnore'],
            'a block out of coverage' => ['    // @codeCoverageIgnoreStart'],
            'a method out of coverage' => ['     * @codeCoverageIgnore'],
            'a method out of mutation' => ['     * @infection-ignore-all'],
            'one mutator out of mutation' => ['    // @infection-ignore'],
        ];
    }

    #[DataProvider('annotations')]
    public function test_an_annotation_in_a_package_is_found_with_its_line(string $annotation): void
    {
        file_put_contents($this->root.'/packages/shop/src/Models/Order.php', "<?php\n\nfinal class Order\n{\n{$annotation}\n}\n");

        $this->assertSame(['packages/shop/src/Models/Order.php:5'], EscapeHatches::find($this->root));
    }

    #[DataProvider('annotations')]
    public function test_an_annotation_in_the_app_is_found(string $annotation): void
    {
        file_put_contents($this->root.'/app/User.php', "<?php\n{$annotation}\n");

        $this->assertSame(['app/User.php:2'], EscapeHatches::find($this->root));
    }

    public function test_every_annotation_is_listed_sorted(): void
    {
        file_put_contents($this->root.'/packages/shop/src/Models/Order.php', "<?php\n// @codeCoverageIgnoreStart\n// @codeCoverageIgnoreEnd\n");
        file_put_contents($this->root.'/app/User.php', "<?php\n// @infection-ignore-all\n");

        $this->assertSame(['app/User.php:2', 'packages/shop/src/Models/Order.php:2', 'packages/shop/src/Models/Order.php:3'], EscapeHatches::find($this->root));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function allowed(): array
    {
        return [
            'clean code' => ['packages/shop/src/Models/Order.php', "<?php\n\nfinal class Order {}\n"],
            'a word that only looks alike' => ['packages/shop/src/Models/Order.php', "<?php\n// codeCoverage is measured by bin/ci\n"],
            'tests may mention it' => ['packages/shop/tests/OrderTest.php', "<?php\n// @codeCoverageIgnore\n"],
            'not a PHP file' => ['packages/shop/src/Models/notes.md', "@codeCoverageIgnore\n"],
        ];
    }

    #[DataProvider('allowed')]
    public function test_nothing_else_is_found(string $path, string $content): void
    {
        file_put_contents($this->root.'/'.$path, $content);

        $this->assertSame([], EscapeHatches::find($this->root));
    }

    public function test_a_checkout_without_an_app_folder_is_scanned(): void
    {
        rmdir($this->root.'/app');
        file_put_contents($this->root.'/packages/shop/src/Models/Order.php', "<?php\n// @codeCoverageIgnore\n");

        $this->assertSame(['packages/shop/src/Models/Order.php:2'], EscapeHatches::find($this->root));
    }

    public function test_the_repository_has_none(): void
    {
        $this->assertSame([], EscapeHatches::find(dirname(__DIR__, 3)));
    }
}
