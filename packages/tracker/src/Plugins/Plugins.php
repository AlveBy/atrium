<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Plugins;

use Atrium\Admin\Facades\Setting;
use InvalidArgumentException;

/**
 * The plugins of the tracker and what they added to the extension points.
 * Everything the tracker lets be extended is read from here, so that what
 * ships with the tracker and what a package adds go the same way.
 */
final class Plugins
{
    /** A kind of custom field: how it is shown, kept and checked. */
    public const FIELD_TYPES = 'field_types';

    /** A rule a transition checks before it may be made. */
    public const TRANSITION_CONDITIONS = 'transition_conditions';

    /** A check of what the person entered on a transition. */
    public const TRANSITION_VALIDATORS = 'transition_validators';

    /** Work done after a transition. */
    public const TRANSITION_FUNCTIONS = 'transition_functions';

    /** What starts an automation rule. */
    public const AUTOMATION_TRIGGERS = 'automation_triggers';

    /** What an automation rule does. */
    public const AUTOMATION_ACTIONS = 'automation_actions';

    /** A function of the search language. */
    public const QUERY_FUNCTIONS = 'query_functions';

    /** A panel on the card of a task. */
    public const TASK_PANELS = 'task_panels';

    /** A gadget for a dashboard. */
    public const GADGETS = 'gadgets';

    /** A report. */
    public const REPORTS = 'reports';

    /** A tab of a project. */
    public const PROJECT_TABS = 'project_tabs';

    public const POINTS = [
        self::FIELD_TYPES, self::TRANSITION_CONDITIONS, self::TRANSITION_VALIDATORS, self::TRANSITION_FUNCTIONS,
        self::AUTOMATION_TRIGGERS, self::AUTOMATION_ACTIONS, self::QUERY_FUNCTIONS, self::TASK_PANELS,
        self::GADGETS, self::REPORTS, self::PROJECT_TABS,
    ];

    /** @var array<string,TrackerPlugin> */
    private array $plugins = [];

    /** @var array<string,array<string,array{plugin:string,definition:mixed}>> */
    private array $points = [];

    public function register(TrackerPlugin $plugin): void
    {
        $key = $plugin->key();

        if (preg_match('/^[a-z0-9_-]{1,40}$/', $key) !== 1) {
            throw new InvalidArgumentException("\"{$key}\" is not a key of a plugin: small letters, digits, \"-\" and \"_\".");
        }

        if (isset($this->plugins[$key])) {
            throw new InvalidArgumentException("The plugin \"{$key}\" is registered already.");
        }

        $this->plugins[$key] = $plugin;
        $plugin->register(new Extensions($this, $key));
    }

    /**
     * Every plugin by key, the switched-off ones too.
     *
     * @return array<string,TrackerPlugin>
     */
    public function all(): array
    {
        return $this->plugins;
    }

    public function isEnabled(string $key): bool
    {
        return isset($this->plugins[$key]) && ! in_array($key, $this->disabled(), true);
    }

    /**
     * The keys an administrator has switched off. A plugin that is new is on.
     *
     * @return list<string>
     */
    public function disabled(): array
    {
        $disabled = Setting::get('tracker.disabled_plugins', []);

        return is_array($disabled) ? array_values(array_filter($disabled, 'is_string')) : [];
    }

    /**
     * The definitions of a point by key, from the plugins that are on.
     *
     * @return array<string,mixed>
     */
    public function extensions(string $point): array
    {
        $found = [];

        foreach ($this->points[$point] ?? [] as $key => $entry) {
            if ($this->isEnabled($entry['plugin'])) {
                $found[$key] = $entry['definition'];
            }
        }

        return $found;
    }

    /**
     * @internal Use {@see Extensions::add()}.
     */
    public function put(string $point, string $key, string $plugin, mixed $definition): void
    {
        if (isset($this->points[$point][$key])) {
            throw new InvalidArgumentException("\"{$key}\" is in the point \"{$point}\" already.");
        }

        $this->points[$point][$key] = ['plugin' => $plugin, 'definition' => $definition];
    }
}
