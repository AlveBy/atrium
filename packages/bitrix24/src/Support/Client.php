<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24\Support;

use Atrium\Admin\Facades\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Calls the portal's REST API through the incoming webhook pasted in the
 * settings: POST {webhook}{method}.json with the parameters as JSON.
 */
final class Client
{
    /** The webhook as Bitrix24 shows it: https://portal.bitrix24.com/rest/1/code/ */
    public const WEBHOOK_PATTERN = '#^https?://[^\s/]+/rest/\d+/[A-Za-z0-9]+/?$#';

    public function webhook(): ?string
    {
        $webhook = Setting::get('bitrix24.webhook');

        return is_string($webhook) ? $webhook : null;
    }

    /**
     * @param  array<string,mixed>  $params
     *
     * @throws Bitrix24Exception
     */
    public function call(string $method, array $params = []): mixed
    {
        $webhook = $this->webhook() ?? throw new Bitrix24Exception(__('atrium-bitrix24::bitrix24.errors.no_webhook'));

        try {
            $response = Http::timeout(15)->acceptJson()->asJson()->post(rtrim($webhook, '/').'/'.$method.'.json', $params);
        } catch (ConnectionException) {
            // The exception carries the address; the portal not answering is all that is known.
            throw new Bitrix24Exception(__('atrium-bitrix24::bitrix24.errors.unreachable'));
        }

        $error = $response->json('error');

        if ($error !== null || $response->failed()) {
            throw new Bitrix24Exception(__('atrium-bitrix24::bitrix24.errors.portal', [
                'error' => is_string($error) ? trim($error.' '.$response->json('error_description')) : 'HTTP '.$response->status(),
            ]));
        }

        return $response->json('result');
    }
}
