<?php

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
}
