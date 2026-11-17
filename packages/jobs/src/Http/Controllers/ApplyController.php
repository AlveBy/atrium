<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Http\Controllers;

use Atrium\Admin\Support\ContentLanguages;
use Atrium\Jobs\Mail\ApplicationMail;
use Atrium\Jobs\Models\Application;
use Atrium\Jobs\Models\Vacancy;
use Atrium\Jobs\Support\FormQuestions;
use Atrium\Jobs\Support\SpamGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * A candidate applies for a vacancy, or sends a CV without one. Anti-spam,
 * validation, the CV onto the private disk, the application stored and HR
 * told. A browser goes back to the form; a request that wants JSON gets JSON.
 */
final class ApplyController
{
    /** Where the form's errors go: one form per page, but keep it apart. */
    public const ERROR_BAG = 'jobs-apply';

    public function __invoke(Request $request, FormQuestions $questions, ContentLanguages $languages): RedirectResponse|JsonResponse
    {
        $vacancy = null;
        $vacancyId = $request->input('vacancy_id');

        if (filled($vacancyId)) {
            $vacancy = ctype_digit((string) $vacancyId) ? Vacancy::query()->find((int) $vacancyId) : null;

            if ($vacancy === null || ! $vacancy->isOpen()) {
                throw self::failed(['form' => __('atrium-jobs::jobs.public.closed')]);
            }
        }

        $spam = SpamGuard::check($request);

        // A bot that filled the trap is told it worked and learns nothing.
        if ($spam === SpamGuard::HONEYPOT) {
            return self::sent($request, $vacancy);
        }

        if ($spam === SpamGuard::TOO_FAST) {
            throw self::failed(['form' => __('atrium-jobs::jobs.public.too_fast')]);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+()\-.\s]+$/'],
            'message' => ['nullable', 'string', 'max:10000'],
            'cv' => [
                config('atrium-jobs.cv_required', true) ? 'required' : 'nullable',
                File::types((array) config('atrium-jobs.cv_types', ['pdf', 'doc', 'docx']))->max((int) config('atrium-jobs.cv_max_size', 5120)),
            ],
            'consent' => ['accepted'],
            ...$questions->rules(),
        ], [], [
            'name' => mb_strtolower(__('atrium-jobs::jobs.fields.name')),
            'email' => mb_strtolower(__('atrium-jobs::jobs.fields.email')),
            'phone' => mb_strtolower(__('atrium-jobs::jobs.fields.phone')),
            'message' => mb_strtolower(__('atrium-jobs::jobs.fields.message')),
            'cv' => mb_strtolower(__('atrium-jobs::jobs.fields.cv')),
            'consent' => mb_strtolower(__('atrium-jobs::jobs.fields.consent')),
            ...$questions->attributes(),
        ]);

        if ($validator->fails()) {
            throw self::failed($validator->errors()->toArray());
        }

        $file = $request->file('cv');
        $path = $file instanceof UploadedFile ? $file->store('jobs/cv/'.now()->format('Y/m'), Application::disk()) : null;

        if ($path === false) {
            throw self::failed(['cv' => __('atrium-jobs::jobs.public.upload_failed')]);
        }

        try {
            $application = DB::transaction(function () use ($request, $vacancy, $file, $path, $questions, $languages): Application {
                $application = new Application;
                $application->forceFill([
                    'vacancy_id' => $vacancy?->id,
                    // In the default language: the one HR reads.
                    'vacancy_name' => $vacancy === null ? null : Str::limit((string) $vacancy->getTranslation('name', $languages->default()), 250, ''),
                    'name' => trim((string) $request->input('name')),
                    'email' => trim((string) $request->input('email')),
                    'phone' => filled($request->input('phone')) ? trim((string) $request->input('phone')) : null,
                    'message' => filled($request->input('message')) ? trim((string) $request->input('message')) : null,
                    'answers' => $questions->fields()->isEmpty() ? null : $questions->answers((array) $request->input('fields', [])),
                    'cv_path' => $path,
                    'cv_name' => $file instanceof UploadedFile ? Str::limit(basename($file->getClientOriginalName()), 250, '') : null,
                    'cv_size' => $file instanceof UploadedFile ? (int) $file->getSize() : null,
                    'consent_at' => now(),
                    'locale' => app()->getLocale(),
                    'page_url' => Str::limit((string) $request->headers->get('referer'), 2000, ''),
                    'ip' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                ])->save();

                if (($copy = $questions->copy($application)) !== null) {
                    $application->forceFill(['form_submission_id' => $copy])->saveQuietly();
                }

                return $application;
            });
        } catch (Throwable $exception) {
            // No row, no file: a CV must not lie on the disk with nobody to delete it.
            if (is_string($path)) {
                Storage::disk(Application::disk())->delete($path);
            }

            throw $exception;
        }

        self::mail($application, $vacancy, $questions->recipients());

        return self::sent($request, $vacancy);
    }

    /**
     * HR, the vacancy's contact person and the form's recipients.
     *
     * @param  array<int,string>  $more
     */
    private static function mail(Application $application, ?Vacancy $vacancy, array $more): void
    {
        $recipients = collect(preg_split('/[\s,;]+/', (string) config('atrium-jobs.notify', '')) ?: [])
            ->push((string) $vacancy?->contact_email)
            ->merge($more)
            ->map(fn (string $address): string => mb_strtolower(trim($address)))
            ->filter(fn (string $address): bool => filter_var($address, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();

        if ($recipients === []) {
            return;
        }

        // A mail server that is down must not lose the application: it is
        // stored already and waits in the admin.
        try {
            Mail::to($recipients)->locale((string) config('app.locale'))->send(new ApplicationMail($application));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private static function sent(Request $request, ?Vacancy $vacancy): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => __('atrium-jobs::jobs.public.sent')]);
        }

        return back()->with('atrium-jobs.sent', $vacancy->id ?? 'general')->withFragment('apply');
    }

    /**
     * @param  array<string,mixed>  $errors
     */
    public static function failed(array $errors): ValidationException
    {
        return ValidationException::withMessages($errors)
            ->errorBag(self::ERROR_BAG)
            ->redirectTo(url()->previous().'#apply');
    }
}
