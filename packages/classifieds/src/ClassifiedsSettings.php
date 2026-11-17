<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds;

use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Modules\ModuleSettings;

/**
 * The board's rules, per site: moderation, how long an ad lives, the abuse
 * limits and the prices of paid placement. Read through Board::option().
 */
final class ClassifiedsSettings extends ModuleSettings
{
    public bool $perSite = true;

    public function fields(): array
    {
        $defaults = (array) config('atrium-classifieds.defaults', []);
        $label = static fn (string $key): string => __('atrium-classifieds::classifieds.settings.'.$key);
        $number = static fn (string $key, int $min, int $max): NumberField => NumberField::make($key)
            ->label($label($key))
            ->default($defaults[$key] ?? null)
            ->min($min)
            ->max($max);

        return [
            CheckboxField::make('premoderation')->label($label('premoderation'))
                ->hint(__('atrium-classifieds::classifieds.settings.premoderation_hint'))
                ->default((bool) ($defaults['premoderation'] ?? true)),
            $number('ttl_days', 1, 365),
            $number('renewals', 0, 100),
            $number('ads_per_day', 1, 1000),
            $number('messages_per_hour', 1, 1000),
            $number('phone_reveals_per_hour', 1, 1000),
            $number('complaints_to_review', 1, 100),
            TextField::make('currency')->label($label('currency'))->default($defaults['currency'] ?? 'USD')->rules(['size:3', 'alpha']),
            $number('per_page', 1, 100),
            $number('max_photos', 1, 50),
            $number('raise_price', 0, 1000000),
            $number('highlight_price', 0, 1000000),
            $number('highlight_days', 1, 365),
            $number('vip_price', 0, 1000000),
            $number('vip_days', 1, 365),
        ];
    }
}
