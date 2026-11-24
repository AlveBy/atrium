<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24;

use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Modules\ActionFailed;
use Atrium\Admin\Modules\HasActions;
use Atrium\Admin\Modules\ModuleSettings;
use Atrium\Bitrix24\Support\Bitrix24Exception;
use Atrium\Bitrix24\Support\Client;

/**
 * Modules → Bitrix24: the incoming webhook (a secret: kept encrypted, shown
 * only as a mask), whether form submissions are sent, the chat widget code,
 * and the button that checks the connection.
 */
final class Bitrix24Settings extends ModuleSettings implements HasActions
{
    public function description(): string
    {
        return __('atrium-bitrix24::bitrix24.settings.description');
    }

    public function fields(): array
    {
        return [
            TextField::make('webhook')->label(__('atrium-bitrix24::bitrix24.settings.webhook'))
                ->hint(__('atrium-bitrix24::bitrix24.settings.webhook_hint'))
                ->placeholder('https://portal.bitrix24.com/rest/1/xxxxxxxxxxxxxxxx/')
                ->rules(['string', 'max:500', 'regex:'.Client::WEBHOOK_PATTERN]),
            CheckboxField::make('send_forms')->label(__('atrium-bitrix24::bitrix24.settings.send_forms'))
                ->hint(__('atrium-bitrix24::bitrix24.settings.send_forms_hint'))
                ->default(true),
            TextareaField::make('widget_code')->label(__('atrium-bitrix24::bitrix24.settings.widget_code'))
                ->hint(__('atrium-bitrix24::bitrix24.settings.widget_code_hint'))
                ->placeholder('<script>...</script>')
                ->rows(8)
                ->rules(['string', 'max:20000']),
        ];
    }

    public function secrets(): array
    {
        return ['webhook'];
    }

    /**
     * The portal and the user the webhook belongs to stay readable, the code
     * does not: https://portal.bitrix24.com/rest/1/••••••••
     */
    public function mask(string $field, string $value): string
    {
        return (string) preg_replace('#^(https?://[^/]+/rest/\d+/).*$#', '$1••••••••', $value);
    }

    public function actions(): array
    {
        return [['name' => 'check', 'label' => __('atrium-bitrix24::bitrix24.settings.check')]];
    }

    public function run(string $action): string
    {
        try {
            $profile = app(Client::class)->call('profile');
        } catch (Bitrix24Exception $e) {
            throw new ActionFailed($e->getMessage(), previous: $e);
        }

        $user = is_array($profile) ? trim((string) ($profile['NAME'] ?? '').' '.(string) ($profile['LAST_NAME'] ?? '')) : '';

        return __('atrium-bitrix24::bitrix24.settings.connected', [
            'portal' => parse_url((string) app(Client::class)->webhook(), PHP_URL_HOST),
            'user' => $user !== '' ? $user : '—',
        ]);
    }
}
