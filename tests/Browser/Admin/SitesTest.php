<?php

declare(strict_types=1);

namespace Tests\Browser\Admin;

use App\Models\Article;
use Atrium\Admin\Models\Language;
use Atrium\Admin\Models\Site;
use Laravel\Dusk\Browser;

/**
 * Stage 4a acceptance: a second site with its own domain and language, set
 * up in the admin; a record made on it is seen on its domain only.
 *
 * The default site answers on 127.0.0.1 (APP_URL); the second one gets
 * "localhost" — the same server, another domain.
 */
final class SitesTest extends AdminTestCase
{
    public function test_a_second_site_with_its_own_domain_and_language_shows_only_its_own_records(): void
    {
        $admin = $this->admin();
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch', 'sort' => 200]);

        $this->browse(function (Browser $browser) use ($admin): void {
            // One site: no switcher in the header.
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/sites')
                ->waitFor('[data-action="create"]')
                ->assertMissing('[data-site-switcher]')
                ->click('[data-action="create"]')
                ->waitForLocation('/admin/sites/create')
                ->waitFor('#name')
                ->type('#name', 'Deutschland')
                ->type('#code', 'de')
                ->click('[data-field="domains"] [data-action="repeatable-add"]')
                ->waitFor('[data-field="domains"] [data-repeatable-item="0"] input')
                ->type('[data-field="domains"] [data-repeatable-item="0"] input', 'localhost')
                ->click('[data-multiselect="languages"]')
                ->waitFor('[data-multiselect="languages"] [data-option-value="de"]')
                ->click('[data-multiselect="languages"] [data-option-value="de"]')
                ->waitFor('[data-multiselect="languages"] [data-selected-value="de"]')
                ->keys('#languages', '{escape}')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/sites')
                ->waitForText('Deutschland');

            $site = Site::query()->where('code', 'de')->sole();
            $this->assertSame(['localhost'], $site->domains);
            $this->assertSame('de', $site->default_language);

            // Switch to the German site and write an article there. It has one
            // language, so the form has no language switch.
            $browser->visit('/admin/articles')
                ->waitFor('[data-site-switcher]')
                ->select('[data-site-switcher]', (string) $site->id)
                ->waitUsing(5, 100, fn () => $browser->attribute('[data-action="visit-site"]', 'href') === 'http://localhost:8000')
                ->visit('/admin/articles/create')
                ->waitFor('#name')
                ->assertMissing('[data-content-locale]')
                ->type('#name', 'Hallo Welt')
                ->waitUsing(5, 100, fn () => $browser->value('[data-field="code"] input') === 'hallo-welt')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/articles')
                ->waitForText('Hallo Welt');

            // Back on the main site the German article is not in the list.
            $main = Site::query()->where('code', 'main')->sole();
            $browser->select('[data-site-switcher]', (string) $main->id)
                ->waitUsing(5, 100, fn () => $browser->attribute('[data-action="visit-site"]', 'href') === 'http://127.0.0.1:8000')
                ->assertDontSee('Hallo Welt')
                ->visit('/admin/articles/create')
                ->waitFor('[data-content-locale="de"]')
                ->type('#name', 'Hello world')
                ->waitUsing(5, 100, fn () => $browser->value('[data-field="code"] input') === 'hello-world')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/articles')
                ->waitForText('Hello world')
                ->assertDontSee('Hallo Welt');

            $this->assertSame($site->id, Article::query()->withoutGlobalScopes()->where('code->de', 'hallo-welt')->sole()->site_id);
            $this->assertSame($main->id, Article::query()->withoutGlobalScopes()->where('code->en', 'hello-world')->sole()->site_id);

            // The German site: its article, in German, without a language prefix.
            $browser->visit('http://localhost:8000/articles/hallo-welt')
                ->assertSeeIn('h1', 'Hallo Welt');
            $this->assertSame('de', $browser->script('return document.documentElement.lang;')[0]);

            $browser->visit('http://localhost:8000/articles/hello-world')->assertSee('404');

            // The main site: its own article only.
            $browser->visit('http://127.0.0.1:8000/articles/hello-world')
                ->assertSeeIn('h1', 'Hello world');
            $this->assertSame('en', $browser->script('return document.documentElement.lang;')[0]);

            $browser->visit('http://127.0.0.1:8000/de/articles/hallo-welt')->assertSee('404');
        });
    }
}
