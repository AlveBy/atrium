<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The README in 15 languages: English in the root, the rest in docs/readme.
 * Translations drift, so the parts that must not (the language switcher,
 * links and every command) are checked against English.
 */
final class ReadmeTest extends TestCase
{
    /** Code → name in the switcher, in the switcher's order. */
    private const LANGUAGES = [
        'en' => 'English',
        'ru' => 'Русский',
        'uk' => 'Українська',
        'be' => 'Беларуская',
        'kk' => 'Қазақша',
        'uz' => 'Oʻzbekcha',
        'de' => 'Deutsch',
        'fr' => 'Français',
        'es' => 'Español',
        'pt-BR' => 'Português (Brasil)',
        'it' => 'Italiano',
        'pl' => 'Polski',
        'tr' => 'Türkçe',
        'zh-CN' => '简体中文',
        'ja' => '日本語',
    ];

    /**
     * @return array<string,array{string}>
     */
    public static function languages(): array
    {
        $cases = [];

        foreach (array_keys(self::LANGUAGES) as $code) {
            $cases[$code] = [$code];
        }

        return $cases;
    }

    #[DataProvider('languages')]
    public function test_the_switcher_lists_every_language_and_marks_its_own(string $code): void
    {
        $items = [];

        foreach (self::LANGUAGES as $other => $name) {
            $items[] = $other === $code ? "**{$name}**" : "[{$name}](".$this->link($code, $other).')';
        }

        $this->assertStringContainsString(implode(' · ', $items), $this->read($code));
    }

    #[DataProvider('languages')]
    public function test_every_relative_link_and_picture_exists(string $code): void
    {
        preg_match_all('/\]\(([^)\s]+)\)/', $this->read($code), $matches);

        $dir = dirname($this->path($code));

        foreach ($matches[1] as $target) {
            if (preg_match('#^(https?:|mailto:|\#)#', $target)) {
                continue;
            }

            $this->assertFileExists($dir.'/'.explode('#', $target)[0], "README.{$code}: broken link {$target}");
        }
    }

    #[DataProvider('languages')]
    public function test_commands_and_code_are_the_same_as_in_english(string $code): void
    {
        $this->assertSame($this->codeBlocks('en'), $this->codeBlocks($code), "README.{$code}: code blocks differ from English");
    }

    #[DataProvider('languages')]
    public function test_it_has_as_many_pictures_and_sections_as_english(string $code): void
    {
        $count = fn (string $pattern, string $lang): int => preg_match_all($pattern, $this->read($lang));

        $this->assertSame($count('/!\[[^\]]*\]\(/', 'en'), $count('/!\[[^\]]*\]\(/', $code), "README.{$code}: pictures");
        $this->assertSame($count('/^## /m', 'en'), $count('/^## /m', $code), "README.{$code}: sections");
    }

    #[DataProvider('languages')]
    public function test_it_lists_the_same_packages_in_the_same_order_as_english(string $code): void
    {
        $this->assertSame($this->packages($this->read('en')), $this->packages($this->read($code)), "README.{$code}: the package table differs from English");
    }

    #[DataProvider('languages')]
    public function test_it_links_the_same_guides_in_the_same_order_as_english(string $code): void
    {
        $this->assertSame($this->guides($this->read('en')), $this->guides($this->read($code)), "README.{$code}: the list of guides differs from English");
    }

    public function test_the_package_and_guide_lists_are_read_from_english(): void
    {
        $english = $this->read('en');

        $this->assertContains('alveby/atrium packages/admin', $this->packages($english));
        $this->assertContains('alveby/atrium-wordpress packages/wordpress', $this->packages($english));
        $this->assertGreaterThan(20, count($this->packages($english)));
        $this->assertContains('installation', $this->guides($english));
        $this->assertContains('social-login', $this->guides($english));
        $this->assertGreaterThan(15, count($this->guides($english)));
    }

    public function test_a_package_row_missing_from_a_translation_is_noticed(): void
    {
        $english = $this->read('en');
        $without = implode("\n", array_filter(explode("\n", $english), fn (string $line): bool => ! str_contains($line, '`alveby/atrium-seo`')));

        $this->assertSame(['alveby/atrium-seo packages/seo'], array_values(array_diff($this->packages($english), $this->packages($without))));
        $this->assertNotSame($this->packages($english), $this->packages($without));
    }

    public function test_a_package_row_in_the_wrong_place_or_with_the_wrong_folder_is_noticed(): void
    {
        $english = $this->read('en');

        $this->assertNotSame($this->packages($english), $this->packages(str_replace('(packages/seo/README.md)', '(packages/news/README.md)', $english)));
        $this->assertNotSame($this->packages($english), array_reverse($this->packages($english)));
    }

    public function test_a_guide_missing_from_a_translation_is_noticed(): void
    {
        $english = $this->read('en');
        $without = implode("\n", array_filter(explode("\n", $english), fn (string $line): bool => ! str_contains($line, '(docs/wordpress.md)')));

        $this->assertSame(['wordpress'], array_values(array_diff($this->guides($english), $this->guides($without))));
    }

    public function test_guides_are_read_from_every_link_style_the_translations_use(): void
    {
        $this->assertSame(['seo', 'cache', 'bitrix'], $this->guides("- [A](docs/seo.md)\n- [B](../cache.md)\n- [C](../bitrix.ru.md)\n- [Not a guide](https://example.com/x.md)\n[Loose](docs/ignored.md)\n"));
    }

    private function path(string $code): string
    {
        $root = dirname(__DIR__, 2);

        return $code === 'en' ? $root.'/README.md' : $root."/docs/readme/README.{$code}.md";
    }

    private function read(string $code): string
    {
        $this->assertFileExists($this->path($code));

        return (string) file_get_contents($this->path($code));
    }

    private function link(string $from, string $to): string
    {
        if ($to === 'en') {
            return '../../README.md';
        }

        return $from === 'en' ? "docs/readme/README.{$to}.md" : "README.{$to}.md";
    }

    /**
     * @return array<int,string>
     */
    private function codeBlocks(string $code): array
    {
        preg_match_all('/^```[a-z]*\n.*?^```$/ms', $this->read($code), $matches);

        return $matches[0];
    }

    /**
     * The package table: name and folder of every row, in order.
     *
     * @return list<string>
     */
    private function packages(string $markdown): array
    {
        preg_match_all('#^\| \[`(alveby/[a-z0-9-]+)`\]\((?:\.\./)*packages/([a-z0-9-]+)#m', $markdown, $matches, PREG_SET_ORDER);

        return array_map(fn (array $match): string => "{$match[1]} packages/{$match[2]}", $matches);
    }

    /**
     * The guides under "Documentation", by file name, in order, whatever the language of the file.
     *
     * @return list<string>
     */
    private function guides(string $markdown): array
    {
        preg_match_all('#^- \[[^\]]+\]\((?:\.\./|docs/)([a-z0-9-]+)(?:\.ru)?\.md\)#m', $markdown, $matches);

        return $matches[1];
    }
}
