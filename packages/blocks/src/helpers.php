<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Atrium\Blocks\Blocks;

if (! function_exists('atrium_block')) {
    /**
     * The block with this code in the current language, ready to print, for
     * a template that does not use the @block directive:
     *
     *     {!! atrium_block('phone') !!}
     */
    function atrium_block(string $code): string
    {
        return app(Blocks::class)->html($code);
    }
}
