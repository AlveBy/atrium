<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Models;

use Atrium\Admin\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A search a visitor keeps, with an e-mail when new ads match it.
 * last_ad_id is the newest ad already reported: the next letter lists
 * only ads published after it.
 *
 * @property int $id
 * @property int|null $site_id
 * @property int $user_id
 * @property string $name
 * @property string|null $locale
 * @property array<string,mixed> $filters
 * @property bool $notify
 * @property int $last_ad_id
 * @property Carbon|null $notified_at
 */
class SavedSearch extends Model
{
    use BelongsToSite;

    protected $table = 'classified_saved_searches';

    protected $fillable = ['site_id', 'user_id', 'name', 'locale', 'filters', 'notify', 'last_ad_id', 'notified_at'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'filters' => 'array', 'notify' => 'boolean', 'last_ad_id' => 'integer', 'notified_at' => 'datetime'];
    }
}
