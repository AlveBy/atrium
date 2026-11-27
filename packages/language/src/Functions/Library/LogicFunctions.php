<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Functions\Library;

use Atrium\Language\Functions\FormulaFunction;
use Atrium\Language\Functions\Invocation;
use Atrium\Language\Functions\Parameter;
use Atrium\Language\Types\Type;
use Atrium\Language\Values\Scalars;

/** IF, AND, OR and NOT belong to the language itself; these are about empty values. */
final class LogicFunctions
{
    /** @return list<FormulaFunction> */
    public static function all(): array
    {
        $any = Parameter::of(Type::any())->orEmpty();

        return [
            new FormulaFunction(['ISBLANK', 'ЕПУСТО'], [$any], Type::boolean(), static fn (Invocation $call): bool => Scalars::blank($call->argument(0))),
            new FormulaFunction(
                ['IFBLANK', 'ЕСЛИПУСТО'],
                [$any, $any],
                static fn (array $types): Type => Type::unify($types[0], $types[1]) ?? Type::any(),
                static fn (Invocation $call): mixed => $call->argument(Scalars::blank($call->argument(0)) ? 1 : 0),
            ),
        ];
    }
}
