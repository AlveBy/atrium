<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Integrations;

use Atrium\Account\Account;
use Atrium\Account\AccountContext;
use Atrium\Account\Models\Company;
use Atrium\Account\Sections\Section;
use Atrium\Admin\Facades\Hook;
use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Support\Owner;

/**
 * The personal cabinet (alveby/atrium-account) seen from the board: who
 * acts (a person, or a person for a company), company names, and the
 * board's sections in the cabinet's menu. Used only when it is installed.
 */
final class AccountBridge
{
    public function owner(): ?Owner
    {
        $context = app(Account::class)->context();

        return $context === null ? null : $this->fromContext($context);
    }

    public function fromContext(AccountContext $context): Owner
    {
        return new Owner((int) $context->user->id, $context->company?->id, $context->name());
    }

    public function companyName(int $id): ?string
    {
        return Company::query()->whereKey($id)->value('name');
    }

    /**
     * My ads, favourites, messages and saved searches in the cabinet menu.
     */
    public function registerSections(): void
    {
        Hook::filter('atrium.account.sections', function (array $sections): array {
            $title = static fn (string $key): \Closure => static fn (): string => __('atrium-classifieds::classifieds.cabinet.'.$key);

            return [
                ...$sections,
                Section::make('classifieds.ads')->title($title('ads'))->icon('tag')->permission('classifieds.ads')->for(Section::BOTH)->sort(400)
                    ->badge(fn (AccountContext $context): int => $this->fromContext($context)->scope(Ad::query())
                        ->where('status', AdStatus::Rejected->value)->count())
                    ->route('atrium.classifieds.cabinet.ads'),
                Section::make('classifieds.messages')->title($title('messages'))->icon('chat')->for(Section::BOTH)->sort(401)
                    ->badge(fn (AccountContext $context): int => app(\Atrium\Classifieds\Support\Conversations::class)->unread($this->fromContext($context)))
                    ->route('atrium.classifieds.cabinet.messages'),
                Section::make('classifieds.favourites')->title($title('favourites'))->icon('heart')->for(Section::USER)->sort(402)
                    ->route('atrium.classifieds.cabinet.favourites'),
                Section::make('classifieds.searches')->title($title('searches'))->icon('star')->for(Section::USER)->sort(403)
                    ->route('atrium.classifieds.cabinet.searches'),
            ];
        });
    }
}
