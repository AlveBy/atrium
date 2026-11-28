<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin\Concerns;

use Atrium\Pages\Models\Page;
use Facebook\WebDriver\WebDriverBy;
use Laravel\Dusk\Browser;

/**
 * What browser tests of the page editor share: stored pages of blocks, and
 * how the editor is read and driven.
 */
trait EditsPageBlocks
{
    /**
     * A page of the layout given: a list of sections, each a list of
     * columns, each a list of blocks. Ids of sections and columns are
     * s1, s2 and c1, c2, … in the order given.
     *
     * @param  list<list<list<array<string,mixed>>>>  $sections
     */
    private function pageOf(array $sections): Page
    {
        return Page::query()->create(['name' => 'Camp', 'code' => 'camp', 'layout' => $this->layoutOf($sections)]);
    }

    /**
     * @param  list<list<list<array<string,mixed>>>>  $sections
     * @return array<string,mixed>
     */
    private function layoutOf(array $sections): array
    {
        $column = 0;
        $tree = [];

        foreach ($sections as $number => $columns) {
            $made = [];

            foreach ($columns as $blocks) {
                $made[] = ['id' => 'c'.(++$column), 'settings' => ['span' => intdiv(12, count($columns))], 'blocks' => $blocks];
            }

            $tree[] = ['id' => 's'.($number + 1), 'settings' => [], 'columns' => $made];
        }

        return ['version' => 1, 'sections' => $tree];
    }

    /** @return array<string,mixed> */
    private function textBlock(string $id, string $text): array
    {
        return ['id' => $id, 'type' => 'text', 'settings' => ['html' => ['en' => "<p>{$text}</p>"]]];
    }

    /** @return array<string,mixed> */
    private function buttonBlock(string $id, string $label, ?string $url = '/contacts'): array
    {
        return ['id' => $id, 'type' => 'button', 'settings' => [
            'label' => ['en' => $label],
            ...($url === null ? [] : ['link' => ['type' => 'url', 'url' => $url]]),
        ]];
    }

    private function openEditor(Browser $browser, Page $page): Browser
    {
        $browser->visit("/admin/pages/{$page->id}/builder/layout")->waitFor('@builder-outline');

        return $this->drawn($browser);
    }

    /** The preview has drawn what is in the editor now. */
    private function drawn(Browser $browser): Browser
    {
        return $browser->waitFor('[dusk="builder-preview"][data-state="ready"]', 10);
    }

    private function editorIs(Browser $browser, string $status): Browser
    {
        return $browser->waitFor('[dusk="builder-status"][data-status="'.$status.'"]', 10);
    }

    /** What was last said to screen readers; the words are not on the screen. */
    private function said(Browser $browser, string $words): Browser
    {
        return $browser->waitUntil('document.querySelector(\'[dusk="builder-announcement"]\').textContent === '.json_encode($words), 5, "Not said: {$words}");
    }

    /** @return list<string> the ids of the blocks of a column, in their order */
    private function order(Browser $browser, int $section, int $column): array
    {
        return $browser->script('return [...document.querySelectorAll(\'[dusk="layout-list-'.$section.'-'.$column.'"] > [data-node-block]\')].map((block) => block.dataset.nodeBlock);')[0];
    }

    /** The editor's buttons are at its top, under the panel's sticky header once scrolled. */
    private function toTop(Browser $browser): void
    {
        $browser->script('window.scrollTo(0, 0);');
    }

    /**
     * Drags the first element onto the second, as a mouse does: press, move
     * a little so a drag starts, move there, release.
     */
    private function drag(Browser $browser, string $from, string $to, int $down = 0): void
    {
        $find = fn (string $selector) => $browser->driver->findElement(WebDriverBy::cssSelector($selector));
        $this->toTop($browser);

        $browser->driver->action()
            ->moveToElement($find($from))
            ->clickAndHold()
            ->moveByOffset(6, 6)
            ->moveByOffset(6, 6)
            ->moveToElement($find($to), 0, $down)
            ->moveByOffset(1, 1)
            ->release()
            ->perform();
    }
}
