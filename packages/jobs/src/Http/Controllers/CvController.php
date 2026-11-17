<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Http\Controllers;

use Atrium\Admin\Models\AdminUser;
use Atrium\Jobs\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The candidate's CV, from the private disk, to an admin who may see
 * applications. The route's name is not one of the CRUD names the panel
 * checks by itself, so the right is checked here.
 */
final class CvController
{
    public function __invoke(Request $request, int $application): StreamedResponse
    {
        $user = $request->user((string) config('atrium.auth.guard', 'admin'));

        abort_unless($user instanceof AdminUser && $user->hasAdminPermission('job_applications.view'), 403, __('atrium::permissions.forbidden'));

        // Through the model: the site scope keeps another site's CVs out.
        $record = Application::query()->findOrFail($application);
        $disk = Storage::disk(Application::disk());

        abort_if($record->cv_path === null || ! $disk->exists($record->cv_path), 404);

        return $disk->download($record->cv_path, $record->cv_name ?: basename($record->cv_path), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
