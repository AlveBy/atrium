<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Models;

use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Atrium\Jobs\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * A letter HR sends a candidate from the application's form: an
 * invitation to an interview, a refusal. The subject and the text are
 * written per language; the candidate gets the one of the language they
 * applied in. `:name`, `:vacancy` and `:company` are replaced.
 *
 * A template may move the application to a status when it is sent (a
 * refusal to "Rejected").
 *
 * @property int $id
 * @property int|null $site_id
 * @property string $name
 * @property string|null $status
 * @property int $sort
 */
class ReplyTemplate extends Model implements Administrable
{
    use BelongsToSite, InteractsWithAdmin, Translatable;

    protected $table = 'job_reply_templates';

    /** @var array<int,string> */
    protected array $translatable = ['subject', 'body'];

    protected $fillable = ['site_id', 'name', 'subject', 'body', 'status', 'sort'];

    protected function casts(): array
    {
        return ['sort' => 'integer'];
    }

    public function setStatusAttribute(?string $value): void
    {
        $this->attributes['status'] = $value === '' ? null : $value;
    }

    /**
     * The subject or the text in a language, with the placeholders filled.
     * Falls back to the default content language when not translated.
     *
     * @param  'subject'|'body'  $attribute
     */
    public function render(string $attribute, Application $application, ?string $locale = null): string
    {
        $text = (string) $this->getTranslation($attribute, $locale ?? app()->getLocale());

        return strtr($text, [
            ':name' => $application->name,
            ':vacancy' => (string) $application->vacancy_name,
            ':company' => (string) config('app.name'),
        ]);
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-jobs::jobs.modules.templates');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-jobs::jobs.create.template');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'job_reply_templates';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'sort';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    public static function getAdministrationFields(): array
    {
        return [
            TextField::make('name')->label(__('atrium::fields.standard.name'))->required()->maxLength(255)->hint(__('atrium-jobs::jobs.hints.template_name')),
            TextField::make('subject')->label(__('atrium-jobs::jobs.fields.subject'))->required(),
            TextareaField::make('body')->label(__('atrium-jobs::jobs.fields.body'))->rows(10)->required()->hint(__('atrium-jobs::jobs.hints.template_body')),
            SelectField::make('status')->label(__('atrium-jobs::jobs.fields.template_status'))->options(ApplicationStatus::options())->clearable()->hint(__('atrium-jobs::jobs.hints.template_status')),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium::fields.standard.name'))->sortable()->searchable(),
            TextColumn::make('subject')->label(__('atrium-jobs::jobs.fields.subject'))->searchable(),
            TextColumn::make('sort')->label(__('atrium::fields.standard.sort'))->sortable(),
        ];
    }
}
