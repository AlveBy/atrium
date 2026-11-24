<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24\Support;

use RuntimeException;

/**
 * A call to the portal that did not work. The message is safe to show: it
 * says what the portal answered, never the address that was called.
 */
final class Bitrix24Exception extends RuntimeException {}
