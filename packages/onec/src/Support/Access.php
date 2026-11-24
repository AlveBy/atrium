<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC\Support;

use Atrium\Admin\Facades\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Who may exchange: 1C signs in once (checkauth, HTTP Basic with the login
 * and the password of the settings) and is given a cookie, which it sends
 * with every later call. Wrong passwords are counted per address.
 */
final class Access
{
    public const COOKIE = 'atrium_1c';

    private const HOUR = 3600;

    private const ATTEMPTS = 20;

    public function configured(): bool
    {
        return $this->setting('login') !== '' && $this->setting('password') !== '';
    }

    /**
     * The cookie value for a correct login, null for a wrong one.
     */
    public function login(Request $request): ?string
    {
        $key = 'atrium-1c:auth:'.$request->ip();

        if (! $this->configured() || RateLimiter::tooManyAttempts($key, self::ATTEMPTS)) {
            return null;
        }

        $login = hash_equals($this->setting('login'), (string) $request->getUser());
        $password = hash_equals($this->setting('password'), (string) $request->getPassword());

        if (! $login || ! $password) {
            RateLimiter::hit($key, 60);

            return null;
        }

        $token = Str::random(40);
        Cache::put(self::cacheKey($token), true, self::HOUR);

        return $token;
    }

    public function allows(Request $request): bool
    {
        return Cache::has(self::cacheKey($this->token($request)));
    }

    public function token(Request $request): string
    {
        return (string) $request->cookie(self::COOKIE);
    }

    private static function cacheKey(string $token): string
    {
        return 'atrium-1c:session:'.$token;
    }

    private function setting(string $name): string
    {
        $value = Setting::get('1c.'.$name);

        return is_string($value) ? $value : '';
    }
}
