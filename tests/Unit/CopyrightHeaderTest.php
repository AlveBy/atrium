<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Every Blade view, Vue, JavaScript, CSS, shell and YAML file carries the
 * copyright header; PHP files get it from Pint (pint.json).
 */
final class CopyrightHeaderTest extends TestCase
{
    public function test_every_source_file_pint_does_not_format_has_the_header(): void
    {
        exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__, 2).'/scripts/copyright-header.php'), $output, $status);

        $this->assertSame(0, $status, implode("\n", $output));
    }
}
