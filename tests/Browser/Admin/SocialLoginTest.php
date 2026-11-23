<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\SocialLogin\Models\SocialAccount;
use Atrium\SocialLogin\Models\SocialProvider;
use Laravel\Dusk\Browser;

/**
 * The screens of alveby/atrium-social-login in the panel: a provider switched
 * on, its button on the login screen, and a linked account unlinked.
 */
final class SocialLoginTest extends AdminTestCase
{
    public function test_an_administrator_switches_a_provider_on_and_its_button_appears_on_the_login_screen(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->visit('/admin/login')
                ->waitFor('#email')
                ->assertMissing('@login-method-social-github');

            $browser->loginAs($admin, 'admin')
                ->visit('/admin/auth/social/providers')
                ->waitFor('@asl-providers');
            $this->assertInPanel($browser, 'admin.social.providers');

            $browser->click('@asl-edit-github')
                ->waitFor('@asl-provider-form');
            $this->assertInPanel($browser, 'admin.social.providers');

            $browser
                ->assertAttributeContains('@asl-redirect-admin', 'value', '/admin/auth/social/callback/github')
                ->type('@asl-client-id', 'Iv1.test-client')
                ->type('@asl-client-secret', 'test-secret-value')
                ->check('@asl-guard-admin')
                ->press('@asl-save')
                ->waitFor('@asl-status')
                ->assertSeeIn('@asl-provider-github', '✓');

            $browser->driver->manage()->deleteAllCookies();

            $browser->visit('/admin/login')
                ->waitFor('@login-method-social-github')
                ->assertSeeIn('@login-method-social-github', 'GitHub')
                ->assertAttributeContains('@login-method-social-github', 'href', '/admin/auth/social/redirect/github');
        });

        $github = SocialProvider::query()->where('key', 'github')->sole();
        $this->assertSame('Iv1.test-client', $github->client_id);
        $this->assertSame('test-secret-value', $github->client_secret);
        $this->assertStringNotContainsString('test-secret-value', (string) $github->getRawOriginal('client_secret'));
        $this->assertContains('admin', $github->guards);
    }

    public function test_an_administrator_unlinks_a_social_account_from_the_profile(): void
    {
        $admin = $this->admin();
        SocialProvider::query()->create(['key' => 'github', 'guards' => ['admin'], 'client_id' => 'Iv1.test-client', 'client_secret' => 'test-secret-value']);
        SocialAccount::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'provider' => 'github', 'provider_user_id' => '583231', 'email' => 'ada@example.com', 'name' => 'Ada']);

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/auth/social/accounts')
                ->waitFor('@asl-accounts');
            $this->assertInPanel($browser, 'admin.social.accounts');

            $browser->assertSeeIn('@asl-linked-github', 'ada@example.com')
                ->press('@asl-unlink-github')
                ->waitFor('@asl-status')
                ->assertMissing('@asl-linked-github')
                ->assertPresent('@asl-link-github');
        });

        $this->assertFalse(SocialAccount::query()->where('user_id', $admin->id)->exists());
    }

    public function test_a_provider_form_the_server_refuses_shows_the_reason_and_keeps_what_was_typed(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/auth/social/providers/entra')
                ->waitFor('@asl-provider-form');
            $this->assertInPanel($browser, 'admin.social.providers');

            $browser->type('@asl-client-id', 'entra-client')
                ->type('@asl-setting-tenant', 'not a tenant!')
                ->press('@asl-save')
                ->waitFor('@asl-setting-tenant-error')
                ->assertPathIs('/admin/auth/social/providers/entra')
                ->assertInputValue('@asl-client-id', 'entra-client')
                ->assertInputValue('@asl-setting-tenant', 'not a tenant!');
        });

        $this->assertFalse(SocialProvider::query()->where('key', 'entra')->exists());
    }
}
