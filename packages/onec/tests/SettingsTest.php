<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC\Tests;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Modules\Settings;
use Atrium\OneC\OneCSettings;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

final class SettingsTest extends TestCase
{
    private const URL = '/admin/modules/1c/settings';

    public function test_the_screen_gives_both_addresses_and_asks_for_a_login_and_a_password(): void
    {
        $this->actingAs($this->admin(), 'admin')->get(self::URL)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('description', __('atrium-1c::onec.settings.description', ['url' => 'http://localhost/atrium-1c/exchange', 'bitrix' => 'http://localhost/bitrix/admin/1c_exchange.php']))
            ->where('fields.0.name', 'login')
            ->where('fields.1.name', 'password'));
    }

    public function test_the_description_names_the_addresses(): void
    {
        $text = (new OneCSettings)->description();

        $this->assertStringContainsString('http://localhost/atrium-1c/exchange', $text);
        $this->assertStringContainsString('http://localhost/bitrix/admin/1c_exchange.php', $text);
    }

    public function test_the_password_is_stored_encrypted_and_never_comes_back_to_the_screen(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->put(self::URL, ['login' => 'shop', 'password' => 'a-secret-phrase'])->assertSessionHasNoErrors();

        $stored = (string) DB::table(Settings::TABLE)->where('module', '1c')->value('values');
        $this->assertStringNotContainsString('a-secret-phrase', $stored);
        $this->assertStringContainsString('shop', $stored);

        $response = $this->actingAs($admin, 'admin')->get(self::URL);
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('values.password')
            ->where('values.login', 'shop')
            ->where('fields.1.placeholder', '••••••••'));
        $this->assertStringNotContainsString('a-secret-phrase', (string) $response->getContent());
        $this->assertSame('a-secret-phrase', Setting::get('1c.password'));
    }

    public function test_leaving_the_password_empty_keeps_it(): void
    {
        $this->actingAs($this->admin(), 'admin')->put(self::URL, ['login' => 'new-login', 'password' => ''])->assertSessionHasNoErrors();

        $this->assertSame('new-login', Setting::get('1c.login'));
        $this->assertSame(self::PASSWORD, Setting::get('1c.password'));
    }

    public function test_only_the_password_is_a_secret(): void
    {
        $this->assertSame(['password'], (new OneCSettings)->secrets());
    }

    public function test_a_login_or_password_that_is_too_long_is_refused(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->put(self::URL, ['login' => str_repeat('a', 101)])->assertSessionHasErrors('login');
        $this->actingAs($admin, 'admin')->put(self::URL, ['login' => str_repeat('a', 100), 'password' => str_repeat('p', 201)])->assertSessionHasErrors('password');
        $this->actingAs($admin, 'admin')->put(self::URL, ['login' => str_repeat('a', 100), 'password' => str_repeat('p', 200)])->assertSessionHasNoErrors();
    }
}
