<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Which catalogue row a 1C identifier is: the GUID 1C gives a group or a
 * product, the kind (section or product) and the id here.
 *
 * @property int $id
 * @property string $kind
 * @property string $guid
 * @property int $model_id
 */
final class Link extends Model
{
    public const SECTION = 'section';

    public const PRODUCT = 'product';

    protected $table = 'onec_links';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['model_id' => 'integer'];
    }

    public static function find1c(string $kind, string $guid): ?int
    {
        return self::query()->where('kind', $kind)->where('guid', $guid)->first()?->model_id;
    }

    public static function remember(string $kind, string $guid, int $modelId): void
    {
        self::query()->updateOrCreate(['kind' => $kind, 'guid' => $guid], ['model_id' => $modelId]);
    }
}
