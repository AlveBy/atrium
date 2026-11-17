<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $ad_id
 * @property-read Ad|null $ad
 */
class Favourite extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'classified_favourites';

    protected $fillable = ['user_id', 'ad_id'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'ad_id' => 'integer'];
    }

    /**
     * @return BelongsTo<Ad,$this>
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
}
