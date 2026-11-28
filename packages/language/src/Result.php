<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language;

use Atrium\Language\Values\Date;
use Atrium\Language\Values\Record;
use Brick\Math\BigDecimal;

/** What a formula came to. */
final readonly class Result
{
    /**
     * @param  BigDecimal|Date|Record|array<mixed>|string|bool|null  $value  null is the empty value
     * @param  array<string,mixed>  $fields  the fields that were read, by key, with the values used
     * @param  Step|null  $trace  how the value was counted, after explain()
     */
    public function __construct(
        public BigDecimal|Date|Record|array|string|bool|null $value,
        public array $fields,
        public ?Step $trace,
    ) {}
}
