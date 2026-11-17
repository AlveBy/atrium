<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Modules\Settings;
use Atrium\TwoFactor\Models\Code;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Support\Totp;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;

/**
 * The screens of alveby/atrium-2fa in the panel: the security page, the
 * second step, the passwordless e-mail code, the log and the reset.
 */
final class TwoFactorTest extends AdminTestCase
{
    public function test_an_administrator_sets_up_an_authenticator_and_passes_the_second_step_with_it(): void
    {
        $admin = $this->admin();
        $secret = '';

        $this->browse(function (Browser $browser) use ($admin, &$secret): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/auth/security')
                ->waitFor('@a2f-security')
                ->assertPresent('@a2f-no-factors')
                ->press('@a2f-totp-start')
                ->waitFor('@a2f-qr');

            $secret = str_replace(' ', '', $browser->text('@a2f-secret'));
            $totp = app(Totp::class);

            $browser->type('@a2f-totp-code', $totp->at($secret, $totp->step()))
                ->press('@a2f-totp-confirm')
                ->waitFor('@a2f-recovery-codes')
                ->assertPresent('@a2f-factor-list');

            // Signed out, the password alone no longer lets them in.
            $browser->driver->manage()->deleteAllCookies();

            $browser->visit('/admin/login')
                ->waitFor('#email')
                ->type('#email', 'ada@example.com')
                ->type('#password', 'correct-horse')
                ->press('button[type="submit"]')
                ->waitFor('@a2f-challenge')
                ->assertPathIs('/admin/auth/challenge')
                ->type('@a2f-code', '000000')
                ->press('@a2f-submit')
                ->waitFor('@a2f-error-code')
                ->assertPathIs('/admin/auth/challenge')
                // The next 30-second step: the one used to set it up is spent.
                ->type('@a2f-code', $totp->at($secret, $totp->step() + 1))
                ->press('@a2f-submit')
                ->waitForLocation('/admin')
                ->assertSee('Ada Admin');
        });

        $factor = Factor::query()->where('guard', 'admin')->where('user_id', $admin->id)->sole();
        $this->assertSame('totp', $factor->type);
        $this->assertNotNull($factor->confirmed_at);
        $this->assertSame($secret, $factor->secret);
        $this->assertStringNotContainsString($secret, (string) $factor->getRawOriginal('secret'));
        $this->assertTrue(Event::query()->where('user_id', $admin->id)->where('event', 'failed')->exists());
        $this->assertTrue(Event::query()->where('user_id', $admin->id)->where('event', 'signed_in')->exists());
    }

    public function test_an_administrator_signs_in_with_a_code_by_email_when_it_is_switched_on(): void
    {
        $this->admin();

        $this->browse(function (Browser $browser): void {
            // Off by default: the login screen has no such button.
            $browser->visit('/admin/login')
                ->waitFor('#email')
                ->assertMissing('@login-method-code');
        });

        app(Settings::class)->save('two_factor', ['admin_magic_code' => '1']);

        $this->browse(function (Browser $browser): void {
            $browser->visit('/admin/login')
                ->waitFor('@login-method-code')
                ->click('@login-method-code')
                ->waitFor('@a2f-magic')
                ->type('@a2f-email', 'ada@example.com')
                ->press('@a2f-submit')
                ->waitFor('@a2f-magic-code');

            // The mail is not read here; the code it carried is replaced by a known one.
            Code::query()->latest('id')->firstOrFail()->forceFill(['code' => Hash::make('482913')])->save();

            $browser->type('@a2f-code', '482913')
                ->press('@a2f-submit')
                ->waitForLocation('/admin')
                ->assertSee('Ada Admin');
        });

        $this->assertTrue(Event::query()->where('event', 'signed_in')->where('method', 'code')->exists());
    }

    public function test_an_administrator_resets_the_factors_of_a_person_and_sees_it_in_the_log(): void
    {
        $admin = $this->admin();
        $lost = AdminUser::query()->create(['name' => 'Lin Lost', 'email' => 'lin@example.com', 'password' => 'another-horse', 'is_active' => true]);
        Factor::query()->create(['guard' => 'admin', 'user_id' => $lost->id, 'type' => 'email', 'name' => 'Mail', 'confirmed_at' => now()]);

        $this->browse(function (Browser $browser) use ($admin, $lost): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/auth/people')
                ->waitFor('@a2f-people')
                ->assertSeeIn('@a2f-person-'.$lost->id, 'lin@example.com')
                ->press('@a2f-reset-'.$lost->id)
                ->acceptDialog()
                ->waitFor('@a2f-status')
                ->visit('/admin/auth/log')
                ->waitFor('@a2f-events')
                ->assertSeeIn('@a2f-events', 'lin@example.com');
        });

        $this->assertFalse(Factor::query()->where('user_id', $lost->id)->exists());
        $reset = Event::query()->where('event', 'reset')->sole();
        $this->assertSame($lost->id, (int) $reset->user_id);
        $this->assertSame($admin->id, (int) $reset->actor_id);
    }
}
