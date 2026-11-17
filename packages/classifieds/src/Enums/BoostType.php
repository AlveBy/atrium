<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Enums;

/**
 * Paid placement. Raise puts the ad on top of its lists once; highlight
 * colours it and VIP pins it above the others, both for some days.
 */
enum BoostType: string
{
    case Raise = 'raise';
    case Highlight = 'highlight';
    case Vip = 'vip';

    public function label(): string
    {
        return __('atrium-classifieds::classifieds.boost.'.$this->value);
    }
}
