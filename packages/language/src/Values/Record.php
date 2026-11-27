<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Values;

use Atrium\Language\Schema;

/** One related record: values by the keys of its fields. */
final readonly class Record
{
    /**
     * @param  array<string,mixed>  $values  values of the language, by field key
     */
    public function __construct(
        public Schema $fields,
        public array $values,
    ) {}
}
