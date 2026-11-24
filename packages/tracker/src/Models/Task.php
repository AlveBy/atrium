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
use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Fields\BelongsToField;
use Atrium\Admin\Fields\ContentField;
use Atrium\Admin\Fields\DateField;
use Atrium\Admin\Fields\DisplayField;
use Atrium\Admin\Fields\GalleryField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\RepeatableField;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Filters\BooleanFilter;
use Atrium\Admin\Filters\SelectFilter;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Traits\HasAdminTabs;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Tracker\Enums\Priority;
use Atrium\Tracker\Enums\TaskType;
use Atrium\Tracker\Support\Admins;
use Atrium\Tracker\Support\Notifier;
use Atrium\Tracker\Support\Statuses;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * A task of a project: ATR-12. Its history is the lines of tracker_events
 * (who changed the status, the assignee or the priority) and its comments.
 *
 * @property int $id
 * @property int $project_id
 * @property int $number
 * @property string $type
 * @property int|null $parent_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string $priority
 * @property int|null $assignee_id
 * @property int|null $author_id
 * @property list<string>|null $labels
 * @property Carbon|null $due_date
 * @property string|null $estimate_hours
 * @property list<string>|null $attachments
 * @property int $sort
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $code
 * @property-read Project|null $project
 * @property-read AdminUser|null $assignee
 * @property-read AdminUser|null $author
 */
class Task extends Model implements Administrable
{
    use HasAdminTabs, InteractsWithAdmin;

    protected $table = 'tracker_tasks';

    /** The number, the author and the closing time are set by the tracker itself. */
    protected $fillable = [
        'project_id', 'type', 'parent_id', 'title', 'description', 'status', 'priority',
        'assignee_id', 'labels', 'due_date', 'estimate_hours', 'attachments', 'sort',
    ];

    /** The comment typed on the form, kept until the task is saved. */
    private string $pendingComment = '';

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'number' => 'integer',
            'parent_id' => 'integer',
            'assignee_id' => 'integer',
            'author_id' => 'integer',
            'labels' => 'array',
            'attachments' => 'array',
            'due_date' => 'date',
            'estimate_hours' => 'decimal:2',
            'sort' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static function (self $task): void {
            $task->number = Project::nextNumber($task->project_id);
            $task->author_id ??= Admins::currentId();
            $task->status ??= Statuses::first();
            $task->type ??= TaskType::Task->value;
            $task->priority ??= Priority::Medium->value;
            $task->completed_at = $task->status === Statuses::done() ? now() : null;
        });

        static::updating(static function (self $task): void {
            if ($task->isDirty('status')) {
                $task->completed_at = $task->status === Statuses::done() ? now() : null;
            }
        });

        static::created(static function (self $task): void {
            $task->record('created');

            if ($task->assignee_id !== null) {
                Notifier::assigned($task);
            }
        });

        static::updated(static function (self $task): void {
            if ($task->wasChanged('status')) {
                $from = $task->getOriginal('status');
                $task->record('status', $from, $task->status);
                Notifier::statusChanged($task, $from);
                Hook::fire('atrium.tracker.task.status_changed', $task, $from, $task->status);
            }

            if ($task->wasChanged('assignee_id')) {
                $task->record('assignee', self::adminName($task->getOriginal('assignee_id')), self::adminName($task->assignee_id));

                if ($task->assignee_id !== null) {
                    Notifier::assigned($task);
                    Hook::fire('atrium.tracker.task.assigned', $task, $task->assignee_id);
                }
            }

            if ($task->wasChanged('priority')) {
                $task->record('priority', (string) $task->getOriginal('priority'), $task->priority);
            }
        });

        static::deleting(static function (self $task): void {
            $task->comments()->delete();
            $task->events()->delete();
            self::query()->where('parent_id', $task->getKey())->update(['parent_id' => null]);
        });
    }

    /** @return BelongsTo<Project,$this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /** @return BelongsTo<self,$this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self,$this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return BelongsTo<AdminUser,$this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'assignee_id');
    }

    /** @return BelongsTo<AdminUser,$this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'author_id');
    }

    /** @return HasMany<Comment,$this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'task_id');
    }

    /** @return HasMany<Event,$this> */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'task_id');
    }

    /**
     * ATR-12: the project's key and the number in it.
     */
    public function getCodeAttribute(): string
    {
        return ($this->project->key ?? '').'-'.$this->number;
    }

    /**
     * Past its due date and not done.
     */
    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->lt(today())
            && $this->status !== Statuses::done();
    }

    private function record(string $kind, ?string $old = null, ?string $new = null): void
    {
        $this->events()->create([
            'actor_id' => Admins::currentId(),
            'kind' => $kind,
            'old_value' => $old,
            'new_value' => $new,
            'created_at' => now(),
        ]);
    }

    private static function adminName(mixed $id): ?string
    {
        return $id === null ? null : AdminUser::query()->whereKey($id)->value('name');
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-tracker::tracker.tasks.title');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'tracker_tasks';
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-tracker::tracker.tasks.add');
    }

    public function getAdminTitle(): string
    {
        return $this->exists ? $this->code.' '.$this->title : $this->title;
    }

    public static function getImportKeys(): array
    {
        return ['id'];
    }

    public function scopeAdminList(Builder $query): Builder
    {
        return $query->with('project');
    }

    public static function getAdminTabs(): array
    {
        return [
            ['name' => 'params', 'label' => __('atrium-tracker::tracker.tasks.tabs.params')],
            ['name' => 'files', 'label' => __('atrium-tracker::tracker.tasks.tabs.files')],
            ['name' => 'activity', 'label' => __('atrium-tracker::tracker.tasks.tabs.activity')],
        ];
    }

    public static function getAdministrationFields(): array
    {
        return [
            BelongsToField::make('project_id')->label(__('atrium-tracker::tracker.tasks.fields.project'))->model(Project::class)
                ->query(static fn (Builder $query): Builder => $query->where('is_archived', false))
                ->required()->rules('integer')->tab('params'),
            SelectField::make('type')->label(__('atrium-tracker::tracker.tasks.fields.type'))
                ->options(TaskType::options())->default(TaskType::Task->value)->required()->tab('params'),
            TextField::make('title')->label(__('atrium-tracker::tracker.tasks.fields.title'))->required()->maxLength(255)->tab('params'),
            ContentField::make('description')->label(__('atrium-tracker::tracker.tasks.fields.description'))->tab('params'),
            SelectField::make('status')->label(__('atrium-tracker::tracker.tasks.fields.status'))
                ->options(Statuses::options())->default(Statuses::first())->required()->tab('params'),
            SelectField::make('priority')->label(__('atrium-tracker::tracker.tasks.fields.priority'))
                ->options(Priority::options())->default(Priority::Medium->value)->required()->tab('params'),
            BelongsToField::make('assignee_id')->label(__('atrium-tracker::tracker.tasks.fields.assignee'))->model(AdminUser::class)
                ->rules('integer')->tab('params'),
            BelongsToField::make('parent_id')->label(__('atrium-tracker::tracker.tasks.fields.parent'))->model(self::class)->displayColumn('title')
                ->hint(__('atrium-tracker::tracker.tasks.fields.parent_hint'))
                ->rules('integer')->tab('params'),
            RepeatableField::make('labels')->label(__('atrium-tracker::tracker.tasks.fields.labels'))->itemType('text')->maxItems(20)
                ->addLabel(__('atrium-tracker::tracker.tasks.fields.add_label'))->tab('params'),
            DateField::make('due_date')->label(__('atrium-tracker::tracker.tasks.fields.due_date'))->tab('params'),
            NumberField::make('estimate_hours')->label(__('atrium-tracker::tracker.tasks.fields.estimate'))
                ->min(0)->max(99999)->decimals(2)->suffix(__('atrium-tracker::tracker.hours_short'))->tab('params'),
            GalleryField::make('attachments')->label(__('atrium-tracker::tracker.tasks.fields.attachments'))
                ->directory('tracker')->maxFiles(20)->maxSize(20480)
                ->acceptedTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'text/plain', 'text/csv', 'application/zip', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                ->tab('files'),
            TextareaField::make('new_comment')->label(__('atrium-tracker::tracker.tasks.fields.new_comment'))
                ->hint(__('atrium-tracker::tracker.tasks.fields.new_comment_hint'))
                ->rows(4)->maxLength(20000)->hideOnCreate()->tab('activity'),
            DisplayField::make('thread')->label(__('atrium-tracker::tracker.tasks.fields.thread'))
                ->html(static fn (Model $task): string => $task instanceof self ? view('atrium-tracker::admin.thread', ['entries' => $task->thread()])->render() : '')
                ->tab('activity'),
        ];
    }

    /**
     * The comments and the changes of the task, the newest first: each with
     * the time, the person, the kind (`comment`, `created`, `status` …) and
     * the text, already in the administrator's language.
     *
     * @return list<array{at:?Carbon,who:?string,kind:string,text:string}>
     */
    public function thread(): array
    {
        $entries = [];

        foreach ($this->comments()->with('author')->orderByDesc('id')->get() as $comment) {
            $entries[] = ['at' => $comment->created_at, 'who' => $comment->author?->name, 'kind' => 'comment', 'text' => $comment->body];
        }

        foreach ($this->events()->orderByDesc('id')->get() as $event) {
            $entries[] = ['at' => $event->created_at, 'who' => self::adminName($event->actor_id), 'kind' => $event->kind, 'text' => $event->describe()];
        }

        // The sort keeps the order of equal times: a comment is above the changes of its second.
        usort($entries, static fn (array $a, array $b): int => $b['at']?->getTimestamp() <=> $a['at']?->getTimestamp());

        return $entries;
    }

    public function transformAdminData(array $data): array
    {
        $this->pendingComment = trim((string) ($data['new_comment'] ?? ''));
        unset($data['new_comment']);

        $projectId = (int) ($data['project_id'] ?? $this->project_id);
        $project = Project::query()->find($projectId);

        // Only a task moving into an archived project is refused: the tasks
        // that are in one can still be edited and closed.
        if ($project?->is_archived && $projectId !== $this->project_id) {
            throw ValidationException::withMessages(['project_id' => __('atrium-tracker::tracker.errors.archived')]);
        }

        $type = $data['type'] ?? $this->type;
        $parentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : $this->parent_id;

        if ($parentId !== null) {
            $this->guardParent((int) $parentId, $projectId, $type);
        }

        $assigneeId = array_key_exists('assignee_id', $data) ? $data['assignee_id'] : $this->assignee_id;

        if ($assigneeId !== null && $project !== null && ! $project->isOpen() && ! $project->hasPerson((int) $assigneeId)) {
            throw ValidationException::withMessages(['assignee_id' => __('atrium-tracker::tracker.errors.not_in_project')]);
        }

        return $data;
    }

    /**
     * The parent is a task of the same project that is not the task itself
     * or one below it, and an epic has none.
     */
    private function guardParent(int $parentId, int $projectId, string $type): void
    {
        $parent = self::query()->find($parentId);

        $error = match (true) {
            $type === TaskType::Epic->value => 'epic_has_parent',
            $parent === null || $parent->project_id !== $projectId => 'parent_other_project',
            $this->exists && $this->isAncestorOrSelf($parent) => 'parent_loop',
            default => null,
        };

        if ($error !== null) {
            throw ValidationException::withMessages(['parent_id' => __('atrium-tracker::tracker.errors.'.$error)]);
        }
    }

    /**
     * Whether this task is $candidate or one of the tasks above it.
     */
    private function isAncestorOrSelf(self $candidate): bool
    {
        $seen = [];

        // Corrupt data may loop above $candidate: stop when a task comes twice.
        for ($task = $candidate; $task !== null && ! isset($seen[$task->getKey()]); $task = $task->parent) {
            if ($task->getKey() === $this->getKey()) {
                return true;
            }

            $seen[$task->getKey()] = $task->getKey();
        }

        return false;
    }

    public function afterAdminSave(array $data): void
    {
        if ($this->pendingComment === '') {
            return;
        }

        $this->comments()->create(['author_id' => Admins::currentId(), 'body' => $this->pendingComment]);
        $this->pendingComment = '';
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('code')->label(__('atrium-tracker::tracker.tasks.fields.code'))->value(self::describe(static fn (self $task): string => $task->code))->width('7rem'),
            TextColumn::make('title')->label(__('atrium-tracker::tracker.tasks.fields.title'))->sortable()->searchable(),
            TextColumn::make('type')->label(__('atrium-tracker::tracker.tasks.fields.type'))->value(self::describe(static fn (self $task): string => TaskType::tryFrom($task->type)?->label() ?? $task->type)),
            TextColumn::make('status')->label(__('atrium-tracker::tracker.tasks.fields.status'))->sortable()->value(self::describe(static fn (self $task): string => Statuses::label($task->status))),
            TextColumn::make('priority')->label(__('atrium-tracker::tracker.tasks.fields.priority'))->value(self::describe(static fn (self $task): string => Priority::tryFrom($task->priority)?->label() ?? $task->priority)),
            TextColumn::make('assignee.name')->label(__('atrium-tracker::tracker.tasks.fields.assignee')),
            DateColumn::make('due_date')->label(__('atrium-tracker::tracker.tasks.fields.due_date'))->sortable(),
        ];
    }

    public static function getAdministrationFilters(): array
    {
        return [
            SelectFilter::make('project_id')->label(__('atrium-tracker::tracker.tasks.fields.project'))
                ->options(fn (): array => Project::query()->orderBy('name')->orderBy('id')->pluck('name', 'id')->all()),
            SelectFilter::make('status')->label(__('atrium-tracker::tracker.tasks.fields.status'))
                ->options(fn (): array => Statuses::options()),
            SelectFilter::make('assignee_id')->label(__('atrium-tracker::tracker.tasks.fields.assignee'))
                ->options(fn (): array => Admins::names()),
            SelectFilter::make('type')->label(__('atrium-tracker::tracker.tasks.fields.type'))
                ->options(fn (): array => TaskType::options()),
            SelectFilter::make('label')->label(__('atrium-tracker::tracker.tasks.filters.label'))
                ->options(fn (): array => self::allLabels())
                ->query(static fn (Builder $query, string $value): Builder => $query->whereJsonContains('labels', $value)),
            BooleanFilter::make('overdue')->label(__('atrium-tracker::tracker.tasks.filters.overdue'))
                ->query(static fn (Builder $query, string $value): Builder => $value === '1'
                    ? $query->whereDate('due_date', '<', today())->where('status', '!=', Statuses::done())
                    : $query->where(static fn (Builder $inner): Builder => $inner->whereNull('due_date')->orWhereDate('due_date', '>=', today())->orWhere('status', Statuses::done()))),
            BooleanFilter::make('mine')->label(__('atrium-tracker::tracker.tasks.filters.mine'))
                ->query(static fn (Builder $query, string $value): Builder => $value === '1'
                    ? $query->where('assignee_id', Admins::currentId())
                    : $query->where(static fn (Builder $inner): Builder => $inner->whereNull('assignee_id')->orWhere('assignee_id', '!=', Admins::currentId()))),
        ];
    }

    /**
     * A column's value of a task; the column is never asked about another model.
     *
     * @param  Closure(self): string  $value
     * @return Closure(Model): string
     */
    private static function describe(Closure $value): Closure
    {
        return static fn (Model $record): string => $record instanceof self ? $value($record) : '';
    }

    /**
     * Every label in use, label => label.
     *
     * @return array<string,string>
     */
    private static function allLabels(): array
    {
        $labels = [];

        foreach (self::query()->whereNotNull('labels')->pluck('labels') as $list) {
            foreach ($list as $label) {
                $labels[$label] = $label;
            }
        }

        ksort($labels);

        return $labels;
    }
}
