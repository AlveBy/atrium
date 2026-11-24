<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Modules\Settings;
use Atrium\Bitrix24\Models\FormLink;
use Atrium\Forms\Models\Form;
use Laravel\Dusk\Browser;

/**
 * Connecting Bitrix24: the webhook is pasted once and then shown only as a
 * mask, the check button says why it cannot connect, and a form is linked
 * to Bitrix24 with the fields mapped line by line.
 */
final class Bitrix24Test extends AdminTestCase
{
    /** A host that never resolves: the check must fail without any traffic. */
    private const WEBHOOK = 'https://portal.invalid/rest/1/abc123def456/';

    public function test_the_webhook_is_saved_hidden_and_checked(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/modules/bitrix24/settings')
                ->waitFor('[data-settings-form]')
                ->assertSee('Incoming webhook')
                ->assertPresent('[data-action="run-check"]')
                ->type('#webhook', 'not a webhook')
                ->press('[data-action="save"]')
                ->waitForText('field format is invalid')
                ->type('#webhook', self::WEBHOOK)
                ->type('#widget_code', '<script>window.b24 = 1;</script>')
                ->press('[data-action="save"]')
                ->waitForText('Settings saved.');

            $browser->visit('/admin/modules/bitrix24/settings')
                ->waitFor('[data-settings-form]')
                ->assertAttribute('#webhook', 'placeholder', 'https://portal.invalid/rest/1/••••••••')
                ->assertInputValue('#webhook', '')
                ->assertDontSee('abc123def456')
                ->screenshot('bitrix24-settings')
                ->press('[data-action="run-check"]')
                ->waitForText('Bitrix24 does not answer')
                ->assertDontSee('abc123def456');
        });

        app(Settings::class)->flush();

        $this->assertSame(self::WEBHOOK, Setting::get('bitrix24.webhook'));
        $this->assertSame('<script>window.b24 = 1;</script>', Setting::get('bitrix24.widget_code'));
    }

    public function test_a_form_is_linked_as_a_deal_with_its_fields_mapped(): void
    {
        $admin = $this->admin();
        $form = Form::query()->create(['name' => 'Callback', 'code' => 'callback']);

        $this->browse(function (Browser $browser) use ($admin, $form): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/bitrix24_forms/create')
                ->waitFor('#mode')
                ->select('#form_id', (string) $form->id)
                ->select('#mode', FormLink::DEAL)
                ->type('#mapping', "phone = PHONE\nwhat is this")
                ->press('[data-action="save"]')
                ->waitForText('Line 2 is not')
                ->type('#mapping', "phone = PHONE\nmessage = COMMENTS")
                ->press('[data-action="save"]')
                ->waitForText('Callback')
                ->assertSee('Deal with a contact')
                ->screenshot('bitrix24-forms');

            $this->assertInPanel($browser, 'admin.bitrix24_forms.index');
        });

        $link = FormLink::query()->sole();
        $this->assertSame([$form->id, 'deal', "phone = PHONE\nmessage = COMMENTS"], [$link->form_id, $link->mode, $link->mapping]);
    }
}
