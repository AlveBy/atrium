<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Database;

use NumberFormatter;

/**
 * What the demo needs to know about the country of the site: its currency,
 * land prices and whether land is measured in square metres or feet. Picked
 * by the installer's locale; the made-up prices are only believable.
 * The admin's DemoLocale will hold such facts for every module; until it
 * does they are here, in one place.
 */
final class DemoCountry
{
    /** Locale => [intl locale, currency, price of a square metre of land, imperial units]. */
    private const FACTS = [
        'en' => ['en_US', 'USD', 270, true],
        'ru' => ['ru_RU', 'RUB', 5000, false],
        'uk' => ['uk_UA', 'UAH', 1200, false],
        'be' => ['be_BY', 'BYN', 60, false],
        'kk' => ['kk_KZ', 'KZT', 15000, false],
        'uz' => ['uz_UZ', 'UZS', 400000, false],
        'de' => ['de_DE', 'EUR', 250, false],
        'fr' => ['fr_FR', 'EUR', 180, false],
        'es' => ['es_ES', 'EUR', 150, false],
        'pt_BR' => ['pt_BR', 'BRL', 600, false],
        'it' => ['it_IT', 'EUR', 160, false],
        'pl' => ['pl_PL', 'PLN', 400, false],
        'tr' => ['tr_TR', 'TRY', 6000, false],
        'zh_CN' => ['zh_CN', 'CNY', 3000, false],
        'ja' => ['ja_JP', 'JPY', 80000, false],
    ];

    private function __construct(
        public readonly string $locale,
        private readonly string $intl,
        private readonly string $currency,
        private readonly int $pricePerSquareMetre,
        private readonly bool $imperial,
    ) {}

    public static function for(?string $locale): self
    {
        $locale = self::normalise($locale);

        return new self($locale, ...self::FACTS[$locale]);
    }

    /**
     * pt-br, pt_BR and PT-BR are one locale; an unknown one is English.
     */
    public static function normalise(?string $locale): string
    {
        $locale = str_replace('-', '_', trim((string) $locale));

        foreach (array_keys(self::FACTS) as $known) {
            if (strcasecmp($known, $locale) === 0) {
                return $known;
            }
        }

        return 'en';
    }

    /**
     * The words of the demo in this country's language.
     *
     * @return array<string,mixed>
     */
    public function texts(): array
    {
        $path = dirname(__DIR__, 2).'/database/demo/';

        return is_file($path.$this->locale.'.php') ? require $path.$this->locale.'.php' : require $path.'en.php';
    }

    /**
     * "476 m²", or "5,124 sq ft" where land is measured in feet.
     */
    public function area(float $squareMetres): string
    {
        return $this->imperial
            ? $this->number($squareMetres * 10.7639).' sq ft'
            : $this->number($squareMetres).' m²';
    }

    /**
     * The price of a lot of that size, rounded the way a price list is.
     */
    public function lotPrice(float $squareMetres): string
    {
        $amount = $squareMetres * $this->pricePerSquareMetre;
        $step = 10 ** max(0, (int) floor(log10(max($amount, 1))) - 1);
        $amount = round($amount / $step) * $step;

        if (class_exists(NumberFormatter::class)) {
            $format = new NumberFormatter($this->intl, NumberFormatter::CURRENCY);
            $format->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);

            return (string) $format->formatCurrency($amount, $this->currency);
        }

        return number_format($amount, 0, '.', ' ').' '.$this->currency;
    }

    private function number(float $value): string
    {
        if (class_exists(NumberFormatter::class)) {
            return (string) (new NumberFormatter($this->intl, NumberFormatter::DECIMAL))->format(round($value));
        }

        return number_format(round($value), 0, '.', ' ');
    }
}
