<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language;

use Atrium\Language\Types\Type;

final readonly class SchemaField
{
    public function __construct(
        public string $key,
        public Type $type,
    ) {}
}
