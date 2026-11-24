<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Tests\Fixtures;

use Atrium\Integrations\Jobs\IntegrationJob;
use RuntimeException;

/**
 * The least a connector has to write: it names itself and does the work and
 * leaves the "about" and the secrets to the base's defaults.
 */
final class BareJob extends IntegrationJob
{
    public function __construct(public readonly bool $fail = false) {}

    protected function integration(): string
    {
        return 'bare';
    }

    protected function action(): string
    {
        return 'thing.send';
    }

    protected function perform(): ?string
    {
        if ($this->fail) {
            throw new RuntimeException('Refused by https://hooks.example.test/x');
        }

        return null;
    }
}
