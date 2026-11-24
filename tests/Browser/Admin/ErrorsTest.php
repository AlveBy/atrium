<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Errors\Errors;
use Atrium\Errors\Models\Issue;
use Laravel\Dusk\Browser;
use RuntimeException;

/**
 * The error log of alveby/atrium-errors: the dashboard tile, the list with
 * its filters, and one error with its trace, chart and status buttons.
 */
final class ErrorsTest extends AdminTestCase
{
    public function test_find_an_error_read_its_trace_and_resolve_it_in_the_next_release(): void
    {
        $admin = $this->admin();
        $room = $this->capture(2, fn (): RuntimeException => $this->roomTaken());
        $feed = $this->capture(1, fn (): RuntimeException => $this->feedSilent());

        $this->browse(function (Browser $browser) use ($admin, $room, $feed): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/errors')
                ->waitFor('@errors-table')
                ->assertAttribute('@errors-status-new', 'aria-pressed', 'true')
                ->assertSeeIn('@errors-table', 'Room 12 is not free')
                ->assertSeeIn('@errors-table', 'The price feed did not answer')
                ->screenshot('errors/list');

            // The search box stands in one line with the selects beside it, its
            // label above it like theirs.
            $tops = $browser->script(<<<'JS'
                return ['search', 'site'].map((name) => Math.round(
                    document.querySelector(`[dusk="errors-${name}"]`).getBoundingClientRect().top,
                ));
            JS)[0];
            $this->assertSame($tops[1], $tops[0], 'the search box is out of line with the site select');

            $browser->type('@errors-search', 'Room 12')
                ->click('@errors-apply')
                ->waitUntilMissingText('The price feed did not answer')
                ->assertSeeIn('@errors-table', 'Room 12 is not free')
                ->type('@errors-search', 'nothing like this')
                ->click('@errors-apply')
                ->waitFor('@errors-empty')
                ->click('@errors-reset')
                ->waitFor('@errors-table')
                ->assertSeeIn('@errors-table', 'The price feed did not answer')
                ->clickAtXPath('//tr[@dusk="errors-row"][.//*[contains(., "Room 12")]]//a[@dusk="errors-open"]')
                ->waitFor('@error-message')
                ->assertPathIs('/admin/errors/'.$room->id)
                ->assertSeeIn('@error-message', 'Room 12 is not free on the chosen dates')
                ->assertSeeIn('@error-status', 'Unresolved')
                // The trace points at this file, with the code around the line.
                ->assertSeeIn('@error-exception', RuntimeException::class)
                ->assertSeeIn('@error-frame', 'ErrorsTest.php')
                ->assertPresent('@error-code')
                ->assertPresent('@bar-chart')
                ->click('@error-chart-daily')
                ->assertAttribute('@error-chart-daily', 'aria-pressed', 'true')
                ->click('@error-chart-hourly')
                ->assertAttribute('@error-chart-hourly', 'aria-pressed', 'true')
                // The event on screen is marked; the other one is a link to it.
                ->assertCount('@error-occurrences tbody tr', 2)
                ->assertCount('@error-occurrence', 1)
                ->click('@error-occurrence')
                ->waitUntil('location.search.includes("event=")')
                ->assertQueryStringHas('event')
                ->assertCount('@error-occurrence', 1);

            // The sticky header would take a click on a button scrolled under it.
            $browser->script('window.scrollTo(0, 0)');
            $browser->resize(1440, 2600)
                ->screenshot('errors/issue')
                ->resize(1920, 1080)
                ->click('@error-resolve-next')
                ->waitFor('@error-reopen')
                ->assertSeeIn('@error-status', 'Resolved')
                ->assertMissing('@error-resolve');

            $room->refresh();
            $this->assertSame(Issue::RESOLVED, $room->status);
            $this->assertNotNull($room->resolved_in);
            $this->assertSame(Issue::NEW, $feed->refresh()->status);

            // A resolved error leaves the default list and is found under its status.
            $browser->click('@error-back')
                ->waitFor('@errors-table')
                ->assertDontSeeIn('@errors-table', 'Room 12 is not free')
                ->click('@errors-status-resolved')
                ->waitForTextIn('@errors-table', 'Room 12 is not free')
                ->assertDontSeeIn('@errors-table', 'The price feed did not answer');
        });
    }

    public function test_the_dashboard_tile_counts_the_day_and_opens_the_noisiest_error(): void
    {
        $admin = $this->admin();
        $room = $this->capture(3, fn (): RuntimeException => $this->roomTaken());
        $this->capture(1, fn (): RuntimeException => $this->feedSilent());

        $this->browse(function (Browser $browser) use ($admin, $room): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor('@errors-gadget')
                ->assertSeeIn('@errors-gadget [data-count="events"]', '4')
                ->assertSeeIn('@errors-gadget [data-count="new"]', '2')
                ->assertSeeIn('@errors-gadget [data-count="unresolved"]', '2')
                ->assertSeeIn('@errors-gadget [data-bar-value]', '4')
                ->screenshot('errors/gadget')
                ->assertSeeIn('@errors-gadget-issue', 'Room 12 is not free')
                ->click('@errors-gadget-issue')
                ->waitFor('@error-message')
                ->assertPathIs('/admin/errors/'.$room->id);
        });
    }

    public function test_without_errors_the_tile_and_the_list_say_so(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor('@errors-gadget')
                ->assertSeeIn('@errors-gadget', 'No errors in the last 24 hours.')
                ->assertMissing('@errors-gadget @bar-chart')
                ->click('@errors-gadget-all')
                ->waitFor('@errors-empty')
                ->assertMissing('@errors-table');
        });

        $this->assertSame(0, Issue::query()->count());
    }

    /**
     * The same bug caught $times times through the real tracker.
     *
     * @param  callable(): RuntimeException  $bug
     */
    private function capture(int $times, callable $bug): Issue
    {
        $message = '';
        foreach (range(1, $times) as $ignored) {
            $e = $bug();
            $message = $e->getMessage();
            app(Errors::class)->capture($e);
        }

        return Issue::query()->where('message', $message)->sole();
    }

    private function roomTaken(): RuntimeException
    {
        return new RuntimeException('Room 12 is not free on the chosen dates');
    }

    private function feedSilent(): RuntimeException
    {
        return new RuntimeException('The price feed did not answer in 3 seconds');
    }
}
