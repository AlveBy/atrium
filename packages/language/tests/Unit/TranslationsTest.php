<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class TranslationsTest extends TestCase
{
    /** The admin's languages: every package speaks all of them. */
    public const LANGUAGES = ['be', 'de', 'en', 'es', 'fr', 'it', 'ja', 'kk', 'pl', 'pt_BR', 'ru', 'tr', 'uk', 'uz', 'zh_CN'];

    public function test_it_has_exactly_the_admin_languages(): void
    {
        $this->assertSame(self::LANGUAGES, array_map(static fn (string $file): string => basename(dirname($file)), glob(__DIR__.'/../../lang/*/errors.php') ?: []));
    }

    /**
     * @return iterable<string,array{string}>
     */
    public static function languages(): iterable
    {
        foreach (glob(__DIR__.'/../../lang/*/errors.php') ?: [] as $file) {
            yield basename(dirname($file)) => [$file];
        }
    }

    /** The order of the placeholders may differ: languages order their words differently. */
    #[DataProvider('languages')]
    public function test_every_language_has_the_english_keys_and_placeholders(string $file): void
    {
        $english = self::flatten(require __DIR__.'/../../lang/en/errors.php');
        $lines = self::flatten(require $file);

        $this->assertSame(array_keys($english), array_keys($lines));
        $this->assertNotContains('', $lines);

        foreach ($english as $key => $line) {
            $this->assertSame(self::placeholders($line), self::placeholders($lines[$key]), $key);
        }
    }

    public function test_every_problem_the_code_names_has_a_sentence(): void
    {
        $english = self::flatten(require __DIR__.'/../../lang/en/errors.php');
        $named = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../../src', RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            preg_match_all("/'((?:syntax|arguments|formula|ir|check|value|limit)\\.[a-z_]+)'/", (string) file_get_contents($file->getPathname()), $found);
            $named = [...$named, ...$found[1]];
        }

        $named = array_unique($named);
        sort($named);
        $sentences = array_values(array_filter(array_keys($english), static fn (string $key): bool => $key !== 'at' && ! str_starts_with($key, 'types.')));
        sort($sentences);

        // Both ways: no code without a sentence, no sentence nothing says.
        $this->assertSame($sentences, $named);
    }

    /** @return list<string> */
    private static function placeholders(string $line): array
    {
        preg_match_all('/:[a-z_]+/', $line, $found);
        $placeholders = array_unique($found[0]);
        sort($placeholders);

        return $placeholders;
    }

    /**
     * @param  array<string,mixed>  $lines
     * @return array<string,string>
     */
    private static function flatten(array $lines, string $prefix = ''): array
    {
        $flat = [];

        foreach ($lines as $key => $value) {
            if (is_array($value)) {
                $flat = [...$flat, ...self::flatten($value, $prefix.$key.'.')];
            } else {
                $flat[$prefix.$key] = (string) $value;
            }
        }

        return $flat;
    }
}
