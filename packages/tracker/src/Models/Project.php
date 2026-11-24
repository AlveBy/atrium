<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Models;

use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\BelongsToField;
use Atrium\Admin\Fields\BelongsToManyField;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Filters\BooleanFilter;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A project: a key (ATR), a name, a lead, the administrators who work in it.
 * Its tasks are numbered ATR-1, ATR-2 … in the order they were made.
 *
 * @property int $id
 * @property string $name
 * @property string $key
 * @property string|null $description
 * @property int|null $lead_id
 * @property bool $is_archived
 * @property int $task_counter
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Project extends Model implements Administrable
{
    use InteractsWithAdmin;

    protected $table = 'tracker_projects';

    protected $fillable = ['name', 'key', 'description', 'lead_id', 'is_archived'];

    protected function casts(): array
    {
        return [
            'lead_id' => 'integer',
            'is_archived' => 'boolean',
            'task_counter' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // The tasks go with the project, each through its own delete so that
        // its comments and history go too.
        static::deleting(function (self $project): void {
            $project->tasks()->get()->each->delete();
            DB::table('tracker_project_members')->where('project_id', $project->getKey())->delete();
        });
    }

    /** @return BelongsTo<AdminUser,$this> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'lead_id');
    }

    /** @return BelongsToMany<AdminUser,$this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'tracker_project_members', 'project_id', 'admin_id');
    }

    /** @return HasMany<Task,$this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    /**
     * The next task number of a project, one for each caller even when two
     * tasks are made at once: the row is locked by the update until the
     * transaction ends.
     */
    public static function nextNumber(int $projectId): int
    {
        return DB::transaction(static function () use ($projectId): int {
            DB::table('tracker_projects')->where('id', $projectId)->increment('task_counter');

            return (int) DB::table('tracker_projects')->where('id', $projectId)->value('task_counter');
        });
    }

    /**
     * Whether an administrator works in the project: its lead or a member.
     */
    public function hasPerson(int $adminId): bool
    {
        return $this->lead_id === $adminId || $this->members()->whereKey($adminId)->exists();
    }

    /**
     * A project with nobody listed is open to every administrator.
     */
    public function isOpen(): bool
    {
        return $this->lead_id === null && ! $this->members()->exists();
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-tracker::tracker.projects.title');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'tracker_projects';
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-tracker::tracker.projects.add');
    }

    public function getAdminTitle(): string
    {
        return $this->key.' '.$this->name;
    }

    public static function getDefaultSortColumn(): string
    {
        return 'name';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    public static function getImportKeys(): array
    {
        return ['id', 'key'];
    }

    public static function getAdministrationFields(): array
    {
        return [
            TextField::make('name')->label(__('atrium-tracker::tracker.projects.fields.name'))->required()->maxLength(150),
            TextField::make('key')->label(__('atrium-tracker::tracker.projects.fields.key'))
                ->hint(__('atrium-tracker::tracker.projects.fields.key_hint'))
                ->required()
                ->rules(['regex:/^[A-Z][A-Z0-9]{1,9}$/'])
                ->unique('tracker_projects', 'key'),
            TextareaField::make('description')->label(__('atrium-tracker::tracker.projects.fields.description'))->rows(5)->maxLength(5000),
            BelongsToField::make('lead_id')->label(__('atrium-tracker::tracker.projects.fields.lead'))->model(AdminUser::class)->rules('integer'),
            BelongsToManyField::make('members')->label(__('atrium-tracker::tracker.projects.fields.members'))
                ->hint(__('atrium-tracker::tracker.projects.fields.members_hint'))
                ->model(AdminUser::class),
            CheckboxField::make('is_archived')->label(__('atrium-tracker::tracker.projects.fields.archived'))
                ->hint(__('atrium-tracker::tracker.projects.fields.archived_hint'))
                ->default(false),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('key')->label(__('atrium-tracker::tracker.projects.fields.key'))->sortable()->searchable(),
            TextColumn::make('name')->label(__('atrium-tracker::tracker.projects.fields.name'))->sortable()->searchable(),
            TextColumn::make('lead.name')->label(__('atrium-tracker::tracker.projects.fields.lead')),
            BooleanColumn::make('is_archived')->label(__('atrium-tracker::tracker.projects.fields.archived')),
        ];
    }

    public static function getAdministrationFilters(): array
    {
        return [
            BooleanFilter::make('is_archived')->label(__('atrium-tracker::tracker.projects.fields.archived')),
        ];
    }
}
