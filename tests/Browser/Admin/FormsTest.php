<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Forms\Models\Form;
use Atrium\Forms\Models\FormField;
use Atrium\Forms\Models\FormSubmission;
use Laravel\Dusk\Browser;

/**
 * The forms list shows how many submissions each form has, and the number
 * follows a submission deleted in the admin. The fields are built on the
 * form's own page, two to a row.
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

    public function test_an_editor_builds_a_form_of_two_columns_and_saves_it(): void
    {
        $admin = $this->admin();

        // Where each field of the preview starts: [left, top].
        $place = fn (Browser $browser, string $name): array => $browser->script(
            "const box = document.querySelector('[dusk=\"builder-preview-{$name}\"]').getBoundingClientRect();
             return [Math.round(box.left), Math.round(box.top)];"
        )[0];

        $this->browse(function (Browser $browser) use ($admin, $place): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/forms/create')
                ->waitFor('#name')
                ->type('#name', 'Call me back')
                ->type('#code', 'callback')
                ->assertVisible('@builder-empty')
                ->click('@builder-add-text')
                ->waitFor('@builder-settings-0')
                ->type('@builder-label', 'Your name')
                ->assertInputValue('@builder-name', 'your_name')
                ->type('@builder-name', 'name')
                ->click('@builder-required')
                ->click('@builder-width-6')
                ->click('@builder-add-tel')
                ->waitFor('@builder-settings-1')
                ->type('@builder-label', 'Phone')
                ->assertInputValue('@builder-name', 'phone')
                ->type('@builder-placeholder', '+375 29 000-00-00')
                ->click('@builder-width-6')
                ->click('@builder-add-textarea')
                ->waitFor('@builder-settings-2')
                ->type('@builder-label', 'Message')
                ->waitFor('@builder-preview-message');

            // Name and phone share the first row, the message takes the next.
            [$nameLeft, $nameTop] = $place($browser, 'name');
            [$phoneLeft, $phoneTop] = $place($browser, 'phone');
            [$messageLeft, $messageTop] = $place($browser, 'message');
            $this->assertSame($nameTop, $phoneTop);
            $this->assertGreaterThan($nameLeft, $phoneLeft);
            $this->assertSame($nameLeft, $messageLeft);
            $this->assertGreaterThan($nameTop, $messageTop);
            $browser->screenshot('forms-builder-computer');

            // On a phone every field takes the whole row.
            $browser->click('@builder-device-phone')
                ->waitFor('[dusk="builder-preview"][data-device="phone"]');
            [$nameLeft, $nameTop] = $place($browser, 'name');
            [$phoneLeft, $phoneTop] = $place($browser, 'phone');
            $this->assertSame($nameLeft, $phoneLeft);
            $this->assertGreaterThan($nameTop, $phoneTop);
            $browser->screenshot('forms-builder-phone');

            $browser->press('[data-action="save"]')
                ->waitForLocation('/admin/forms')
                ->waitForText('Call me back');
        });

        $form = Form::query()->where('code', 'callback')->sole();
        $fields = $form->fields()->get();
        $this->assertSame(['name', 'phone', 'message'], $fields->pluck('name')->all());
        $this->assertSame(['text', 'tel', 'textarea'], $fields->pluck('type')->all());
        $this->assertSame([6, 6, 12], $fields->pluck('width')->all());
        $this->assertSame([true, false, false], $fields->pluck('required')->all());
        $this->assertSame('Your name', $fields[0]->label);
        $this->assertSame('+375 29 000-00-00', $fields[1]->placeholder);
        $this->assertSame(3, FormField::query()->count());
    }
}
