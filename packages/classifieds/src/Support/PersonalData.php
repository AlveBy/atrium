<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

/**
 * How personal data is kept: phones, e-mails and messages are encrypted
 * at rest and decrypted only to be shown to the person allowed to see
 * them. One place to change when the encryption core arrives.
 */
final class PersonalData
{
    public const CAST = 'encrypted';
}
