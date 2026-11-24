<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Support;

use Atrium\Admin\Models\AdminUser;

/**
 * Who is signed in to the panel, whichever way the request came: the form,
 * the API or the assistant all sign the administrator in on the panel's guard.
 */
final class Admins
{
    public static function current(): ?AdminUser
    {
        $admin = auth()->guard((string) config('atrium.auth.guard', 'admin'))->user();

        return $admin instanceof AdminUser ? $admin : null;
    }

    public static function currentId(): ?int
    {
        $admin = self::current();

        return $admin === null ? null : $admin->id;
    }

    /**
     * Active administrators by name, id => name: who a task can be given to.
     *
     * @return array<int,string>
     */
    public static function names(): array
    {
        return AdminUser::query()->where('is_active', true)->orderBy('name')->orderBy('id')->pluck('name', 'id')->all();
    }
}
