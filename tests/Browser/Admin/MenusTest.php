<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use App\Models\Article;
use Atrium\Menus\Models\MenuItem;
use Laravel\Dusk\Browser;

/**
 * An editor builds the header menu: an address, a record picked by name,
 * one item dragged under another; the site's header shows it with the
 * current page marked.
 */
final class MenusTest extends AdminTestCase
{
    private const FIRST = 'li[data-tree-depth="1"][data-tree-item="0"] > [data-tree-body]';

    private const SECOND = 'li[data-tree-depth="1"][data-tree-item="1"] > [data-tree-body]';

    public function test_build_a_menu_by_dragging_and_see_it_in_the_header(): void
    {
        $admin = $this->admin();
        $article = Article::query()->create(['name' => 'Hello world', 'code' => 'hello-world', 'active' => true]);

        $this->browse(function (Browser $browser) use ($admin, $article): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/menus/create')
                ->waitFor('#name')
                ->type('#name', 'Header')
                ->type('#code', 'header')
                ->click('[data-action="tree-add"]')
                ->waitFor(self::FIRST)
                ->type(self::FIRST.' input[id$=".title"]', 'Blog')
                ->click(self::FIRST.' [data-link-kind="url"]')
                ->type(self::FIRST.' [data-link-url]', '/articles')
                ->click('[data-action="tree-add"]')
                ->waitFor(self::SECOND)
                ->type(self::SECOND.' input[id$=".title"]', 'First post')
                ->click(self::SECOND.' [data-link-kind="record"]')
                ->select(self::SECOND.' [data-link-module]', Article::class)
                ->type(self::SECOND.' [data-link-search]', 'Hello')
                ->waitFor(self::SECOND." [data-link-record=\"{$article->id}\"]")
                ->click(self::SECOND." [data-link-record=\"{$article->id}\"]")
                ->waitFor(self::SECOND.' [data-link-chosen]');

            // Drag the second item onto the middle of the first.
            $browser->script(<<<'JS'
                const rows = document.querySelectorAll('li[data-tree-depth="1"] > [data-tree-row]');
                const [target, source] = rows;
                const box = target.getBoundingClientRect();
                const at = { bubbles: true, cancelable: true, clientY: box.top + box.height / 2, dataTransfer: new DataTransfer() };
                source.dispatchEvent(new DragEvent('dragstart', at));
                target.dispatchEvent(new DragEvent('dragover', at));
                target.dispatchEvent(new DragEvent('drop', at));
                source.dispatchEvent(new DragEvent('dragend', at));
            JS);

            $browser->waitFor('li[data-tree-depth="2"]')
                ->assertSeeIn('li[data-tree-depth="2"] [data-tree-title]', 'First post')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/menus')
                ->waitForText('Header');

            $blog = MenuItem::query()->where('title', 'Blog')->sole();
            $post = MenuItem::query()->where('title', 'First post')->sole();
            $this->assertSame('/articles', $blog->url);
            $this->assertSame($blog->id, $post->parent_id);
            $this->assertSame([Article::class, $article->id], [$post->linkable_type, $post->linkable_id]);

            $browser->visit('/articles/hello-world')
                ->assertSeeIn('header nav[data-menu]', 'Blog')
                ->assertAttribute('header nav[data-menu] a[aria-current="page"]', 'href', url('/articles/hello-world'))
                ->assertPresent('header nav[data-menu] > ul > li.is-active');
        });
    }
}
