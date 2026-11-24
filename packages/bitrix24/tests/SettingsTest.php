<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24\Tests;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Modules\ActionFailed;
use Atrium\Admin\Modules\Settings;
use Atrium\Bitrix24\Bitrix24Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;

final class SettingsTest extends TestCase
{
    private const URL = '/admin/modules/bitrix24/settings';

    public function test_the_screen_explains_itself_and_offers_the_check_button(): void
    {
        $this->actingAs($this->admin(), 'admin')->get(self::URL)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('description', __('atrium-bitrix24::bitrix24.settings.description'))
            ->where('actions', [['name' => 'check', 'label' => 'Check connection']])
            ->where('fields.0.name', 'webhook')
            ->where('fields.1.name', 'send_forms')
            ->where('fields.2.name', 'widget_code'));
    }

    public function test_the_webhook_is_stored_encrypted_and_the_screen_shows_only_where_it_points(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->put(self::URL, ['webhook' => self::WEBHOOK])->assertSessionHasNoErrors();

        $stored = (string) DB::table(Settings::TABLE)->where('module', 'bitrix24')->value('values');
        $this->assertStringNotContainsString('abc123def456', $stored);
        $this->assertStringNotContainsString('portal.example.test', $stored);

        $response = $this->actingAs($admin, 'admin')->get(self::URL);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('values.webhook')
            ->where('fields.0.placeholder', 'https://portal.example.test/rest/1/••••••••'));
        $this->assertStringNotContainsString('abc123def456', (string) $response->getContent());
    }

    public function test_the_mask_keeps_the_portal_and_hides_the_code(): void
    {
        $settings = new Bitrix24Settings;

        $this->assertSame('https://portal.example.test/rest/1/••••••••', $settings->mask('webhook', self::WEBHOOK));
        $this->assertSame('http://crm.example.test:8080/rest/12/••••••••', $settings->mask('webhook', 'http://crm.example.test:8080/rest/12/Zz9Zz9'));
        $this->assertSame(['webhook'], $settings->secrets());
    }

    public function test_leaving_the_webhook_empty_keeps_it(): void
    {
        $admin = $this->admin();
        $this->connect();

        $this->actingAs($admin, 'admin')->put(self::URL, ['webhook' => '', 'widget_code' => '<b></b>'])->assertSessionHasNoErrors();

        $this->assertSame(self::WEBHOOK, Setting::get('bitrix24.webhook'));
        $this->assertSame('<b></b>', Setting::get('bitrix24.widget_code'));
    }

    /** @return array<string,array{0:string,1:mixed,2:bool}> */
    public static function values(): array
    {
        return [
            'webhook: bitrix24.com' => ['webhook', 'https://portal.bitrix24.com/rest/1/abc123/', true],
            'webhook: no slash at the end' => ['webhook', 'https://portal.bitrix24.com/rest/1/abc123', true],
            'webhook: another word' => ['webhook', 'https://portal.bitrix24.com/hello', false],
            'webhook: a method on the end' => ['webhook', 'https://portal.bitrix24.com/rest/1/abc123/profile.json', false],
            'webhook: no scheme' => ['webhook', 'portal.bitrix24.com/rest/1/abc123/', false],
            'webhook: too long' => ['webhook', 'https://portal.bitrix24.com/rest/1/'.str_repeat('a', 500).'/', false],
            'widget: code' => ['widget_code', '<script>1</script>', true],
            'widget: the limit' => ['widget_code', str_repeat('a', 20000), true],
            'widget: over the limit' => ['widget_code', str_repeat('a', 20001), false],
        ];
    }

    #[DataProvider('values')]
    public function test_each_field_keeps_what_is_right_and_refuses_the_rest(string $field, mixed $value, bool $valid): void
    {
        $response = $this->actingAs($this->admin(), 'admin')->put(self::URL, [$field => $value]);

        if ($valid) {
            $response->assertSessionHasNoErrors();
            $this->assertSame($value, Setting::get('bitrix24.'.$field));
        } else {
            $response->assertSessionHasErrors($field);
            $this->assertNull(Setting::get('bitrix24.'.$field));
        }
    }

    public function test_forms_are_sent_unless_switched_off(): void
    {
        $this->assertTrue(Setting::get('bitrix24.send_forms'));

        $this->actingAs($this->admin(), 'admin')->put(self::URL, ['send_forms' => false])->assertSessionHasNoErrors();

        $this->assertFalse(Setting::get('bitrix24.send_forms'));
    }

    public function test_the_check_button_names_the_portal_and_the_person_of_the_webhook(): void
    {
        $this->connect();
        Http::fake(['*' => Http::response(['result' => ['ID' => '1', 'NAME' => 'Ivan', 'LAST_NAME' => 'Petrov']])]);

        $this->actingAs($this->admin(), 'admin')->from(self::URL)->post(self::URL.'/actions/check')
            ->assertRedirect(self::URL)
            ->assertSessionHas('success', 'Connected: portal.example.test, user Ivan Petrov.')
            ->assertSessionMissing('error');
        Http::assertSent(fn ($request): bool => $request->url() === 'https://portal.example.test/rest/1/abc123def456/profile.json');
    }

    public function test_a_user_without_a_name_is_a_dash_and_one_name_is_enough(): void
    {
        $this->connect();
        $settings = new Bitrix24Settings;

        Http::fake(['*' => Http::sequence()
            ->push(['result' => ['ID' => '1']])
            ->push(['result' => ['NAME' => 'Ivan']])
            ->push(['result' => ['LAST_NAME' => 'Petrov']])
            ->push(['result' => true])]);

        $this->assertSame('Connected: portal.example.test, user —.', $settings->run('check'));
        $this->assertSame('Connected: portal.example.test, user Ivan.', $settings->run('check'));
        $this->assertSame('Connected: portal.example.test, user Petrov.', $settings->run('check'));
        $this->assertSame('Connected: portal.example.test, user —.', $settings->run('check'));
    }

    public function test_a_refusal_shows_as_an_error_and_without_a_webhook_it_says_to_paste_one(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->from(self::URL)->post(self::URL.'/actions/check')
            ->assertSessionHas('error', 'The Bitrix24 webhook is not set: paste it in Modules → Bitrix24.');

        $this->connect();
        Http::fake(['*' => Http::response(['error' => 'INVALID_CREDENTIALS', 'error_description' => 'Invalid request credentials'], 401)]);
        $this->actingAs($admin, 'admin')->from(self::URL)->post(self::URL.'/actions/check')
            ->assertSessionHas('error', 'Bitrix24 refused: INVALID_CREDENTIALS Invalid request credentials')
            ->assertSessionMissing('success');
    }

    public function test_a_failed_check_carries_the_reason_and_the_cause(): void
    {
        $this->connect();
        Http::fake(['*' => Http::response('', 500)]);

        try {
            (new Bitrix24Settings)->run('check');
            $this->fail('A refusal must throw.');
        } catch (ActionFailed $failed) {
            $this->assertSame('Bitrix24 refused: HTTP 500', $failed->getMessage());
            $this->assertNotNull($failed->getPrevious());
        }
    }

    public function test_only_a_superadmin_changes_them(): void
    {
        $this->actingAs($this->reader([]), 'admin')->get(self::URL)->assertForbidden();
        $this->actingAs($this->reader([]), 'admin')->put(self::URL, ['widget_code' => 'x'])->assertForbidden();
        $this->actingAs($this->reader([]), 'admin')->post(self::URL.'/actions/check')->assertForbidden();
        $this->assertNull(Setting::get('bitrix24.widget_code'));
    }
}
