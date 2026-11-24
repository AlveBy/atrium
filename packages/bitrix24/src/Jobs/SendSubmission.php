<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24\Jobs;

use Atrium\Bitrix24\Models\FormLink;
use Atrium\Bitrix24\Support\Client;
use Atrium\Bitrix24\Support\Payload;
use Atrium\Forms\Models\FormSubmission;
use Atrium\Integrations\Jobs\IntegrationJob;
use Illuminate\Support\Facades\Cache;

/**
 * Sends one form submission to Bitrix24 as a lead, or as a contact and a deal
 * that points to it. Five attempts, each a line of the exchange log.
 */
final class SendSubmission extends IntegrationJob
{
    public function __construct(private readonly int $submissionId) {}

    protected function integration(): string
    {
        return 'bitrix24';
    }

    protected function action(): string
    {
        return $this->link()?->mode === FormLink::DEAL ? 'deal.add' : 'lead.add';
    }

    protected function subject(): string
    {
        return __('atrium-bitrix24::bitrix24.payload.subject', ['id' => $this->submissionId]);
    }

    protected function secrets(): array
    {
        return [(string) app(Client::class)->webhook()];
    }

    protected function perform(): string
    {
        $submission = FormSubmission::withoutGlobalScopes()->find($this->submissionId);

        if ($submission === null) {
            return __('atrium-bitrix24::bitrix24.payload.gone');
        }

        $client = app(Client::class);
        $link = $this->link();
        $fields = Payload::fields($submission, $link);

        if ($link?->mode !== FormLink::DEAL) {
            return __('atrium-bitrix24::bitrix24.payload.lead', ['id' => $client->call('crm.lead.add', ['fields' => $fields, 'params' => ['REGISTER_SONET_EVENT' => 'N']])]);
        }

        [$contact, $deal] = Payload::split($fields);

        // A retry after the deal failed must not make the contact twice.
        $contactId = Cache::remember('atrium-bitrix24:contact:'.$this->submissionId, now()->addDay(), fn (): mixed => $client->call('crm.contact.add', ['fields' => $contact, 'params' => ['REGISTER_SONET_EVENT' => 'N']]));
        $dealId = $client->call('crm.deal.add', ['fields' => [...$deal, 'CONTACT_ID' => $contactId], 'params' => ['REGISTER_SONET_EVENT' => 'N']]);

        return __('atrium-bitrix24::bitrix24.payload.deal', ['deal' => $dealId, 'contact' => $contactId]);
    }

    private function link(): ?FormLink
    {
        $formId = FormSubmission::withoutGlobalScopes()->whereKey($this->submissionId)->value('form_id');

        return $formId === null ? null : FormLink::query()->where('form_id', $formId)->first();
    }
}
