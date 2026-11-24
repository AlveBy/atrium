<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Tracker\Models\Project;
use Atrium\Tracker\Models\Task;
use Atrium\Tracker\Tests\TestCase;
use Inertia\Testing\AssertableInertia;

/**
 * Projects have a short key, a lead and people; an archived one takes no
 * new tasks.
 */
final class ProjectTest extends TestCase
{
    /**
     * @param  array<string,mixed>  $extra
     * @return array<string,mixed>
     */
    private function form(array $extra = []): array
    {
        return [...['name' => 'Website', 'key' => 'WEB'], ...$extra];
    }

    public function test_a_project_is_made_with_its_people(): void
    {
        $lead = $this->admin(name: 'Lead');
        $member = $this->admin(name: 'Member');
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/tracker_projects/create')->assertOk();
        $this->post('/admin/tracker_projects', $this->form(['lead_id' => $lead->id, 'members' => [$member->id], 'description' => 'The site']))
            ->assertSessionDoesntHaveErrors()->assertRedirect(route('admin.tracker_projects.index'));

        $project = Project::query()->sole();
        $this->assertSame(['WEB', 'Website', $lead->id, 0], [$project->key, $project->name, $project->lead_id, $project->task_counter]);
        $this->assertSame([$member->id], $project->members()->pluck('admin_users.id')->all());
        $this->assertFalse($project->is_archived);
    }

    public function test_the_key_is_two_to_ten_capitals_or_digits_and_unique(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->project(['key' => 'WEB']);

        foreach (['web', 'W', 'WEB SITE', '1WEB', 'WEB-1', 'ABCDEFGHIJK', 'ВЕБ'] as $key) {
            $this->post('/admin/tracker_projects', $this->form(['key' => $key, 'name' => 'Other']))->assertSessionHasErrors(['key']);
        }

        $this->post('/admin/tracker_projects', $this->form(['name' => 'Same key']))->assertSessionHasErrors(['key']);
        $this->assertSame(1, Project::query()->count());

        foreach (['WE', 'ABCDEFGHIJ', 'A1'] as $key) {
            $this->post('/admin/tracker_projects', $this->form(['key' => $key, 'name' => "P $key"]))->assertSessionDoesntHaveErrors();
        }

        $this->assertSame(4, Project::query()->count());
    }

    public function test_a_project_needs_a_name(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/tracker_projects', $this->form(['name' => '']))
            ->assertSessionHasErrors(['name']);
    }

    public function test_a_project_can_keep_its_own_key_when_edited(): void
    {
        $project = $this->project(['key' => 'WEB']);

        $this->actingAs($this->admin(), 'admin')
            ->put("/admin/tracker_projects/{$project->id}", $this->form(['name' => 'Renamed', 'is_archived' => true]))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(['Renamed', true], [$project->fresh()->name, $project->fresh()->is_archived]);
    }

    public function test_the_project_form_offers_only_projects_that_take_tasks_in_the_task_form(): void
    {
        $this->project();
        $this->project(['name' => 'Old', 'key' => 'OLD', 'is_archived' => true]);

        $this->actingAs($this->admin(), 'admin')->get('/admin/tracker_tasks/create')
            ->assertInertia(function (AssertableInertia $page): void {
                $project = collect($page->toArray()['props']['fields'])->firstWhere('name', 'project_id');

                $this->assertSame(['Atrium'], array_column($project['options'], 'label'));
            });
    }

    public function test_the_list_shows_key_name_lead_and_archive_flag(): void
    {
        $lead = $this->admin(name: 'Lead');
        $this->project(['lead_id' => $lead->id]);

        $this->actingAs($this->admin(), 'admin')->get('/admin/tracker_projects')
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page): void {
                $props = $page->toArray()['props'];

                $this->assertSame(['key', 'name', 'lead.name', 'is_archived'], array_column($props['columns'], 'name'));
                $this->assertSame('ATR', $props['items']['data'][0]['key']);
                $this->assertSame(['is_archived'], array_column($props['filterControls'], 'name'));
            });
    }

    public function test_a_project_is_titled_by_its_key_and_name(): void
    {
        $this->assertSame('ATR Atrium', $this->project()->getAdminTitle());
    }

    public function test_a_project_with_nobody_listed_is_open(): void
    {
        $project = $this->project();
        $this->assertTrue($project->isOpen());

        $project->members()->attach($this->admin()->id);
        $this->assertFalse($project->isOpen());

        $lead = $this->project(['name' => 'Led', 'key' => 'LED', 'lead_id' => $this->admin()->id]);
        $this->assertFalse($lead->isOpen());
    }

    public function test_a_person_of_the_project_is_its_lead_or_a_member(): void
    {
        $lead = $this->admin(name: 'Lead');
        $member = $this->admin(name: 'Member');
        $stranger = $this->admin(name: 'Stranger');
        $project = $this->project(['lead_id' => $lead->id]);
        $project->members()->attach($member->id);

        $this->assertTrue($project->hasPerson($lead->id));
        $this->assertTrue($project->hasPerson($member->id));
        $this->assertFalse($project->hasPerson($stranger->id));
    }

    public function test_a_project_is_found_by_its_id_or_its_key_on_import(): void
    {
        $this->assertSame(['id', 'key'], Project::getImportKeys());
        $this->assertSame(['id'], Task::getImportKeys());
    }
}
