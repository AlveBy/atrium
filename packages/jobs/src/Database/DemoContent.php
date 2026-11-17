<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Database;

/**
 * The demo's texts and the facts of its country, from resources/demo.
 *
 * The country facts (offices, people, phones, currency, how a salary is
 * stated) are read here only: when the admin's shared demo locale helper
 * lands, country() is the one place to switch to it.
 */
final class DemoContent
{
    /** The Atrium languages the demo is written in. */
    public const LOCALES = ['en', 'ru', 'uk', 'be', 'kk', 'uz', 'de', 'fr', 'es', 'pt_BR', 'it', 'pl', 'tr', 'zh_CN', 'ja'];

    /** @var array<string,array<string,mixed>> */
    private static array $files = [];

    /**
     * An Atrium locale for any spelling: "pt-br", "pt_BR", "de_AT" or "de"; a
     * language the demo is not written in is English.
     */
    public static function locale(?string $locale): string
    {
        $locale = str_replace('-', '_', trim((string) $locale));
        [$language, $region] = array_pad(explode('_', $locale, 2), 2, '');
        $candidate = strtolower($language).($region !== '' ? '_'.strtoupper($region) : '');

        foreach ([$candidate, strtolower($language)] as $try) {
            if (in_array($try, self::LOCALES, true)) {
                return $try;
            }
        }

        return match (strtolower($language)) {
            'pt' => 'pt_BR',
            'zh' => 'zh_CN',
            default => 'en',
        };
    }

    /**
     * The content language code a translation is kept under: "pt_BR" → "pt-br".
     */
    public static function language(string $locale): string
    {
        return strtolower(str_replace('_', '-', $locale));
    }

    /**
     * @return array{code:string,currency:string,domain:string,offices:array<int,array{name:string,street:string,postal_code:string,region:?string}>,contact:array{name:string,email:string,phone:string},salaries:array<string,?array{from:int,to?:int,period:string}>,applicants:array<int,array{name:string,email:string}>}
     */
    public static function country(string $locale): array
    {
        return self::file($locale)['country'];
    }

    /**
     * @return array{departments:array<string,string>,vacancies:array<string,array<string,string>>,templates:array<string,array{name:string,subject:string,body:string}>,messages:array<int,string>}
     */
    public static function texts(string $locale): array
    {
        return self::file($locale)['texts'];
    }

    /**
     * @return array<string,mixed>
     */
    private static function file(string $locale): array
    {
        $locale = self::locale($locale);

        return self::$files[$locale] ??= require dirname(__DIR__, 2).'/resources/demo/'.$locale.'.php';
    }
}
