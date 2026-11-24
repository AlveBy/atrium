<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Tests;

use Atrium\Admin\Facades\Setting;
use PHPUnit\Framework\Attributes\DataProvider;

final class SettingsTest extends TestCase
{
    public function test_the_settings_screen_explains_itself(): void
    {
        $this->actingAs($this->admin(), 'admin')->get('/admin/modules/integrations/settings')
            ->assertOk()
            ->assertSee(json_encode(__('atrium-integrations::integrations.settings.description'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), false);
    }

    /** @return array<string,array{0:string,1:mixed,2:bool}> */
    public static function values(): array
    {
        return [
            'days: one' => ['retention_days', 1, true],
            'days: ten years' => ['retention_days', 3650, true],
            'days: zero' => ['retention_days', 0, false],
            'days: too many' => ['retention_days', 3651, false],
            'days: not a number' => ['retention_days', 'week', false],
            'days: a fraction' => ['retention_days', 1.5, false],
            'emails: two' => ['alert_emails', "ops@example.com\nlead@example.org", true],
            'emails: too long' => ['alert_emails', str_repeat('a', 2001), false],
        ];
    }

    #[DataProvider('values')]
    public function test_each_field_keeps_what_is_right_and_refuses_the_rest(string $field, mixed $value, bool $valid): void
    {
        $default = $field === 'retention_days' ? 90 : null;
        $response = $this->actingAs($this->admin(), 'admin')->put('/admin/modules/integrations/settings', [$field => $value]);

        if ($valid) {
            $response->assertSessionHasNoErrors();
            $this->assertEquals($value, Setting::get('integrations.'.$field));
        } else {
            $response->assertSessionHasErrors($field);
            $this->assertSame($default, Setting::get('integrations.'.$field));
        }
    }

    public function test_the_retention_default_is_the_configured_one(): void
    {
        config(['atrium-integrations.retention_days' => 30]);

        $this->assertSame(30, Setting::get('integrations.retention_days'));
    }

    public function test_only_a_superadmin_changes_them(): void
    {
        $this->actingAs($this->admin(false), 'admin')->get('/admin/modules/integrations/settings')->assertForbidden();
        $this->actingAs($this->admin(false), 'admin')->put('/admin/modules/integrations/settings', ['retention_days' => 5])->assertForbidden();
        $this->assertSame(90, Setting::get('integrations.retention_days'));
    }
}
