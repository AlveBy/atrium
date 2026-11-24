<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Enums;

/**
 * Out: the site called the other system. In: the other system called the
 * site (1C pushing a catalogue, a webhook).
 */
enum Direction: string
{
    case Out = 'out';
    case In = 'in';

    public function label(): string
    {
        return __('atrium-integrations::integrations.direction.'.$this->value);
    }
}
