# Tracker plugins

[English](tracker-plugins.md) · [Русский](tracker-plugins.ru.md)

A plugin is a Composer package that adds something to [Tracker](tracker.md) without changing it: a panel on the task card, a gadget, a report, a condition on a transition, an action of an automation rule. It is registered in its service provider, and an administrator can switch it off in the tracker's settings.

**Where things stand.** The registry, the contract and the eleven extension points are in place and tested. A point is *read* by the stage of the [roadmap](tracker.md#roadmap) that owns it, and what a definition has to look like is written here when that stage lands. Until then a plugin can register its definitions, and they wait. Nothing in the tracker keeps a plugin from being moved onto the Atrium extension points once the platform has them: a plugin only talks to `Plugins`.

## The contract

```php
use Atrium\Tracker\Plugins\Extensions;
use Atrium\Tracker\Plugins\TrackerPlugin;

interface TrackerPlugin
{
    public function key(): string;          // "git-links": small letters, digits, "-" and "_", up to 40
    public function title(): string;        // shown in the settings, in the language of the panel
    public function register(Extensions $extensions): void;
}
```

`register()` is called once, when the plugin is handed to the registry. It adds what the plugin brings with `$extensions->add($point, $key, $definition)`:

- `$point` is one of the constants of `Atrium\Tracker\Plugins\Plugins` (below); an unknown point throws `InvalidArgumentException`.
- `$key` is unique inside the point; start it with the key of the plugin (`git-links.commits`). A taken key throws, so two plugins never overwrite each other.
- `$definition` is whatever the point asks for: an array, a class name, a closure.

## The extension points

| Constant | What it is |
|---|---|
| `FIELD_TYPES` | A kind of custom field: how it is shown, kept and checked |
| `TRANSITION_CONDITIONS` | A rule a transition checks before it may be made |
| `TRANSITION_VALIDATORS` | A check of what the person entered on a transition |
| `TRANSITION_FUNCTIONS` | Work done after a transition |
| `AUTOMATION_TRIGGERS` | What starts an automation rule |
| `AUTOMATION_ACTIONS` | What an automation rule does |
| `QUERY_FUNCTIONS` | A function of the search language |
| `TASK_PANELS` | A panel on the card of a task |
| `GADGETS` | A gadget for a dashboard |
| `REPORTS` | A report |
| `PROJECT_TABS` | A tab of a project |

Types, statuses, priorities and resolutions are not points: they are rows an administrator edits (see [Tracker](tracker.md#types-statuses-priorities-and-resolutions)), and a plugin that needs its own type, status or resolution adds a row, for example from a migration.

## The shape of a definition

The points that *do* something (`TRANSITION_CONDITIONS`, `TRANSITION_VALIDATORS`, `TRANSITION_FUNCTIONS`, `AUTOMATION_ACTIONS`) take a class name whose class has the shape the platform plans for automation actions: `settings(): list<Field>` (the same `Atrium\Admin\Fields` classes as a form, so the settings of a step are drawn and checked like any form) and a `run(...)` that does the work with those settings. Moving them onto the platform registry is then a re-registration, not a rewrite. Query functions (`QUERY_FUNCTIONS`) are a class with a name, its arguments and `evaluate()`. The exact signatures are fixed by the stage that reads the point.

## Registering

In the service provider of the package:

```php
use Atrium\Tracker\Plugins\Plugins;

public function boot(): void
{
    $this->callAfterResolving(Plugins::class, function (Plugins $plugins): void {
        $plugins->register(new GitLinksPlugin);
    });
}
```

A key registered twice, or a key with a capital letter, a dot or a space, throws at once.

## Reading

A part of the tracker (or another plugin) asks a point for its definitions:

```php
app(Plugins::class)->extensions(Plugins::TASK_PANELS);
// ['git-links.commits' => [...], …]  by key, in the order added
```

Only the plugins that are on are read: `isEnabled($key)` is false for one an administrator switched off and for one nobody registered. `all()` lists every registered plugin, the switched-off ones too.

## Switching a plugin off

Once at least one plugin is registered, the module's settings (`/admin/modules/tracker/settings`) get a **Switched-off plugins** field with all of them. What is ticked is ignored: its definitions are not returned by `extensions()`. A new plugin is on until it is ticked. The choice is kept in the setting `tracker.disabled_plugins` (a list of keys).

## An example

A plugin that adds a panel to the task card and a gadget:

```php
namespace Acme\GitLinks;

use Atrium\Tracker\Plugins\Extensions;
use Atrium\Tracker\Plugins\Plugins;
use Atrium\Tracker\Plugins\TrackerPlugin;

final class GitLinksPlugin implements TrackerPlugin
{
    public function key(): string
    {
        return 'git-links';
    }

    public function title(): string
    {
        return __('git-links::plugin.title');
    }

    public function register(Extensions $extensions): void
    {
        $extensions->add(Plugins::TASK_PANELS, 'git-links.commits', [
            'component' => 'GitCommitsPanel',     // a Vue component of the package
            'label' => __('git-links::plugin.commits'),
        ]);

        $extensions->add(Plugins::GADGETS, 'git-links.recent', [
            'label' => __('git-links::plugin.recent'),
        ]);
    }
}
```

Test it like the tracker does: register the plugin in a test, read the point, switch it off with `Setting` and read again (see `PluginsTest` in the package).
