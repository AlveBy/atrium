<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Models;

use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\Field;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\ValidationException;

/**
 * A word an administrator edits that tasks are sorted by: a type, a priority,
 * a status or a resolution. A task keeps the code of one; the name is shown in
 * the administrator's language when it is a built-in one and is left empty,
 * and as written when someone gave it a name of their own.
 *
 * @property int $id
 * @property string $code
 * @property string|null $name
 * @property int $sort
 * @property bool $is_default
 */
abstract class Term extends Model implements Administrable
{
    use InteractsWithAdmin;

    public $timestamps = false;

    protected $guarded = ['id'];

    /** @var array<class-string,array<string,static>> */
    private static array $memo = [];

    /**
     * The words of the kind in order, by code: read once per request and again after a change.
     *
     * @return array<string,static>
     */
    public static function terms(): array
    {
        return self::$memo[static::class] ??= static::query()->orderBy('sort')->orderBy('id')->get()->keyBy('code')->all();
    }

    public static function flush(): void
    {
        self::$memo = [];
    }

    /**
     * Which column of a task holds the code.
     */
    abstract public static function taskColumn(): string;

    /**
     * Where the built-in names are in the language files, under "tracker.".
     */
    abstract public static function group(): string;

    /**
     * A constructor nobody overrides, so that `new static` is the kind asked for.
     *
     * @param  array<string,mixed>  $attributes
     */
    final public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(static fn () => self::flush());
        static::deleted(static fn () => self::flush());

        static::deleting(static function (self $term): void {
            if (Task::query()->where(static::taskColumn(), $term->code)->exists()) {
                throw ValidationException::withMessages(['code' => __('atrium-tracker::tracker.errors.in_use')]);
            }
        });
    }

    public function label(): string
    {
        if ($this->name) {
            return $this->name;
        }

        $key = 'atrium-tracker::tracker.'.static::group().'.'.$this->code;

        return Lang::has($key) ? __($key) : $this->code;
    }

    /**
     * code => name, in order.
     *
     * @return array<string,string>
     */
    public static function options(): array
    {
        return array_map(static fn (self $term): string => $term->label(), static::terms());
    }

    /**
     * The name of a code. One that is no longer there (it was deleted
     * before the tasks were changed) is shown as it was stored.
     */
    public static function labelOf(?string $code): string
    {
        return (static::terms()[$code ?? ''] ?? null)?->label() ?? (string) $code;
    }

    /**
     * The code a new task gets: the one marked as the default, or else the first.
     */
    public static function default(): string
    {
        foreach (static::terms() as $code => $term) {
            if ($term->is_default) {
                return $code;
            }
        }

        return (string) array_key_first(static::terms());
    }

    public function transformAdminData(array $data): array
    {
        // The code is what tasks hold: it is chosen once.
        if ($this->exists) {
            unset($data['code']);
        }

        return $data;
    }

    /**
     * The fields every kind has; a kind adds its own after them.
     *
     * @return list<Field>
     */
    protected static function commonFields(): array
    {
        return [
            TextField::make('code')->label(__('atrium-tracker::tracker.terms.fields.code'))->hint(__('atrium-tracker::tracker.terms.fields.code_hint'))
                ->required()->rules(['regex:/^[a-z0-9_]{1,40}$/'])->unique((new static)->getTable(), 'code'),
            TextField::make('name')->label(__('atrium-tracker::tracker.terms.fields.name'))->hint(__('atrium-tracker::tracker.terms.fields.name_hint'))
                ->maxLength(100),
            NumberField::make('sort')->label(__('atrium-tracker::tracker.terms.fields.sort'))->default(500),
            CheckboxField::make('is_default')->label(__('atrium-tracker::tracker.terms.fields.default'))->hint(__('atrium-tracker::tracker.terms.fields.default_hint'))->default(false),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('code')->label(__('atrium-tracker::tracker.terms.fields.code'))->sortable()->searchable(),
            TextColumn::make('name')->label(__('atrium-tracker::tracker.terms.fields.name'))->value(static fn (Model $term): string => static::labelOf((string) $term->getAttribute('code'))),
            TextColumn::make('sort')->label(__('atrium-tracker::tracker.terms.fields.sort'))->sortable(),
        ];
    }

    public static function getAdminRoutePrefix(): string
    {
        return (new static)->getTable();
    }

    public static function getDefaultSortColumn(): string
    {
        return 'sort';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    public static function getImportKeys(): array
    {
        return ['code'];
    }
}
