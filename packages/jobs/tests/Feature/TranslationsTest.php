<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Tests\Feature;

use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;

/**
 * Every admin language has every line English has, and no other, and none
 * of them is empty.
 */
final class TranslationsTest extends TestCase
{
    /** As packages/admin/tests/Unit/TranslationsTest.php. */
    private const PLURAL_FORMS = [
        'en' => 2, 'de' => 2, 'es' => 2, 'it' => 2, 'pt_BR' => 2, 'fr' => 2,
        'ru' => 3, 'uk' => 3, 'be' => 3, 'pl' => 3,
        'ja' => 1, 'zh_CN' => 1, 'tr' => 1, 'kk' => 1, 'uz' => 1,
    ];

    public function test_every_language_has_the_english_keys(): void
    {
        $dir = dirname(__DIR__, 2).'/lang';
        $english = array_keys(Arr::dot(require $dir.'/en/jobs.php'));
        $languages = array_values(array_diff(scandir($dir) ?: [], ['.', '..']));
        $admin = array_values(array_diff(scandir(dirname(__DIR__, 3).'/admin/lang') ?: [], ['.', '..']));

        $this->assertSame($admin, $languages);
        $this->assertCount(15, $languages);

        foreach ($languages as $language) {
            $lines = Arr::dot(require $dir.'/'.$language.'/jobs.php');

            $this->assertSame($english, array_keys($lines), $language);
            $this->assertNotContains('', $lines, $language);
        }
    }

    public function test_placeholders_and_plural_forms_fit_each_language(): void
    {
        $dir = dirname(__DIR__, 2).'/lang';
        $english = Arr::dot(require $dir.'/en/jobs.php');
        $placeholders = static function (string $line): array {
            preg_match_all('/:([a-z_]+)/', $line, $matches);
            $names = array_values(array_unique($matches[1]));
            sort($names);

            return $names;
        };

        foreach (self::PLURAL_FORMS as $language => $forms) {
            foreach (Arr::dot(require $dir.'/'.$language.'/jobs.php') as $key => $line) {
                $this->assertSame($placeholders($english[$key]), $placeholders($line), "{$language}: {$key}");

                if (str_contains($english[$key], '|')) {
                    $this->assertCount($forms, explode('|', $line), "{$language}: {$key} needs {$forms} plural form(s)");
                } else {
                    $this->assertStringNotContainsString('|', $line, "{$language}: {$key} is not a plural string");
                }
            }
        }
    }

    public function test_the_views_have_no_hard_coded_text(): void
    {
        foreach (glob(dirname(__DIR__, 2).'/resources/views/{,*/}*.blade.php', GLOB_BRACE) ?: [] as $view) {
            // Text between tags once comments, styles, Blade echoes and directives are gone.
            $html = preg_replace(['/\{\{--.*?--\}\}/s', '/<style\b.*?<\/style>/s', '/<script\b.*?<\/script>/s', '/@php.*?@endphp/s', '/\{\{.*?\}\}/s', '/\{!!.*?!!\}/s', '/@\w+(\s*\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\))?/'], '', (string) file_get_contents($view));
            preg_match_all('/>([^<]*)</', (string) $html, $matches);

            foreach ($matches[1] as $text) {
                $plain = trim((string) preg_replace('/&[a-z#0-9]+;/i', '', $text));

                $this->assertDoesNotMatchRegularExpression('/\p{L}{2,}/u', $plain, basename($view).': "'.$plain.'"');
            }
        }
    }
}
