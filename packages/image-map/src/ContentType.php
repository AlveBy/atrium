<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap;

/**
 * What the popover or the modal window of an area shows.
 */
enum ContentType: string
{
    /** Rich text from the visual editor, cleaned of anything but text markup. */
    case Text = 'text';

    /** Custom HTML, cleaned more loosely; only for those who may set the module up. */
    case Html = 'html';

    /** The card of a record of another module: a store, a lot, an office. */
    case Record = 'record';

    public function label(): string
    {
        return __('atrium-image-map::image-map.content_types.'.$this->value);
    }

    /**
     * @return array<string,string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
