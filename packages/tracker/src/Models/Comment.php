<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Models;

use Atrium\Admin\Columns\DateColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A comment on a task. Written on the task's form; the admin lists them and
 * may delete one that should not be there.
 *
 * @property int $id
 * @property int $task_id
 * @property int|null $author_id
 * @property string $body
 * @property Carbon|null $created_at
 */
class Comment extends Model implements Administrable
{
    use InteractsWithAdmin;

    protected $table = 'tracker_comments';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['task_id' => 'integer', 'author_id' => 'integer'];
    }

    /** @return BelongsTo<Task,$this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /** @return BelongsTo<AdminUser,$this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'author_id');
    }

    public static function isCreatableInAdmin(): bool
    {
        return false;
    }

    public static function isEditableInAdmin(): bool
    {
        return false;
    }

    public static function isImportableInAdmin(): bool
    {
        return false;
    }

    public static function isContentModule(): bool
    {
        return false;
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-tracker::tracker.comments.title');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'tracker_comments';
    }

    public function getAdminTitle(): string
    {
        return '#'.$this->id;
    }

    public static function getAdministrationFields(): array
    {
        return [
            TextareaField::make('body')->label(__('atrium-tracker::tracker.comments.fields.body'))->rows(8)->readonly()->hideOnCreate(),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('task.title')->label(__('atrium-tracker::tracker.comments.fields.task')),
            TextColumn::make('author.name')->label(__('atrium-tracker::tracker.comments.fields.author')),
            TextColumn::make('body')->label(__('atrium-tracker::tracker.comments.fields.body'))->searchable()->limit(100),
            DateColumn::make('created_at')->label(__('atrium-tracker::tracker.comments.fields.created'))->sortable(),
        ];
    }
}
