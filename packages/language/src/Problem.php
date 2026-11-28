<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language;

use Atrium\Language\Types\Type;

/**
 * One thing wrong with a formula or with a run of it: a code for programs,
 * and a sentence for people in any of the admin's languages.
 */
final readonly class Problem
{
    /**
     * @param  string  $code  a key of the package's `errors` translations, such as `check.unknown_field`
     * @param  array<string,string|int|Type>  $params  the translation's placeholders
     */
    public function __construct(
        public string $code,
        public array $params = [],
        public ?Position $at = null,
    ) {}

    public function message(?string $locale = null): string
    {
        $params = array_map(
            static fn (string|int|Type $param): string => $param instanceof Type ? $param->describe($locale) : (string) $param,
            $this->params,
        );

        $message = trans('atrium-language::errors.'.$this->code, $params, $locale);

        if ($this->at === null) {
            return $message;
        }

        return trans('atrium-language::errors.at', [
            'line' => $this->at->line,
            'column' => $this->at->column,
            'message' => $message,
        ], $locale);
    }
}
