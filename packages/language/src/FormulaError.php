<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language;

use Atrium\Language\Types\Type;
use RuntimeException;

/**
 * A formula could not be read, did not pass the check or stopped while
 * running. The message is in the application's language; message() of each
 * problem gives any other.
 */
final class FormulaError extends RuntimeException
{
    /**
     * @param  non-empty-list<Problem>  $problems
     */
    public function __construct(public readonly array $problems)
    {
        parent::__construct(implode(' ', array_map(
            static fn (Problem $problem): string => $problem->message(),
            $problems,
        )));
    }

    /**
     * @param  array<string,string|int|Type>  $params
     */
    public static function of(string $code, ?Position $at = null, array $params = []): self
    {
        return new self([new Problem($code, $params, $at)]);
    }

    /** The first problem: the only one, unless the check found several. */
    public function problem(): Problem
    {
        return $this->problems[0];
    }
}
