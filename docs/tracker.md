# Tracker

[English](tracker.md) · [Русский](tracker.ru.md)

`alveby/atrium-tracker` keeps the work of a team in the admin panel: projects, tasks in them, comments, a history of what changed and a letter to the person a task is given to. It is the small kind of Jira. It is used by administrators and only by them: what each may see and do follows their roles and permissions, and nothing of it reaches the site.

## Roadmap

The goal is what Jira and Tempo do for a team, inside the Atrium panel, in stages. Each stage is its own pull request, and every stage builds on what Atrium already has instead of writing it again. Jira Service Management (a request portal) and Confluence are out of scope until someone asks.

Rules for all stages:

- **Data, not code.** Task types, statuses, transitions, priorities, resolutions, link types and fields are rows an administrator edits, not enums or a class per type. The built-in ones are seeded rows. This is what lets Tracker move onto the Atrium builder later.
- **Plugins from the first day.** Everything below the foundation registers through the same registry a plugin uses (conditions, validators and post-functions of transitions, automation triggers and actions, query functions, panels of the task card, dashboard gadgets, reports, project tabs), so the API is tested by the built-in features. A plugin is a Composer package that registers in its service provider and is switched on and off in the settings; see [tracker-plugins.md](tracker-plugins.md).
- **Business logic in services, not in models**, so a rule or a plugin can call it, and model fields that map one to one onto fields of an entity.
- **Automation rules are stored as a tree of data** (when → if → do), not as PHP.

| # | Stage | What it brings | What Atrium already has and is reused |
|---|---|---|---|
| 1 | **Foundation** | Projects (key, lead, category), numbering `PROJ-123` through all tasks, issue types with a hierarchy (epic, story, task, bug, sub-task and your own), priorities, resolutions, components, versions, labels, links between tasks (blocks, relates to, duplicates, clones), watchers, comments with @mentions, attachments, history of a task | Custom fields (`custom-fields`) for fields of tasks and projects; the change history and activity log (#115, #117); hooks and the event catalogue (#118, #120); import and export; roles and permissions |
| 2 | **Workflows** | Statuses and transitions per project and per issue type, workflow schemes, conditions, validators, post-functions, a screen on a transition | Hooks; the module registry; settings screens |
| 3 | **Boards** | Scrum: backlog, sprints, start and close a sprint. Kanban: WIP limits, swimlanes, quick filters | The kanban view of module lists (#116); the calendar for sprint dates |
| 4 | **Search** | A query language like JQL, saved filters, filter subscriptions by mail, bulk edit, move, clone | Filters above lists; import and export; the queue and mail |
| 5 | **Time tracking (like Tempo)** | Estimate and remaining, worklogs, timesheets and their approval, clients and billable hours, teams, resource planning, reports with Excel export | Import and export (xlsx); the calendar; roles |
| 6 | **Reports and dashboards** | Burndown, burnup, velocity, sprint report, cumulative flow, control chart, created vs resolved, dashboards of gadgets | Dashboard gadgets (platform PR 3, #123); the change history as the source of the numbers |
| 7 | **Timeline** | A Gantt chart with dependencies between tasks | The calendar; task links from stage 1 |
| 8 | **Permissions and notifications** | Permission schemes, project roles, security levels of tasks, notification schemes (mail and in the panel) | Roles and permissions of the panel; the mail module; the event catalogue. **Depends on platform PR 15** (permissions down to a row and a field): permission schemes are built on it, not on a tracker table of their own |
| 9 | **Automation** | Rules trigger → condition → action, stored as data | Event catalogue and `atrium.event.occurred`; the queue. **Depends on platform PR 14** (automation rules as one data tree shared by blocks, formulas and plugins): the tracker adds its triggers and actions to that registry, and does not keep a rule engine of its own |
| 10 | **Integrations** | REST API and tokens, webhooks, Git (commits, branches and pull requests by the task key), import from Jira | API tokens and `/api/v1`; import and export. **Webhooks depend on platform PR 9** (signed delivery from `atrium.event.occurred` through `integrations`), planned right after stage 1: the tracker only declares its events |

Stages that lean on the platform: the event catalogue (PR 2, #120) for the events of every stage; gadgets (PR 3, #123) and record panels (PR 4, #124) for stages 1, 3 and 6; screens of a module (PR 6) for time tracking and boards; formulas (PR 7) for the search language; webhooks (PR 9) for stage 10; automation (PR 14) for stage 9; permissions down to the field (PR 15) for stage 8. Time tracking, boards, the search language and the timeline are the tracker's own. Where the tracker needs something before the platform has it, it builds a thin layer and moves it onto the platform later.

The plugin API is written with stage 1 and described in [tracker-plugins.md](tracker-plugins.md). What is built so far is described below.

- [Install](#install)
- [Projects](#projects)
- [Tasks](#tasks)
- [Comments and history](#comments-and-history)
- [Types, statuses, priorities and resolutions](#types-statuses-priorities-and-resolutions)
- [My tasks and the filters](#my-tasks-and-the-filters)
- [Mail](#mail)
- [Settings](#settings)
- [Permissions](#permissions)
- [Hooks](#hooks)
- [Demo](#demo)

## Install

```bash
composer require alveby/atrium-tracker
php artisan atrium:module:install alveby/atrium-tracker
```

Three modules appear in the menu under **Tracker**: **Projects**, **Tasks** and **Task comments**. Letters need a working mail setup (`MAIL_*` in `.env`); without it the tracker works and sends none.

## Projects

A project has a name, a **key**, a description, a lead and members.

- The key is two to ten capital letters or digits, starting with a letter (`SHOP`, `WEB2`), and is unique. Tasks are numbered with it: `SHOP-1`, `SHOP-2` … A number is never used twice, even after a task is deleted.
- **Members** are the administrators who work in the project. When some are listed, a task can be given only to them and to the lead; with none listed, to any active administrator.
- An **archived** project takes no new tasks and is hidden from the choice of a project on a task form; its tasks stay.

## Tasks

| Field | Meaning |
|---|---|
| Project | Where the task lives. Cannot be an archived project |
| Type | One of the [types](#types-statuses-priorities-and-resolutions): Task, Bug, Story, Epic, Sub-task or your own |
| Title | Up to 255 characters |
| Description | Text, HTML or the visual editor |
| Status | One of the [statuses](#types-statuses-priorities-and-resolutions) |
| Priority | Lowest, Low, Medium, High, Highest, each with an arrow and a word, never a colour alone |
| Assignee | An active administrator |
| Parent task | A subtask of a task, or a task of an epic, in the same project |
| Labels | Free words, up to 20 |
| Due date | A task past it and not done shows as **overdue** |
| Estimate | Hours, up to two decimals |
| Attachments | Images, PDF, text, CSV, zip and Office files, up to 20 of 20 MB |

The rules the form holds to: a type marked **No parent** (the epic) has no parent, and a type marked **Needs a parent** (the sub-task) must have one; a parent is in the same project; a task cannot become a child of itself or of one of its own subtasks. The author is the administrator who made the task, filled in by itself. A new task gets the type, status and priority marked **Default**, and the moment it reaches a status of the **Done** category is kept as its completion time. **Resolution** says why it was closed (done, won’t do, duplicate …) and is left to the person.

## Comments and history

The **Comments and history** tab of a task shows, newest first, what was said and what happened: the comments, and a line for every change of status, assignee and priority with who made it and when. To comment, write in **New comment** and save the task. Comments cannot be added or edited anywhere else in the panel, so the thread stays what it was.

**Task comments** in the menu is the same comments as a read-only list across all tasks.

## Types, statuses, priorities and resolutions

These four are rows, not code: the lists **Task types**, **Statuses**, **Priorities** and **Resolutions** are in the module's menu, and an administrator adds, renames, orders and removes their rows. Plugins and later stages read them the same way.

| List | Built in | Besides the name |
|---|---|---|
| Statuses | To do, In progress, In review, Done | **Category** (to do, in progress, done): a status of the done category closes a task, whatever it is called |
| Task types | Task, Bug, Story, Epic, Sub-task | **No parent** (the epic) and **Needs a parent** (the sub-task) |
| Priorities | Lowest … Highest | A **symbol** shown before the name (⇣ ↓ → ↑ ⇡), so a priority is never told by colour alone |
| Resolutions | Done, Won’t do, Duplicate, Cannot reproduce | |

Rules of every list: the **code** (small letters, digits, `_`) is chosen once, because tasks keep it; the **name** may be left empty, and then the built-in name is shown in the language of each administrator, and a name you write is shown as written; the row marked **Default** is what a new task gets (without a mark, the first); a row cannot be deleted while tasks use it, change those tasks first.

## My tasks and the filters

The **My tasks** button on the list of tasks shows what is given to you and is not done (`/admin/tracker_tasks?mine=1`). Above the list: project, status, assignee, type, label, **Overdue** and **Mine**.

## Mail

A letter in the administrator's own language goes to the assignee when:

- a task is given to them (a new task made for them, or a task handed over later);
- the status of their task changes.

The administrator who makes the change is not mailed about their own change, nor is an inactive one. A letter goes out only after the change is saved for good. The text is plain, with a link to the task. Each kind can be switched off in Settings.

## Settings

The module's settings screen (`/admin/modules/tracker/settings`):

| Setting | Default |
|---|---|
| Switched-off plugins | none (shown once a [plugin](tracker-plugins.md) is installed) |
| Mail on assignment | on |
| Mail on a change of status | on |

## Permissions

Each of the three modules, and each of the four lists of terms, has the usual view, create, edit and delete in the role form. A role that may only view sees the lists and the tasks, and cannot change them; **Task comments** is read-only for everyone.

## Hooks

Two actions for other modules and the site:

```php
use Atrium\Admin\Facades\Hook;

Hook::on('atrium.tracker.task.status_changed', function (Task $task, string $from, string $to): void {
    // ...
});

Hook::on('atrium.tracker.task.assigned', function (Task $task, int $assigneeId): void {
    // ...
});
```

## Demo

`php artisan atrium:install --demo`, or `atrium:module:install alveby/atrium-tracker --demo`, creates two projects (an online shop and a company site) and ten tasks across all four statuses, with an epic, subtasks, late ones, labels and a comment each. It does nothing when the tracker already has a project, and sends no mail.
