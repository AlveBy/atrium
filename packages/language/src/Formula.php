<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language;

use Atrium\Language\Checking\Checker;
use Atrium\Language\Interpreter\Interpreter;
use Atrium\Language\Interpreter\Meter;
use Atrium\Language\Ir\Codec;
use Atrium\Language\Ir\Node;
use Atrium\Language\Ir\Shape;
use Atrium\Language\Parser\Parser;

/**
 * A formula as its tree: read from text, from stored JSON or built node by
 * node; checked against a context; worked out in it.
 */
final readonly class Formula
{
    /** The longest stored tree, in bytes. */
    public const JSON_BYTES = 1000000;

    private function __construct(public Node $root)
    {
        Shape::ensure($root);
    }

    /** @throws FormulaError with the line and column of what cannot be read */
    public static function parse(string $source): self
    {
        return new self(Parser::parse($source));
    }

    public static function fromNode(Node $root): self
    {
        return new self($root);
    }

    /** @throws FormulaError */
    public static function fromJson(string $json): self
    {
        if (strlen($json) > self::JSON_BYTES) {
            throw FormulaError::of('formula.too_long', null, ['limit' => self::JSON_BYTES]);
        }

        return new self(Codec::decode(Codec::read($json)));
    }

    public function toJson(): string
    {
        return json_encode(Codec::encode($this->root), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /** Everything that is wrong with the formula before running it, and the type of its result. */
    public function check(Context $context): Check
    {
        return (new Checker($context))->check($this->root);
    }

    /**
     * Checks the formula and works it out.
     *
     * @throws FormulaError when the check fails, a value is unfit or a limit is reached
     */
    public function evaluate(Context $context): Result
    {
        return $this->run($context, false);
    }

    /**
     * Like evaluate(), keeping how the result was counted: the value of every
     * part that was worked out.
     *
     * @throws FormulaError
     */
    public function explain(Context $context): Result
    {
        return $this->run($context, true);
    }

    private function run(Context $context, bool $explain): Result
    {
        return Meter::run($context->limits(), $context->clock(), function (Meter $meter) use ($context, $explain): Result {
            $check = $this->check($context);

            if (! $check->passes()) {
                throw new FormulaError($check->problems);
            }

            return (new Interpreter($context, $check->functions, $meter, $explain))->run($this->root);
        });
    }
}
