<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Ci;

use RuntimeException;

/**
 * A wrong argument or a missing tool: bin/ci stops and says so.
 */
final class CiException extends RuntimeException {}
