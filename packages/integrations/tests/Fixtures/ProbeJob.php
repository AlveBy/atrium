<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Tests\Fixtures;

use Atrium\Integrations\Jobs\IntegrationJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * A connector's job as the tests need it: it fails for the first
 * `$failTimes` attempts (counted in the cache, which survives the queue's
 * round trips) and then works.
 */
final class ProbeJob extends IntegrationJob
{
    public function __construct(
        public readonly int $failTimes = 0,
        public readonly string $secret = '',
        public readonly ?string $about = null,
        public readonly int $takesMs = 0,
    ) {}

    protected function integration(): string
    {
        return 'probe';
    }

    protected function action(): string
    {
        return 'thing.send';
    }

    protected function subject(): ?string
    {
        return $this->about;
    }

    protected function secrets(): array
    {
        return [$this->secret];
    }

    protected function perform(): ?string
    {
        $tried = Cache::increment('probe-job-tries');
        Carbon::setTestNow(now()->addMilliseconds($this->takesMs));

        if ($tried <= $this->failTimes) {
            throw new RuntimeException("Could not reach https://hooks.example.test/{$this->secret}/add (try {$tried})");
        }

        return "Sent with {$this->secret} on try {$tried}";
    }
}
