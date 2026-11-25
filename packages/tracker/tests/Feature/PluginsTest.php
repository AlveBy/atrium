<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Admin\Modules\Settings;
use Atrium\Tracker\Plugins\Extensions;
use Atrium\Tracker\Plugins\Plugins;
use Atrium\Tracker\Plugins\TrackerPlugin;
use Atrium\Tracker\Tests\TestCase;
use Inertia\Testing\AssertableInertia;
use InvalidArgumentException;

/**
 * A package registers a plugin; the tracker reads what it added from the
 * extension points, and an administrator can switch it off.
 */
final class PluginsTest extends TestCase
{
    private function plugin(string $key = 'git-links', string $title = 'Git links', ?callable $register = null): TrackerPlugin
    {
        return new class($key, $title, $register) implements TrackerPlugin
        {
            /** @param  callable(Extensions):void|null  $register */
            public function __construct(private string $key, private string $title, private $register) {}

            public function key(): string
            {
                return $this->key;
            }

            public function title(): string
            {
                return $this->title;
            }

            public function register(Extensions $extensions): void
            {
                if ($this->register !== null) {
                    ($this->register)($extensions);
                }
            }
        };
    }

    private function plugins(): Plugins
    {
        return $this->app->make(Plugins::class);
    }

    public function test_the_registry_is_one_for_the_application(): void
    {
        $this->assertSame($this->plugins(), $this->app->make(Plugins::class));
        $this->assertSame([], $this->plugins()->all());
    }

    public function test_a_plugin_adds_what_it_brings_to_the_points(): void
    {
        $this->plugins()->register($this->plugin(register: function (Extensions $extensions): void {
            $extensions->add(Plugins::TASK_PANELS, 'git-links.commits', ['component' => 'Commits']);
            $extensions->add(Plugins::REPORTS, 'git-links.commits', 'report');
        }));

        $this->assertSame(['git-links'], array_keys($this->plugins()->all()));
        $this->assertSame(['git-links.commits' => ['component' => 'Commits']], $this->plugins()->extensions(Plugins::TASK_PANELS));
        $this->assertSame(['git-links.commits' => 'report'], $this->plugins()->extensions(Plugins::REPORTS));
        $this->assertSame([], $this->plugins()->extensions(Plugins::GADGETS));
        $this->assertSame([], $this->plugins()->extensions('nothing'));
    }

    public function test_the_extensions_of_two_plugins_are_read_together_in_the_order_added(): void
    {
        $this->plugins()->register($this->plugin('one', register: fn (Extensions $e) => $e->add(Plugins::GADGETS, 'one.a', 1)));
        $this->plugins()->register($this->plugin('two', register: fn (Extensions $e) => $e->add(Plugins::GADGETS, 'two.a', 2)));

        $this->assertSame(['one.a' => 1, 'two.a' => 2], $this->plugins()->extensions(Plugins::GADGETS));
    }

    public function test_a_plugin_is_on_until_an_administrator_switches_it_off(): void
    {
        $this->plugins()->register($this->plugin('one', register: fn (Extensions $e) => $e->add(Plugins::GADGETS, 'one.a', 1)));
        $this->plugins()->register($this->plugin('two', register: fn (Extensions $e) => $e->add(Plugins::GADGETS, 'two.a', 2)));

        $this->assertTrue($this->plugins()->isEnabled('one'));
        $this->assertSame([], $this->plugins()->disabled());

        $this->settings(['disabled_plugins' => ['one']]);

        $this->assertFalse($this->plugins()->isEnabled('one'));
        $this->assertTrue($this->plugins()->isEnabled('two'));
        $this->assertSame(['one'], $this->plugins()->disabled());
        $this->assertSame(['two.a' => 2], $this->plugins()->extensions(Plugins::GADGETS));
        $this->assertSame(['one', 'two'], array_keys($this->plugins()->all()), 'A switched-off plugin is still known.');
    }

    public function test_a_plugin_nobody_registered_is_not_on(): void
    {
        $this->assertFalse($this->plugins()->isEnabled('ghost'));
    }

    public function test_an_odd_list_of_switched_off_plugins_switches_nothing_off(): void
    {
        $this->plugins()->register($this->plugin('one'));

        foreach (['one', 5, null] as $value) {
            $this->settings(['disabled_plugins' => $value]);
            $this->assertSame([], $this->plugins()->disabled());
            $this->assertTrue($this->plugins()->isEnabled('one'));
        }

        $this->settings(['disabled_plugins' => [1, 'one', null]]);
        $this->assertSame(['one'], $this->plugins()->disabled());
    }

    public function test_the_key_of_a_plugin_is_small_letters_digits_dashes_and_underscores(): void
    {
        foreach (['', 'Big', 'with space', 'dot.ted', str_repeat('a', 41)] as $key) {
            try {
                $this->plugins()->register($this->plugin($key));
                $this->fail("\"{$key}\" was accepted");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString("\"{$key}\" is not a key of a plugin", $e->getMessage());
            }
        }

        $this->assertSame([], $this->plugins()->all());

        foreach (['a', 'git-links', 'git_links2', str_repeat('a', 40)] as $key) {
            $this->plugins()->register($this->plugin($key));
        }

        $this->assertCount(4, $this->plugins()->all());
    }

    public function test_a_plugin_is_registered_once(): void
    {
        $this->plugins()->register($this->plugin());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The plugin "git-links" is registered already.');
        $this->plugins()->register($this->plugin());
    }

    public function test_a_point_the_tracker_does_not_have_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The tracker has no extension point "colours".');
        $this->plugins()->register($this->plugin(register: fn (Extensions $e) => $e->add('colours', 'git-links.red', 1)));
    }

    public function test_a_key_is_taken_once_in_a_point_but_may_be_used_in_another(): void
    {
        $this->plugins()->register($this->plugin('one', register: function (Extensions $e): void {
            $e->add(Plugins::GADGETS, 'same', 1);
            $e->add(Plugins::REPORTS, 'same', 2);
        }));

        try {
            $this->plugins()->register($this->plugin('two', register: fn (Extensions $e) => $e->add(Plugins::GADGETS, 'same', 3)));
            $this->fail('A key was taken twice.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('"same" is in the point "gadgets" already.', $e->getMessage());
        }

        $this->assertSame(['same' => 1], $this->plugins()->extensions(Plugins::GADGETS));
        $this->assertSame(['same' => 2], $this->plugins()->extensions(Plugins::REPORTS));
    }

    public function test_the_points_are_all_listed_and_different(): void
    {
        $constants = (new \ReflectionClass(Plugins::class))->getConstants();
        unset($constants['POINTS']);

        $this->assertCount(11, $constants);
        $this->assertSame(array_values($constants), Plugins::POINTS);
        $this->assertCount(11, array_unique(Plugins::POINTS));
    }

    public function test_the_settings_offer_the_plugins_only_when_there_are_some(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/modules/tracker/settings')->assertInertia(function (AssertableInertia $page): void {
            $this->assertSame(['notify_assigned', 'notify_status'], array_column($page->toArray()['props']['fields'], 'name'));
        });

        $this->plugins()->register($this->plugin());
        $this->plugins()->register($this->plugin('other', 'Other one'));

        $this->get('/admin/modules/tracker/settings')->assertInertia(function (AssertableInertia $page): void {
            $field = collect($page->toArray()['props']['fields'])->firstWhere('name', 'disabled_plugins');

            $this->assertSame('multiSelect', $field['type']);
            $this->assertSame([['value' => 'git-links', 'label' => 'Git links'], ['value' => 'other', 'label' => 'Other one']], $field['options']);
        });
    }

    public function test_the_settings_switch_a_plugin_off(): void
    {
        $this->plugins()->register($this->plugin());
        $this->plugins()->register($this->plugin('other', 'Other one'));

        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/modules/tracker/settings', ['notify_assigned' => true, 'notify_status' => true, 'disabled_plugins' => ['other']])
            ->assertSessionDoesntHaveErrors();
        $this->app->make(Settings::class)->flush();

        $this->assertSame(['other'], $this->plugins()->disabled());
        $this->assertTrue($this->plugins()->isEnabled('git-links'));
    }
}
