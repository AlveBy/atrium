<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Forms\Models\Form;
use Atrium\Forms\Models\FormSubmission;
use Laravel\Dusk\Browser;

/**
 * The forms list shows how many submissions each form has, and the number
 * follows a submission deleted in the admin.
 */
final class FormsTest extends AdminTestCase
{
    public function test_the_forms_list_counts_submissions_and_follows_a_deletion(): void
    {
        $admin = $this->admin();
        $form = Form::query()->create(['name' => 'Contact us', 'code' => 'contact']);
        Form::query()->create(['name' => 'Quiet form', 'code' => 'quiet']);

        foreach (['Ann', 'Bob'] as $name) {
            (new FormSubmission)->forceFill([
                'form_id' => $form->id,
                'form_name' => $form->name,
                'data' => [['name' => 'name', 'type' => 'text', 'label' => 'Your name', 'value' => $name]],
            ])->save();
        }

        $count = fn (Browser $browser, string $row): string => (string) $browser->script(
            "const i = [...document.querySelectorAll('thead th')].findIndex(th => th.textContent.trim().toLowerCase().startsWith('submissions'));
             const tr = [...document.querySelectorAll('tbody tr')].find(tr => tr.textContent.includes('{$row}'));
             return i < 0 || ! tr ? null : tr.children[i].textContent.trim();"
        )[0];

        $this->browse(function (Browser $browser) use ($admin, $count): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/forms')
                ->waitForText('Contact us');

            $this->assertSame('2', $count($browser, 'Contact us'));
            $this->assertSame('0', $count($browser, 'Quiet form'));
            $browser->screenshot('forms-list');

            $browser->visit('/admin/form_submissions')
                ->waitForText('Ann')
                ->click('[data-action="delete"]')
                ->waitFor('[data-action="delete-confirm"]')
                ->click('[data-action="delete-confirm"]')
                ->waitUntil('document.querySelectorAll("tbody tr").length === 1');

            $browser->visit('/admin/forms')->waitForText('Contact us');
            $this->assertSame('1', $count($browser, 'Contact us'));
            $this->assertSame('0', $count($browser, 'Quiet form'));
        });

        $this->assertSame(1, $form->fresh()?->submissions_count);
    }
}
