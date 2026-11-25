<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Plugins;

use InvalidArgumentException;

/**
 * What one plugin adds to the extension points. A point holds definitions by
 * key; what a definition looks like is told by the part of the tracker that
 * reads the point.
 */
final class Extensions
{
    public function __construct(private readonly Plugins $plugins, private readonly string $plugin) {}

    /**
     * @param  string  $point  one of the Plugins::* constants
     * @param  string  $key  unique in the point; start it with the key of the plugin: "git-links.commits"
     */
    public function add(string $point, string $key, mixed $definition): void
    {
        if (! in_array($point, Plugins::POINTS, true)) {
            throw new InvalidArgumentException("The tracker has no extension point \"{$point}\".");
        }

        $this->plugins->put($point, $key, $this->plugin, $definition);
    }
}
