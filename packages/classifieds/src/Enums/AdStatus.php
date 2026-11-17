<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Enums;

/**
 * Where an ad is in its life. Only an active ad is on the site; the owner
 * sees all of theirs in the cabinet.
 */
enum AdStatus: string
{
    case Draft = 'draft';
    case Moderation = 'moderation';
    case Active = 'active';
    case Expired = 'expired';
    case Sold = 'sold';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('atrium-classifieds::classifieds.status.'.$this->value);
    }

    /** Statuses the owner may send for publishing from. */
    public function canSubmit(): bool
    {
        return in_array($this, [self::Draft, self::Rejected, self::Expired], true);
    }

    /** Statuses the owner may still edit the text of. */
    public function isEditable(): bool
    {
        return $this !== self::Sold;
    }

    /**
     * @return array<string,string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
