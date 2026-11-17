<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Modules\ModuleSettings;

/**
 * What can be set for image maps, for all sites at once.
 *
 * The permission to open these settings, `image_map.settings`, is also the
 * permission to write custom HTML in areas: both are for those trusted
 * with what a page may load.
 */
final class ImageMapSettings extends ModuleSettings
{
    public const DEFAULT_IFRAME_HOSTS = "www.youtube-nocookie.com\nwww.youtube.com\nplayer.vimeo.com\nwww.google.com\nyandex.ru\nyandex.com";

    public function description(): string
    {
        return __('atrium-image-map::image-map.settings.description');
    }

    public function fields(): array
    {
        return [
            TextareaField::make('iframe_hosts')
                ->label(__('atrium-image-map::image-map.settings.iframe_hosts'))
                ->hint(__('atrium-image-map::image-map.settings.iframe_hosts_hint'))
                ->rules(['nullable', 'string', 'max:2000'])
                ->default(self::DEFAULT_IFRAME_HOSTS),
        ];
    }

    /**
     * Hosts an iframe in custom HTML may load from, one per line or comma.
     *
     * @return array<int,string>
     */
    public static function iframeHosts(): array
    {
        $value = Setting::get('image_map.iframe_hosts', self::DEFAULT_IFRAME_HOSTS);

        return array_values(array_filter(array_map(
            static fn (string $host): string => strtolower(trim($host)),
            preg_split('/[\s,]+/', is_string($value) ? $value : '') ?: [],
        )));
    }
}
