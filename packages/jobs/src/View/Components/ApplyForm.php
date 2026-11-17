<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\View\Components;

use Atrium\Admin\Support\ContentLanguages;
use Atrium\Jobs\Http\Controllers\ApplyController;
use Atrium\Jobs\Jobs;
use Atrium\Jobs\Models\Vacancy;
use Atrium\Jobs\Support\FormQuestions;
use Atrium\Jobs\Support\SpamGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;

/**
 * <x-atrium-jobs::apply-form :vacancy="$vacancy" /> — the application form
 * of a vacancy; without one, the "send us your CV" form. A closed vacancy
 * gets no form.
 */
final class ApplyForm extends Component
{
    public function __construct(public ?Vacancy $vacancy = null) {}

    public function render(): View|string
    {
        if (! Jobs::installed() || ! Route::has('atrium.jobs.apply') || ($this->vacancy !== null && ! $this->vacancy->isOpen())) {
            return '';
        }

        $session = request()->hasSession() ? request()->session() : null;
        $errors = $session?->get('errors');
        $key = (string) ($this->vacancy->id ?? 'general');
        $own = $session !== null && (string) $session->getOldInput('_apply') === $key;
        $types = (array) config('atrium-jobs.cv_types', ['pdf', 'doc', 'docx']);

        return view('atrium-jobs::components.apply-form', [
            'vacancy' => $this->vacancy,
            'id' => 'apply-'.$key,
            'key' => $key,
            'action' => app(ContentLanguages::class)->route('atrium.jobs.apply'),
            // Errors and old input belong to the form that was sent.
            'errors' => $own && $errors instanceof ViewErrorBag ? $errors->getBag(ApplyController::ERROR_BAG) : new MessageBag,
            'old' => fn (string $name, mixed $default = ''): mixed => $own ? $session->getOldInput($name, $default) : $default,
            'sent' => $session !== null && (string) $session->get('atrium-jobs.sent') === $key,
            'questions' => app(FormQuestions::class)->fields(),
            'accept' => implode(',', array_map(fn (string $type): string => '.'.ltrim($type, '.'), $types)),
            'types' => implode(', ', array_map('strtoupper', $types)),
            'maxSize' => max(1, (int) round((int) config('atrium-jobs.cv_max_size', 5120) / 1024)),
            'cvRequired' => (bool) config('atrium-jobs.cv_required', true),
            'privacyUrl' => filled(config('atrium-jobs.privacy_url')) ? (string) config('atrium-jobs.privacy_url') : null,
            'honeypot' => SpamGuard::honeypotName(),
            'timeField' => SpamGuard::TIME_FIELD,
            'token' => SpamGuard::token(),
        ]);
    }
}
