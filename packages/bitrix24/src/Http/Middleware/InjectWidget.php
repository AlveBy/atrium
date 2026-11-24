<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24\Http\Middleware;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Modules\Modules;
use Atrium\Bitrix24\Bitrix24ServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the Bitrix24 chat widget code from the settings at the end of the
 * pages of the site: no theme or layout to edit. Full HTML pages of the site
 * only, never the admin, an Inertia visit, a download or a redirect.
 */
final class InjectWidget
{
    public function __construct(private readonly Modules $modules) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->modules->isInstalled(Bitrix24ServiceProvider::MODULE) || $this->isAdmin($request) || ! $this->isHtmlPage($request, $response)) {
            return $response;
        }

        $code = trim((string) Setting::get('bitrix24.widget_code', ''));
        $content = $response->getContent();

        if ($code === '' || ! is_string($content) || ($end = strripos($content, '</body>')) === false) {
            return $response;
        }

        $response->setContent(substr($content, 0, $end).$code."\n".substr($content, $end));

        return $response;
    }

    private function isAdmin(Request $request): bool
    {
        $prefix = trim((string) config('atrium.prefix', 'admin'), '/');

        return $request->is($prefix, $prefix.'/*');
    }

    private function isHtmlPage(Request $request, Response $response): bool
    {
        return $response->getStatusCode() < 300
            && ! $request->headers->has('X-Inertia')
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && ! $response->headers->has('Content-Disposition');
    }
}
