<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC;

use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Modules\ModuleSettings;

/**
 * Modules → 1C: the login and the password 1C signs in with (the password is
 * a secret: kept encrypted, shown only as a mask). The addresses 1C calls are
 * in the line under the title.
 */
final class OneCSettings extends ModuleSettings
{
    public function description(): string
    {
        return __('atrium-1c::onec.settings.description', [
            'url' => url(OneCServiceProvider::PATH),
            'bitrix' => url(OneCServiceProvider::PATH_BITRIX),
        ]);
    }

    public function fields(): array
    {
        return [
            TextField::make('login')->label(__('atrium-1c::onec.settings.login'))
                ->hint(__('atrium-1c::onec.settings.login_hint'))
                ->placeholder('exchange')
                ->rules(['max:100']),
            TextField::make('password')->label(__('atrium-1c::onec.settings.password'))
                ->hint(__('atrium-1c::onec.settings.password_hint'))
                ->rules(['max:200']),
        ];
    }

    public function secrets(): array
    {
        return ['password'];
    }
}
