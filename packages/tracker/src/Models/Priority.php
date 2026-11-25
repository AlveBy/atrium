<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Models;

use Atrium\Admin\Fields\TextField;

/**
 * How soon a task is wanted. The name always carries a symbol as well as the
 * word, so the priority is not told by colour.
 *
 * @property string|null $symbol
 */
final class Priority extends Term
{
    protected $table = 'tracker_priorities';

    public static function taskColumn(): string
    {
        return 'priority';
    }

    public static function group(): string
    {
        return 'priorities';
    }

    public function label(): string
    {
        return trim($this->symbol.' '.parent::label());
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-tracker::tracker.terms.priorities');
    }

    public static function getAdministrationFields(): array
    {
        return [
            ...self::commonFields(),
            TextField::make('symbol')->label(__('atrium-tracker::tracker.terms.fields.symbol'))->hint(__('atrium-tracker::tracker.terms.fields.symbol_hint'))->maxLength(4),
        ];
    }
}
