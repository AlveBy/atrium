<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Models;

use Atrium\Admin\Columns\DateColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Classifieds\Support\StopWords;
use Illuminate\Database\Eloquent\Model;

/**
 * A word or phrase that sends an ad to the moderators instead of the site.
 *
 * @property int $id
 * @property int|null $site_id
 * @property string $word
 */
class StopWord extends Model implements Administrable
{
    use BelongsToSite, InteractsWithAdmin;

    protected $table = 'classified_stop_words';

    protected $fillable = ['site_id', 'word'];

    protected static function booted(): void
    {
        $flush = static fn () => app(StopWords::class)->flush();

        static::saved($flush);
        static::deleted($flush);
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-classifieds::classifieds.modules.stop_words');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-classifieds::classifieds.create.stop_word');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'classified_stop_words';
    }

    public function getAdminTitle(): string
    {
        return $this->word;
    }

    public static function getAdministrationFields(): array
    {
        return [
            TextField::make('word')->label(__('atrium-classifieds::classifieds.fields.word'))->required()->rules(['max:100']),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('word')->label(__('atrium-classifieds::classifieds.fields.word'))->sortable()->searchable(),
            DateColumn::make('created_at')->label(__('atrium::fields.standard.created_at'))->sortable(),
        ];
    }
}
