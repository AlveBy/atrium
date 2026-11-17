<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Support\ContentLanguages;
use Illuminate\Support\Number;

/**
 * The board's settings and addresses in one place.
 */
final class Board
{
    public const MODULE = 'alveby/atrium-classifieds';

    /**
     * A setting of the current site: what the administrator saved, else the
     * default from config/atrium-classifieds.php.
     */
    public function option(string $key): mixed
    {
        return Setting::get('classifieds.'.$key, config('atrium-classifieds.defaults.'.$key));
    }

    public function int(string $key): int
    {
        return (int) $this->option($key);
    }

    public function currency(): string
    {
        return strtoupper((string) $this->option('currency'));
    }

    /**
     * @param  array<string,mixed>  $parameters
     */
    public function route(string $name, array $parameters = [], ?string $language = null): string
    {
        return app(ContentLanguages::class)->route('atrium.classifieds.'.$name, $parameters, $language);
    }

    /**
     * A price as the visitor reads it: "1 250 €", or "Negotiable".
     */
    public function price(?float $amount, bool $negotiable, ?string $currency = null): string
    {
        if ($amount === null || $negotiable) {
            return $amount === null || $amount <= 0
                ? __('atrium-classifieds::classifieds.price.negotiable')
                : $this->money($amount, $currency).' · '.__('atrium-classifieds::classifieds.price.negotiable');
        }

        return $amount <= 0 ? __('atrium-classifieds::classifieds.price.free') : $this->money($amount, $currency);
    }

    public function money(float $amount, ?string $currency = null): string
    {
        return (string) Number::currency($amount, $currency ?: $this->currency(), app()->getLocale(), fmod($amount, 1.0) === 0.0 ? 0 : 2);
    }
}
