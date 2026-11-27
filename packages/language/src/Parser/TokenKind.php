<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Parser;

enum TokenKind
{
    case Number;
    case Text;
    case Date;
    case Word;
    /** A name in square brackets. */
    case Name;
    case Symbol;
    case End;
}
