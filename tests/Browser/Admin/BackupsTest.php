<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Backup\Backups;
use Laravel\Dusk\Browser;

/**
 * The Backups screen: back up now, read what a restore would change, delete.
 * The restore itself is covered by the module's PHPUnit tests.
 */
final class BackupsTest extends AdminTestCase
{
    public function test_back_up_preview_a_restore_and_delete(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->clickLink('Backups')
                ->waitFor('@backup-now')
                ->click('@backup-now')
                // The run happens after the response; the page reloads when it ends.
                ->waitFor('@backup', 60)
                ->assertMissing('@backup-running')
                ->assertSeeIn('@backup-log', 'Ada Admin');

            $file = (string) $browser->attribute('@backup', 'data-backup');
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}\.zip$/', $file);
            $this->assertCount(1, app(Backups::class)->all());

            $browser->click('@backup @restore')
                ->waitFor('@restore-dialog')
                ->click('@preview')
                ->waitFor('@plan-databases', 30)
                ->assertSeeIn('@plan-databases', 'sqlite')
                ->assertPresent('@plan-folders')
                ->assertDisabled('@start-restore')
                ->type('@restore-confirm', $file.'x')
                ->assertDisabled('@start-restore')
                ->clear('@restore-confirm')
                ->type('@restore-confirm', $file)
                ->assertEnabled('@start-restore')
                ->click('@cancel-restore')
                ->waitUntilMissing('@restore-dialog')
                ->click('@backup @delete')
                ->waitFor('@confirm-delete')
                ->click('@confirm-delete')
                ->waitUntilMissing('@backup', 30);
        });

        $this->assertSame([], app(Backups::class)->all());
    }
}
