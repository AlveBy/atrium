<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    /*
     * How many products and offers one import request reads. 1C asks again
     * until the file is done, so a bigger number means fewer, longer requests.
     */
    'step' => 500,
];
