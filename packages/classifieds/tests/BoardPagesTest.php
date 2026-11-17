<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Tests;

use Atrium\Classifieds\Enums\AdStatus;

final class BoardPagesTest extends TestCase
{
    public function test_the_board_lists_published_ads_only(): void
    {
        $seller = $this->user();
        $category = $this->category();
        $this->ad($seller, $category, ['title' => 'Red scooter']);
        $this->ad($seller, $category, ['title' => 'Hidden draft', 'status' => AdStatus::Draft]);

        $this->get('/classifieds')
            ->assertOk()
            ->assertSee('Red scooter')
            ->assertDontSee('Hidden draft');
    }
}
