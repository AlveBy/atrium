<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Anti-spam without an outside service: a hidden field people leave empty
 * and the time the form was drawn, signed so it cannot be forged.
 */
final class SpamGuard
{
    public const TIME_FIELD = '_t';

    public const HONEYPOT = 'honeypot';

    public const TOO_FAST = 'too_fast';

    public static function honeypotName(): string
    {
        return (string) config('atrium-jobs.honeypot', 'website');
    }

    public static function token(): string
    {
        return Crypt::encryptString((string) time());
    }

    /**
     * @return self::HONEYPOT|self::TOO_FAST|null
     */
    public static function check(Request $request): ?string
    {
        if (filled($request->input(self::honeypotName()))) {
            return self::HONEYPOT;
        }

        try {
            $drawnAt = (int) Crypt::decryptString((string) $request->input(self::TIME_FIELD));
        } catch (DecryptException) {
            return self::TOO_FAST;
        }

        return time() - $drawnAt < (int) config('atrium-jobs.min_seconds', 3) ? self::TOO_FAST : null;
    }
}
