<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Models;

use Atrium\Classifieds\Support\PersonalData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A message about an ad between a buyer and its seller. The text is
 * personal data and is kept encrypted.
 *
 * @property int $id
 * @property int $ad_id
 * @property int $buyer_id
 * @property int $sender_id
 * @property bool $from_seller
 * @property string $body
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property-read Ad|null $ad
 */
class Message extends Model
{
    protected $table = 'classified_messages';

    protected $fillable = ['ad_id', 'buyer_id', 'sender_id', 'from_seller', 'body', 'read_at'];

    protected function casts(): array
    {
        return [
            'ad_id' => 'integer',
            'buyer_id' => 'integer',
            'sender_id' => 'integer',
            'from_seller' => 'boolean',
            'body' => PersonalData::CAST,
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Ad,$this>
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
}
