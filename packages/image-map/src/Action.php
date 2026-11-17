<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap;

/**
 * What a visitor gets from an area.
 */
enum Action: string
{
    /** The title and the short description, on hover and on focus. */
    case Tooltip = 'tooltip';

    /** A small window next to the area, on click or Enter. */
    case Popover = 'popover';

    /** A dialog over the page, with the focus kept inside. */
    case Modal = 'modal';

    /** Goes to an address: the area's own, or the linked record's page. */
    case Link = 'link';

    public function label(): string
    {
        return __('atrium-image-map::image-map.actions.'.$this->value);
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
