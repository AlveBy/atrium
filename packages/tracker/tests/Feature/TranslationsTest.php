<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Tracker\Tests\TestCase;

/**
 * Every language of the panel has every line of the module, with the same
 * :placeholders.
 */
final class TranslationsTest extends TestCase
{
    /**
     * @param  array<string,mixed>  $lines
     * @return array<string,string>
     */
    private function flat(array $lines, string $prefix = ''): array
    {
        $flat = [];

        foreach ($lines as $key => $value) {
            if (is_array($value)) {
                $flat += $this->flat($value, $prefix.$key.'.');
            } else {
                $flat[$prefix.$key] = $value;
            }
        }

        return $flat;
    }

    /**
     * @return list<string>
     */
    private function placeholders(string $line): array
    {
        preg_match_all('/:[a-z]+/', $line, $found);
        sort($found[0]);

        return $found[0];
    }

    public function test_the_module_speaks_the_fifteen_languages_of_the_panel(): void
    {
        $locales = array_map('basename', glob(__DIR__.'/../../lang/*', GLOB_ONLYDIR) ?: []);
        $this->assertCount(15, $locales);

        $english = $this->flat(require __DIR__.'/../../lang/en/tracker.php');
        $this->assertNotEmpty($english);

        foreach ($locales as $locale) {
            $lines = $this->flat(require __DIR__."/../../lang/{$locale}/tracker.php");

            $this->assertSame(array_keys($english), array_keys($lines), $locale);

            foreach ($lines as $key => $line) {
                $this->assertNotSame('', trim($line), "{$locale} {$key}");
                $this->assertSame($this->placeholders($english[$key]), $this->placeholders($line), "{$locale} {$key}");
            }
        }
    }

    public function test_the_status_names_differ_in_every_language_but_not_within_one(): void
    {
        foreach (glob(__DIR__.'/../../lang/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $statuses = (require $dir.'/tracker.php')['statuses'];

            $this->assertCount(4, array_unique($statuses), basename($dir));
        }
    }
}
