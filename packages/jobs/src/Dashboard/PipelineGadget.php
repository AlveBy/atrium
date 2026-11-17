<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Dashboard;

use Atrium\Admin\Dashboard\Gadget;
use Atrium\Admin\Models\AdminUser;
use Atrium\Jobs\Enums\ApplicationStatus;
use Atrium\Jobs\Jobs;
use Atrium\Jobs\Models\Application;
use Illuminate\Support\Facades\Route;

/**
 * The applications by stage of the pipeline: how many are new, in review,
 * at the interview… Each line opens the list filtered to that stage. Drawn
 * by the panel's "recent items" tile, so no frontend of its own.
 */
final class PipelineGadget extends Gadget
{
    public function component(): string
    {
        return 'RecentElementsGadget';
    }

    public function title(): string
    {
        return (string) ($this->option('title') ?? __('atrium-jobs::jobs.dashboard.title'));
    }

    public function isVisible(): bool
    {
        $user = auth()->guard((string) config('atrium.auth.guard', 'admin'))->user();

        return parent::isVisible()
            && Jobs::installed()
            && Route::has('admin.job_applications.index')
            && $user instanceof AdminUser
            && $user->hasAdminPermission('job_applications.view');
    }

    public function data(): array
    {
        $counts = Application::query()->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $items = [];

        foreach (ApplicationStatus::cases() as $status) {
            $items[] = [
                'id' => $status->value,
                'image' => null,
                'date' => null,
                'name' => $status->label().': '.(int) ($counts[$status->value] ?? 0),
                'url' => route('admin.job_applications.index', ['status' => $status->value]),
            ];
        }

        return [
            'items' => $items,
            'allUrl' => route('admin.job_applications.index'),
            'allLabel' => __('atrium-jobs::jobs.dashboard.all'),
        ];
    }
}
