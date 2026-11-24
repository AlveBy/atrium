<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Enums;

/**
 * How one attempt ended: it worked, it failed and will be tried again, or it
 * failed for good.
 */
enum Status: string
{
    case Ok = 'ok';
    case Retry = 'retry';
    case Failed = 'failed';

    public function label(): string
    {
        return __('atrium-integrations::integrations.status.'.$this->value);
    }
}
