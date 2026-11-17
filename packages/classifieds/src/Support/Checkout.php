<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

use Atrium\Classifieds\Models\Boost;

/**
 * Where a boost is paid for. The shop module provides one
 * (Integrations\ShopCheckout); without it nothing is bound and an
 * administrator marks boosts paid by hand.
 */
interface Checkout
{
    /**
     * Put the boost into the buyer's cart and give the address to pay at,
     * or null when it cannot be sold.
     */
    public function start(Boost $boost): ?string;
}
