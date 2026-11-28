<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Ir;

/**
 * What reads a tree. A new kind of node adds a method here, so nothing that
 * reads trees can forget it.
 *
 * @template T
 */
interface Visitor
{
    /** @return T */
    public function literal(Literal $node): mixed;

    /** @return T */
    public function field(Field $node): mixed;

    /** @return T */
    public function variable(Variable $node): mixed;

    /** @return T */
    public function member(Member $node): mixed;

    /** @return T */
    public function operation(Operation $node): mixed;

    /** @return T */
    public function call(Call $node): mixed;

    /** @return T */
    public function condition(Condition $node): mixed;

    /** @return T */
    public function each(Each $node): mixed;
}
