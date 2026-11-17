<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs;

use Atrium\Jobs\Console\PruneCommand;
use Atrium\Jobs\Dashboard\PipelineGadget;
use Atrium\Jobs\Http\Controllers\ApplyController;
use Atrium\Jobs\Http\Controllers\CvController;
use Atrium\Jobs\Models\Vacancy;
use Atrium\Jobs\Support\FormQuestions;
use Atrium\Jobs\Support\JobPosting;
use Atrium\Theme\Pages;
use Atrium\Theme\RecordView;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class JobsServiceProvider extends ServiceProvider
{
    /**
     * The models, the /jobs pages and the migrations come from extra.atrium
     * in composer.json, once `atrium:module:install alveby/atrium-jobs` has run.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/atrium-jobs.php', 'atrium-jobs');
        $this->app->singleton(Jobs::class);
        $this->app->singleton(JobPosting::class);
        // Per request: the form is looked up once and not kept between tests or jobs.
        $this->app->scoped(FormQuestions::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-jobs');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'atrium-jobs');
        Blade::componentNamespace('Atrium\\Jobs\\View\\Components', 'atrium-jobs');

        RateLimiter::for('atrium-jobs', function (Request $request): Limit {
            return Limit::perHour(max(1, (int) config('atrium-jobs.per_hour', 10)))
                ->by($request->ip() ?? '')
                ->response(function (Request $request, array $headers) {
                    $message = __('atrium-jobs::jobs.public.too_many');

                    if ($request->expectsJson()) {
                        return response()->json(['message' => $message], 429, $headers);
                    }

                    throw ApplyController::failed(['form' => $message]);
                });
        });

        // Once the panel has booted: Route::localized() is its macro.
        $this->app->booted(function (): void {
            if ($this->app->routesAreCached() || ! Jobs::installed()) {
                return;
            }

            Route::middleware(['web', 'throttle:atrium-jobs'])->group(function (): void {
                Route::localized(function (): void {
                    Route::post(trim((string) config('atrium-jobs.apply_path', 'jobs/apply'), '/'), ApplyController::class)
                        ->name('atrium.jobs.apply');
                });
            });

            // The CV is on a private disk: only through the admin, with the right to see applications.
            Route::prefix((string) config('atrium.prefix', 'admin'))
                ->middleware([...(array) config('atrium.middleware', ['web']), 'atrium.site', 'atrium.inertia', 'admin.auth', 'admin.can'])
                ->group(function (): void {
                    Route::get('job_applications/{application}/cv', CvController::class)
                        ->whereNumber('application')
                        ->name('admin.job_applications.cv');
                });

            // The pipeline on the dashboard, unless the site lists its tiles itself with it.
            $gadgets = (array) config('atrium.dashboard.gadgets', []);
            $listed = collect($gadgets)->contains(fn (mixed $entry): bool => (is_array($entry) ? ($entry['gadget'] ?? null) : $entry) === PipelineGadget::class);

            if (! $listed && config('atrium-jobs.dashboard', true)) {
                config(['atrium.dashboard.gadgets' => [...$gadgets, ['gadget' => PipelineGadget::class, 'width' => 'half']]]);
            }
        });

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('atrium:jobs:prune')->daily()->withoutOverlapping();
        });

        $this->composers();

        if ($this->app->runningInConsole()) {
            $this->commands([PruneCommand::class]);

            $this->publishes([
                __DIR__.'/../config/atrium-jobs.php' => config_path('atrium-jobs.php'),
            ], 'atrium-jobs-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/atrium-jobs'),
            ], 'atrium-jobs-views');

            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/atrium-jobs'),
            ], 'atrium-jobs-lang');
        }
    }

    /**
     * The theme draws the pages (Vacancy::themeView) with the vacancies of
     * the list; the filter and the structured data are added here.
     */
    private function composers(): void
    {
        View::composer('atrium-jobs::index', function (\Illuminate\View\View $view): void {
            $jobs = $this->app->make(Jobs::class);
            $filters = $jobs->filters(request());
            $pages = $this->app->make(Pages::class);

            $records = $jobs->filtered($filters)->paginate((int) config('atrium-jobs.per_page', 20))->withQueryString();
            $records->setCollection($records->getCollection()->map(fn (Model $record): RecordView => new RecordView($record, $pages)));

            $view->with([
                'records' => $records,
                'filters' => $filters,
                'options' => $jobs->options(),
                'filtered' => array_filter($filters) !== [],
                'anyOpen' => $records->total() > 0 || $jobs->open()->exists(),
            ]);
        });

        View::composer('atrium-jobs::vacancy', function (\Illuminate\View\View $view): void {
            $page = $view->getData()['page'] ?? null;
            $vacancy = $page instanceof RecordView && $page->record instanceof Vacancy ? $page->record : null;

            $view->with([
                'vacancy' => $vacancy?->loadMissing(['department', 'location']),
                'jsonLd' => $vacancy !== null && $vacancy->isOpen() ? $this->app->make(JobPosting::class)->jsonLd($vacancy, $page->url()) : '',
            ]);
        });
    }
}
