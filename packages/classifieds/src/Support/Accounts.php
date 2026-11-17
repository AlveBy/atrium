<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

use Atrium\Admin\Modules\Modules;
use Atrium\Classifieds\Integrations\AccountBridge;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

/**
 * People and companies, wherever they live. With the personal cabinet
 * (alveby/atrium-account) installed they are its users and companies and
 * the board's pages sit in its menu; without it the application's users
 * sign in through the configured guard and post on their own.
 */
class Accounts
{
    public const CABINET = 'alveby/atrium-account';

    public function hasCabinet(): bool
    {
        return class_exists('Atrium\\Account\\Account') && app(Modules::class)->isInstalled(self::CABINET);
    }

    public function guard(): string
    {
        return $this->hasCabinet() ? 'account' : (string) config('atrium-classifieds.guard', 'web');
    }

    /**
     * The middleware of the cabinet pages: sign-in required.
     */
    public function middleware(): string
    {
        return $this->hasCabinet() ? 'account.auth' : 'auth:'.$this->guard();
    }

    public function user(): ?Authenticatable
    {
        return Auth::guard($this->guard())->user();
    }

    public function userId(): ?int
    {
        $id = Auth::guard($this->guard())->id();

        return $id === null ? null : (int) $id;
    }

    /**
     * Who acts right now, or null for a guest.
     */
    public function owner(): ?Owner
    {
        if ($this->hasCabinet()) {
            return app(AccountBridge::class)->owner();
        }

        $user = $this->user();

        return $user === null ? null : new Owner((int) $user->getAuthIdentifier(), null, $this->nameOf($user));
    }

    /**
     * A person by id, through the guard's user provider.
     */
    public function find(int $id): ?Authenticatable
    {
        $provider = Auth::createUserProvider(config('auth.guards.'.$this->guard().'.provider'));

        return $provider?->retrieveById($id);
    }

    public function email(int $id): ?string
    {
        $user = $this->find($id);
        $email = $user?->getAttribute('email');

        return is_string($email) && $email !== '' ? $email : null;
    }

    public function nameOf(Authenticatable $user): string
    {
        return (string) ($user->getAttribute('name') ?? $user->getAttribute('email') ?? '');
    }

    /**
     * The public name of a company, null when there is none (no cabinet,
     * or the company is gone).
     */
    public function companyName(int $id): ?string
    {
        return $this->hasCabinet() ? app(AccountBridge::class)->companyName($id) : null;
    }
}
