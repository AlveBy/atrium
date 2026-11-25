<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Plugins;

/**
 * A plugin of the tracker. It is a small class a composer package hands to
 * {@see Plugins::register()} from its service provider; there it adds what it
 * brings to the extension points, and an administrator can switch it off in
 * the tracker's settings.
 */
interface TrackerPlugin
{
    /**
     * A short stable name, small letters, digits, "-" and "_": "git-links".
     */
    public function key(): string;

    /**
     * The name shown in the settings, in the language of the panel.
     */
    public function title(): string;

    /**
     * Add what the plugin brings: `$extensions->add(Plugins::TASK_PANELS, 'git.commits', [...])`.
     */
    public function register(Extensions $extensions): void;
}
