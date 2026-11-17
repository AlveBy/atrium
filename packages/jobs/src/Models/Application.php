<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Models;

use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Forms\Models\FormSubmission;
use Atrium\Jobs\Enums\ApplicationStatus;
use Atrium\Jobs\Mail\ReplyMail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Throwable;

/**
 * What a candidate sent: their name and contacts, a cover letter, a CV on
 * the private disk and the answers to the extra questions. HR moves it
 * through the pipeline (new → reviewing → interview → offer → hired, or
 * rejected), keeps notes and replies from templates. It cannot be created
 * in the admin, and it is deleted with its CV after the retention period.
 *
 * @property int $id
 * @property int|null $site_id
 * @property int|null $vacancy_id
 * @property string|null $vacancy_name
 * @property ApplicationStatus $status
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $message
 * @property array<int,array{name:string,label:string,value:mixed}>|null $answers
 * @property string|null $cv_path
 * @property string|null $cv_name
 * @property int|null $cv_size
 * @property string|null $notes
 * @property Carbon|null $consent_at
 * @property string|null $locale
 * @property string|null $page_url
 * @property string|null $ip
 * @property string|null $user_agent
 * @property int|null $form_submission_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Vacancy|null $vacancy
 */
class Application extends Model implements Administrable
{
    use BelongsToSite, InteractsWithAdmin;

    protected $table = 'job_applications';

    /** The admin changes the status and the notes; the rest comes from the site. */
    protected $fillable = ['status', 'notes'];

    /** @var list<string> */
    protected $appends = ['status_label', 'submitted_at', 'cv_url', 'cv_label', 'answers_text', 'contacts'];

    /** @var array<string,mixed> */
    protected $attributes = ['status' => 'new'];

    /** The reply chosen on the form, sent once the record is saved. */
    private ?int $replyTemplateId = null;

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'answers' => 'array',
            'consent_at' => 'datetime',
            'cv_size' => 'integer',
            'vacancy_id' => 'integer',
            'form_submission_id' => 'integer',
        ];
    }

    /**
     * The CV and the copy in the forms module go with the application.
     */
    protected static function booted(): void
    {
        static::deleted(function (Application $application): void {
            if ($application->cv_path !== null) {
                Storage::disk(self::disk())->delete($application->cv_path);
            }

            if ($application->form_submission_id !== null && Schema::hasTable('form_submissions')) {
                $submission = class_exists(FormSubmission::class)
                    ? FormSubmission::query()->withoutGlobalScopes()->find($application->form_submission_id)
                    : null;

                $submission !== null
                    ? $submission->delete()
                    : DB::table('form_submissions')->where('id', $application->form_submission_id)->delete();
            }
        });
    }

    public static function disk(): string
    {
        return (string) config('atrium-jobs.disk', 'local');
    }

    /**
     * @return BelongsTo<Vacancy,$this>
     */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getSubmittedAtAttribute(): string
    {
        return (string) $this->created_at?->format('Y-m-d H:i');
    }

    public function getContactsAttribute(): string
    {
        return implode(', ', array_filter([$this->email, $this->phone]));
    }

    /**
     * Where the admin downloads the CV; null without one.
     */
    public function getCvUrlAttribute(): ?string
    {
        return $this->cv_path !== null && Route::has('admin.job_applications.cv')
            ? route('admin.job_applications.cv', $this->id)
            : null;
    }

    public function getCvLabelAttribute(): ?string
    {
        if ($this->cv_path === null) {
            return null;
        }

        return trim($this->cv_name.($this->cv_size ? ' ('.Number::fileSize($this->cv_size).')' : ''));
    }

    /**
     * "Question: answer" lines of the extra questions.
     */
    public function getAnswersTextAttribute(): string
    {
        return collect($this->answers ?? [])
            ->map(fn (array $item): string => $item['label'].': '.self::text($item['value'] ?? null))
            ->implode("\n");
    }

    public static function text(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? __('atrium-jobs::jobs.public.yes') : __('atrium-jobs::jobs.public.no'),
            is_scalar($value) => (string) $value,
            default => '',
        };
    }

    /**
     * The list narrowed by status (`?status=interview`) or vacancy
     * (`?vacancy_id=3`): the pipeline gadget on the dashboard links here.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scopeAdminList(Builder $query): Builder
    {
        $status = request()->query('status');

        if (is_string($status) && ApplicationStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        $vacancy = request()->query('vacancy_id');

        if (is_string($vacancy) && ctype_digit($vacancy)) {
            $query->where('vacancy_id', (int) $vacancy);
        }

        return $query;
    }

    public static function getAdministrationFilters(): array
    {
        return ['status', 'vacancy_id'];
    }

    /**
     * Applications come from the site only.
     */
    public static function isCreatableInAdmin(): bool
    {
        return false;
    }

    public function getAdminTitle(): string
    {
        return $this->name;
    }

    /**
     * The reply is not a column: it is taken out here and sent after the save.
     */
    public function transformAdminData(array $data): array
    {
        $template = $data['reply_template_id'] ?? null;
        $this->replyTemplateId = is_numeric($template) ? (int) $template : null;
        unset($data['reply_template_id']);

        return $data;
    }

    public function afterAdminSave(array $data): void
    {
        $id = $this->replyTemplateId;
        $this->replyTemplateId = null;

        $template = $id === null ? null : ReplyTemplate::query()->find($id);

        if ($template instanceof ReplyTemplate) {
            $this->reply($template);
        }
    }

    /**
     * Mail the template to the candidate in the language they applied in,
     * note it, and move the application to the template's status.
     */
    public function reply(ReplyTemplate $template): bool
    {
        $sent = true;

        try {
            Mail::to($this->email, $this->name)->send(new ReplyMail($this, $template));
        } catch (Throwable $exception) {
            report($exception);
            $sent = false;
        }

        $admin = auth()->guard((string) config('atrium.auth.guard', 'admin'))->user();
        $line = now()->format('Y-m-d H:i').' — '.__($sent ? 'atrium-jobs::jobs.notes.reply_sent' : 'atrium-jobs::jobs.notes.reply_failed', [
            'template' => $template->name,
            'admin' => $admin instanceof AdminUser ? $admin->name : 'API',
        ]);

        $this->notes = trim(trim((string) $this->notes)."\n".$line);

        if ($sent && $template->status !== null && ($status = ApplicationStatus::tryFrom($template->status)) !== null) {
            $this->status = $status;
        }

        $this->save();

        return $sent;
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-jobs::jobs.modules.applications');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'job_applications';
    }

    public static function getAdministrationFields(): array
    {
        $templates = ReplyTemplate::query()->orderBy('sort')->orderBy('id')->pluck('name', 'id')->all();

        return [
            SelectField::make('status')->label(__('atrium-jobs::jobs.fields.status'))->options(ApplicationStatus::options())->required()->hideOnCreate(),
            TextareaField::make('notes')->label(__('atrium-jobs::jobs.fields.notes'))->rows(6)->hint(__('atrium-jobs::jobs.hints.notes'))->hideOnCreate(),
            ...($templates === [] ? [] : [
                SelectField::make('reply_template_id')->label(__('atrium-jobs::jobs.fields.reply'))->options($templates)->clearable()->hint(__('atrium-jobs::jobs.hints.reply'))->onlyOnEdit(),
            ]),
            TextField::make('vacancy_name')->label(__('atrium-jobs::jobs.fields.vacancy'))->placeholder(__('atrium-jobs::jobs.public.general'))->readonly()->hideOnCreate(),
            TextField::make('submitted_at')->label(__('atrium-jobs::jobs.fields.submitted_at'))->readonly()->hideOnCreate(),
            TextField::make('name')->label(__('atrium-jobs::jobs.fields.name'))->readonly()->hideOnCreate(),
            TextField::make('email')->label(__('atrium-jobs::jobs.fields.email'))->readonly()->hideOnCreate(),
            TextField::make('phone')->label(__('atrium-jobs::jobs.fields.phone'))->readonly()->hideOnCreate(),
            TextareaField::make('message')->label(__('atrium-jobs::jobs.fields.message'))->rows(8)->readonly()->hideOnCreate(),
            TextareaField::make('answers_text')->label(__('atrium-jobs::jobs.fields.answers'))->rows(4)->readonly()->hideOnCreate(),
            TextField::make('cv_url')->label(__('atrium-jobs::jobs.fields.cv'))->placeholder(__('atrium-jobs::jobs.public.no_cv'))->hint(__('atrium-jobs::jobs.hints.cv'))->readonly()->hideOnCreate(),
            TextField::make('locale')->label(__('atrium-jobs::jobs.fields.locale'))->readonly()->hideOnCreate(),
            TextField::make('page_url')->label(__('atrium-jobs::jobs.fields.page_url'))->readonly()->hideOnCreate(),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('submitted_at')->label(__('atrium-jobs::jobs.fields.submitted_at')),
            TextColumn::make('name')->label(__('atrium-jobs::jobs.fields.name'))->searchable(),
            TextColumn::make('email')->label(__('atrium-jobs::jobs.fields.email'))->searchable(),
            TextColumn::make('vacancy_name')->label(__('atrium-jobs::jobs.fields.vacancy'))->searchable()->default(__('atrium-jobs::jobs.public.general')),
            TextColumn::make('status_label')->label(__('atrium-jobs::jobs.fields.status')),
            TextColumn::make('cv_label')->label(__('atrium-jobs::jobs.fields.cv'))->urlFrom('cv_url'),
        ];
    }
}
