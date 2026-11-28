<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Types;

enum Kind: string
{
    case Number = 'number';
    case Text = 'text';
    case Boolean = 'boolean';
    case Date = 'date';
    case Empty = 'empty';
    case Any = 'any';
    case List = 'list';
    case Record = 'record';
    case Union = 'union';
}
