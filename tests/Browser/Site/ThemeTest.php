<?php

declare(strict_types=1);

namespace Tests\Browser\Site;

use App\Models\Article;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Stage 6 acceptance: the public site drawn by the theme, with no template
 * written for the module, in light and dark.
 */
final class ThemeTest extends DuskTestCase
{
    use DatabaseMigrations;

    private const BACKGROUND = ['light' => 'rgb(255, 255, 255)', 'dark' => 'rgb(18, 20, 24)'];

    public function test_from_the_home_page_to_an_article_and_into_the_dark(): void
    {
        Article::query()->create([
            'name' => 'Hello world',
            'code' => 'hello-world',
            'active' => true,
            'preview_text' => '<p>The first article.</p>',
            'detail_text' => '<p>All of it.</p>',
        ]);

        $this->browse(function (Browser $browser): void {
            $browser->visit('/')
                ->script('localStorage.clear()');

            $browser->visit('/')
                ->assertSeeIn('h2', 'Articles')
                ->assertSee('The first article.')
                ->clickLink('Hello world')
                ->assertPathIs('/articles/hello-world')
                ->assertSeeIn('h1', 'Hello world')
                ->assertSee('All of it.')
                ->assertSeeLink('Articles');

            // Until the visitor chooses, the system decides.
            $system = $browser->script("return matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';")[0];
            $this->assertSame($system, $this->theme($browser));
            $this->assertSame(self::BACKGROUND[$system], $this->background($browser));

            $other = $system === 'dark' ? 'light' : 'dark';
            $browser->click('[data-theme-toggle]')
                ->assertSee($other === 'dark' ? 'Light theme' : 'Dark theme')
                ->assertAttribute('[data-theme-toggle]', 'aria-pressed', $other === 'dark' ? 'true' : 'false');
            $this->assertSame($other, $this->theme($browser));
            $this->assertSame(self::BACKGROUND[$other], $this->background($browser));

            // The choice outlives the page.
            $browser->clickLink('Articles')->assertPathIs('/articles');
            $this->assertSame($other, $this->theme($browser));
            $this->assertSame(self::BACKGROUND[$other], $this->background($browser));

            $browser->click('[data-theme-toggle]');
            $this->assertSame(self::BACKGROUND[$system], $this->background($browser));
        });
    }

    private function theme(Browser $browser): string
    {
        return (string) $browser->script('return document.documentElement.dataset.theme;')[0];
    }

    private function background(Browser $browser): string
    {
        return (string) $browser->script('return getComputedStyle(document.body).backgroundColor;')[0];
    }
}
