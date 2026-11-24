# Tracker

[English](tracker.md) · [Русский](tracker.ru.md)

`alveby/atrium-tracker` keeps the work of a team in the admin panel: projects, tasks in them, comments, a history of what changed and a letter to the person a task is given to. It is the small kind of Jira. It is used by administrators and only by them: what each may see and do follows their roles and permissions, and nothing of it reaches the site.

- [Install](#install)
- [Projects](#projects)
- [Tasks](#tasks)
- [Comments and history](#comments-and-history)
- [Statuses](#statuses)
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
| Type | Task, Story, Bug or Epic |
| Title | Up to 255 characters |
| Description | Text, HTML or the visual editor |
| Status | One of the [statuses](#statuses) |
| Priority | Lowest, Low, Medium, High, Highest, each with an arrow and a word, never a colour alone |
| Assignee | An active administrator |
| Parent task | A subtask of a task, or a task of an epic, in the same project |
| Labels | Free words, up to 20 |
| Due date | A task past it and not done shows as **overdue** |
| Estimate | Hours, up to two decimals |
| Attachments | Images, PDF, text, CSV, zip and Office files, up to 20 of 20 MB |

The rules the form holds to: an epic has no parent; a parent is in the same project; a task cannot become a child of itself or of one of its own subtasks. The author is the administrator who made the task, filled in by itself. The first status is given to a new task, and the moment a task reaches a status the settings call done is kept as its completion time.

## Comments and history

The **Comments and history** tab of a task shows, newest first, what was said and what happened: the comments, and a line for every change of status, assignee and priority with who made it and when. To comment, write in **New comment** and save the task. Comments cannot be added or edited anywhere else in the panel, so the thread stays what it was.

**Task comments** in the menu is the same comments as a read-only list across all tasks.

## Statuses

The built-in ones are **To do**, **In progress**, **In review** and **Done**; the last one is the status that closes a task. To use your own, list them in [Settings](#settings), in order: the first is given to a new task, the last closes it. Renaming a status that tasks already have leaves those tasks with the old word until they are saved again, so rename it task by task or change the tasks first.

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
| Statuses (up to 15) | empty: the built-in four |
| Mail on assignment | on |
| Mail on a change of status | on |

## Permissions

Each of the three modules has the usual view, create, edit and delete in the role form. A role that may only view sees the lists and the tasks, and cannot change them; **Task comments** is read-only for everyone.

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
