<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24\Tests;

use Atrium\Bitrix24\Models\FormLink;
use Atrium\Bitrix24\Support\Payload;
use Atrium\Forms\Models\Form;

/**
 * The screen where each form is linked to Bitrix24.
 */
final class FormLinkTest extends TestCase
{
    public function test_a_link_is_made_and_listed_with_its_form_and_mode(): void
    {
        $form = $this->form('Callback');

        $this->actingAs($this->admin(), 'admin')->post('/admin/bitrix24_forms', [
            'form_id' => $form->id, 'mode' => 'deal', 'active' => true, 'mapping' => "phone = PHONE\n# note\nmessage = COMMENTS",
        ])->assertSessionHasNoErrors();

        $link = FormLink::query()->sole();
        $this->assertSame([$form->id, 'deal', true, "phone = PHONE\n# note\nmessage = COMMENTS"], [$link->form_id, $link->mode, $link->active, $link->mapping]);
        $this->assertSame('Callback', $link->form_name);
        $this->assertSame('Deal with a contact', $link->mode_label);

        $this->actingAs($this->admin(), 'admin')->get('/admin/bitrix24_forms')->assertOk()->assertSee('Callback')->assertSee('Deal with a contact');
    }

    public function test_a_lead_is_the_default_mode_and_says_so(): void
    {
        $link = FormLink::query()->create(['form_id' => $this->form()->id, 'mode' => 'lead', 'active' => true]);

        $this->assertSame('Lead', $link->mode_label);
    }

    public function test_a_mode_that_is_not_a_deal_reads_as_a_lead(): void
    {
        $this->assertSame('Lead', (new FormLink(['mode' => 'anything']))->mode_label);
        $this->assertSame('Deal with a contact', (new FormLink(['mode' => 'deal']))->mode_label);
    }

    public function test_a_link_whose_form_is_gone_has_no_name(): void
    {
        $this->assertSame('', (new FormLink(['form_id' => 424242]))->form_name);
    }

    public function test_the_form_of_another_site_is_still_named(): void
    {
        $form = $this->form('Elsewhere');
        Form::withoutGlobalScopes()->whereKey($form->id)->toBase()->update(['site_id' => 4242]);

        $this->assertSame('Elsewhere', FormLink::query()->create(['form_id' => $form->id, 'mode' => 'lead', 'active' => true])->form_name);
    }

    public function test_a_form_is_linked_once(): void
    {
        $form = $this->form();
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->post('/admin/bitrix24_forms', ['form_id' => $form->id, 'mode' => 'lead', 'active' => true])->assertSessionHasNoErrors();

        $this->actingAs($admin, 'admin')->post('/admin/bitrix24_forms', ['form_id' => $form->id, 'mode' => 'deal', 'active' => true])->assertSessionHasErrors('form_id');

        $this->assertSame(1, FormLink::query()->count());
    }

    public function test_the_form_and_the_mode_are_required_and_the_mode_is_one_of_two(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post('/admin/bitrix24_forms', [])->assertSessionHasErrors(['form_id', 'mode']);
        $this->actingAs($admin, 'admin')->post('/admin/bitrix24_forms', ['form_id' => $this->form()->id, 'mode' => 'order'])->assertSessionHasErrors('mode');
        $this->assertSame(0, FormLink::query()->count());
    }

    public function test_a_bad_line_in_the_fields_is_named_by_its_number(): void
    {
        $admin = $this->admin();
        $form = $this->form();

        $this->actingAs($admin, 'admin')->post('/admin/bitrix24_forms', ['form_id' => $form->id, 'mode' => 'lead', 'mapping' => "a = NAME\n\nwhat is this"])
            ->assertSessionHasErrors(['mapping' => 'Line 3 is not "form field = Bitrix24 field".']);

        $this->actingAs($admin, 'admin')->post('/admin/bitrix24_forms', ['form_id' => $form->id, 'mode' => 'lead', 'mapping' => str_repeat('a', 5001)])
            ->assertSessionHasErrors('mapping');
        $this->assertSame(0, FormLink::query()->count());
    }

    public function test_empty_fields_are_fine(): void
    {
        $this->actingAs($this->admin(), 'admin')->post('/admin/bitrix24_forms', ['form_id' => $this->form()->id, 'mode' => 'lead', 'mapping' => ''])->assertSessionHasNoErrors();

        $this->assertSame(1, FormLink::query()->count());
    }

    public function test_a_link_is_changed_and_taken_off(): void
    {
        $admin = $this->admin();
        $link = FormLink::query()->create(['form_id' => $this->form()->id, 'mode' => 'lead', 'active' => true]);

        $this->actingAs($admin, 'admin')->put('/admin/bitrix24_forms/'.$link->id, ['form_id' => $link->form_id, 'mode' => 'deal', 'active' => false])->assertSessionHasNoErrors();
        $link->refresh();
        $this->assertSame(['deal', false], [$link->mode, $link->active]);

        $this->actingAs($admin, 'admin')->delete('/admin/bitrix24_forms/'.$link->id);
        $this->assertSame(0, FormLink::query()->count());
    }

    public function test_the_screen_is_for_those_who_may_see_it(): void
    {
        $this->actingAs($this->reader([]), 'admin')->get('/admin/bitrix24_forms')->assertForbidden();
        $this->assertSame('Bitrix24: forms', FormLink::getAdminLabel());
        $this->assertSame('bitrix24_forms', FormLink::getAdminRoutePrefix());
    }

    public function test_the_validator_of_the_fields_is_the_one_the_sender_reads_by(): void
    {
        $this->assertNull(Payload::badLine("a = NAME\nb = PHONE"));
    }
}
