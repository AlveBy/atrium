<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Support;

use Atrium\Admin\Modules\Modules;
use Atrium\Admin\Support\ContentLanguages;
use Atrium\Forms\Models\Form;
use Atrium\Forms\Models\FormField;
use Atrium\Forms\Models\FormSubmission;
use Atrium\Jobs\Models\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The link to the forms module (alveby/atrium-forms), soft: without the
 * module, or without the site's form named in `atrium-jobs.form`, there are
 * no extra questions and nothing is written there.
 *
 * With it, the form's fields are asked after the standard ones, a copy of
 * the application goes to the form's submissions (and its counter), and the
 * form's recipients are told.
 */
final class FormQuestions
{
    private Form|false|null $form = null;

    public function form(): ?Form
    {
        if ($this->form === null) {
            $this->form = $this->find() ?? false;
        }

        return $this->form ?: null;
    }

    /**
     * @return Collection<int,FormField>
     */
    public function fields(): Collection
    {
        return $this->form()->fields ?? new Collection;
    }

    /**
     * @return array<string,array<int,mixed>>
     */
    public function rules(): array
    {
        $rules = [];

        foreach ($this->fields() as $field) {
            $rules["fields.{$field->name}"] = $field->publicRules();
        }

        return $rules;
    }

    /**
     * @return array<string,string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->fields() as $field) {
            $attributes["fields.{$field->name}"] = mb_strtolower((string) $field->label);
        }

        return $attributes;
    }

    /**
     * The answers with the labels in the default language, the one HR reads.
     *
     * @param  array<string,mixed>  $input
     * @return array<int,array{name:string,type:string,label:string,value:mixed}>
     */
    public function answers(array $input): array
    {
        $default = app(ContentLanguages::class)->default();
        $answers = [];

        foreach ($this->fields() as $field) {
            $value = $input[$field->name] ?? null;

            $answers[] = [
                'name' => $field->name,
                'type' => $field->type,
                'label' => (string) $field->getTranslation('label', $default),
                'value' => $field->type === 'checkbox' ? (bool) $value : (is_scalar($value) ? trim((string) $value) : null),
            ];
        }

        return $answers;
    }

    /**
     * @return array<int,string>
     */
    public function recipients(): array
    {
        return $this->form()?->recipientList() ?? [];
    }

    /**
     * A copy in the form's submissions, so the forms module counts and lists
     * it. The CV stays with the application.
     */
    public function copy(Application $application): ?int
    {
        $form = $this->form();

        if ($form === null) {
            return null;
        }

        $data = [
            ['name' => 'vacancy', 'type' => 'text', 'label' => __('atrium-jobs::jobs.fields.vacancy'), 'value' => $application->vacancy_name ?? __('atrium-jobs::jobs.public.general')],
            ['name' => 'name', 'type' => 'text', 'label' => __('atrium-jobs::jobs.fields.name'), 'value' => $application->name],
            ['name' => 'email', 'type' => 'email', 'label' => __('atrium-jobs::jobs.fields.email'), 'value' => $application->email],
            ['name' => 'phone', 'type' => 'tel', 'label' => __('atrium-jobs::jobs.fields.phone'), 'value' => $application->phone],
            ['name' => 'message', 'type' => 'textarea', 'label' => __('atrium-jobs::jobs.fields.message'), 'value' => $application->message],
            ['name' => 'cv', 'type' => 'text', 'label' => __('atrium-jobs::jobs.fields.cv'), 'value' => $application->cv_name],
            ...($application->answers ?? []),
        ];

        $submission = new FormSubmission;
        $submission->forceFill([
            'site_id' => $form->site_id,
            'form_id' => $form->id,
            'form_name' => $form->name,
            'data' => $data,
            'locale' => $application->locale,
            'page_url' => $application->page_url,
            'ip' => $application->ip,
            'user_agent' => Str::limit((string) $application->user_agent, 500, ''),
        ])->save();

        return (int) $submission->id;
    }

    private function find(): ?Form
    {
        if (! class_exists(Form::class) || ! app(Modules::class)->available(Form::class)) {
            return null;
        }

        $code = (string) config('atrium-jobs.form', 'job-application');

        return $code === '' ? null : Form::query()->where('code', $code)->where('active', true)->with('fields')->first();
    }
}
