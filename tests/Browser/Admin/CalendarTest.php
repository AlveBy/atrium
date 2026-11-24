<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Models\AdminUser;
use Atrium\News\Models\News;
use Carbon\CarbonImmutable;
use Facebook\WebDriver\WebDriverBy;
use Laravel\Dusk\Browser;

/**
 * The calendar of the news by the day they go live: a news item is dragged to
 * another day with its time, a click on a day opens the form for it, and
 * without the right to update nothing is dragged.
 */
final class CalendarTest extends AdminTestCase
{
    private function day(int $number): string
    {
        return CarbonImmutable::today()->startOfMonth()->addDays($number - 1)->format('Y-m-d');
    }

    private function news(string $name, string $stamp): News
    {
        return News::query()->create(['name' => $name, 'code' => str($name)->slug()->toString(), 'active' => true, 'active_from' => $stamp]);
    }

    /**
     * A drag a person makes: a press, a few moves, a release.
     */
    private function drag(Browser $browser, string $event, string $to): void
    {
        $actions = $browser->driver->action();
        $actions->moveToElement($browser->driver->findElement(WebDriverBy::cssSelector($event)))
            ->clickAndHold()
            ->moveByOffset(8, 8)
            ->moveByOffset(8, 8)
            ->moveToElement($browser->driver->findElement(WebDriverBy::cssSelector($to)), 10, 20)
            ->moveByOffset(2, 2)
            ->release()
            ->perform();
    }

    public function test_news_dragged_to_another_day_is_saved_there_with_its_time(): void
    {
        $admin = $this->admin();
        $item = $this->news('Opening', $this->day(10).' 10:30:00');
        $target = $this->day(17);

        $this->browse(function (Browser $browser) use ($admin, $item, $target): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/news')
                ->waitFor('[data-view-switcher]')
                ->click('[data-view-choice="calendar"]')
                ->waitFor('[data-calendar]')
                ->assertQueryStringHas('view', 'calendar')
                ->assertAttribute('[data-view-choice="calendar"]', 'aria-pressed', 'true')
                ->assertAttribute('[data-calendar-mode="month"]', 'aria-pressed', 'true')
                ->assertPresent("[data-calendar-day=\"{$this->day(10)}\"] [data-event=\"{$item->id}\"]");

            $this->drag($browser, "[data-event=\"{$item->id}\"]", "[data-day=\"{$target}\"]");

            $browser->waitFor("[data-calendar-day=\"{$target}\"] [data-event=\"{$item->id}\"]")
                ->assertMissing("[data-calendar-day=\"{$this->day(10)}\"] [data-event=\"{$item->id}\"]")
                ->assertMissing('[data-calendar-error]');

            $this->assertSame($target.' 10:30:00', $item->fresh()->active_from->format('Y-m-d H:i:s'));
            $this->assertSame('Opening', $item->fresh()->name);

            // The look is remembered: the list opens as a calendar next time.
            $browser->visit('/admin/news')->waitFor('[data-calendar]');
        });
    }

    public function test_the_date_on_a_row_of_the_list_moves_news_without_dragging(): void
    {
        $admin = $this->admin();
        $item = $this->news('Opening', $this->day(10).' 10:30:00');

        $this->browse(function (Browser $browser) use ($admin, $item): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/news?view=calendar&mode=list')
                ->waitFor("[data-event=\"{$item->id}\"] [data-move-to]")
                ->assertAttribute('[data-calendar-mode="list"]', 'aria-pressed', 'true')
                ->script("const input = document.querySelector('[data-event=\"{$item->id}\"] [data-move-to]'); input.value = '{$this->day(20)}'; input.dispatchEvent(new Event('change', { bubbles: true }))");

            $browser->waitFor("[data-calendar-day=\"{$this->day(20)}\"] [data-event=\"{$item->id}\"]");

            $this->assertSame($this->day(20).' 10:30:00', $item->fresh()->active_from->format('Y-m-d H:i:s'));
        });
    }

    public function test_a_click_on_a_day_opens_the_form_with_that_day(): void
    {
        $admin = $this->admin();
        $day = $this->day(12);
        $empty = $this->day(13);

        $this->browse(function (Browser $browser) use ($admin, $day, $empty): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/news?view=calendar')
                ->waitFor("[data-day-add=\"{$day}\"]")
                ->click("[data-day-add=\"{$day}\"]")
                ->waitForLocation('/admin/news/create')
                ->assertQueryStringHas('active_from', $day)
                ->assertValue('[data-field="active_from"] input', $day.'T09:00')
                ->visit('/admin/news?view=calendar')
                ->waitFor("[data-day=\"{$empty}\"]")
                ->click("[data-day=\"{$empty}\"]")
                ->waitForLocation('/admin/news/create')
                ->assertQueryStringHas('active_from', $empty);
        });
    }

    public function test_the_calendar_turns_to_the_next_month_and_the_week_and_back_to_today(): void
    {
        $admin = $this->admin();
        $next = CarbonImmutable::today()->startOfMonth()->addMonth();

        $this->browse(function (Browser $browser) use ($admin, $next): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/news?view=calendar')
                ->waitFor('[data-calendar-title]')
                ->click('[data-calendar-nav="next"]')
                ->waitUsing(5, 100, fn (): bool => str_contains($browser->driver->getCurrentURL(), 'date='.$next->format('Y-m-d')))
                ->click('[data-calendar-mode="week"]')
                ->waitFor('[data-calendar][data-mode="week"]')
                ->assertQueryStringHas('mode', 'week')
                ->click('[data-calendar-nav="today"]')
                ->waitUsing(5, 100, fn (): bool => str_contains($browser->driver->getCurrentURL(), 'date='.CarbonImmutable::today()->format('Y-m-d')))
                ->assertPresent('[data-calendar-day="'.CarbonImmutable::today()->format('Y-m-d').'"]');
        });
    }

    public function test_without_the_right_to_update_nothing_can_be_moved(): void
    {
        $reader = AdminUser::query()->create(['name' => 'Reader', 'email' => 'reader@example.com', 'password' => 'correct-horse', 'is_active' => true]);
        $reader->roles()->attach(AdminRole::query()->create(['name' => 'Reader', 'permissions' => ['news.view']]));
        $item = $this->news('Opening', $this->day(10).' 10:30:00');
        $target = $this->day(17);

        $this->browse(function (Browser $browser) use ($reader, $item, $target): void {
            $browser->loginAs($reader, 'admin')
                ->visit('/admin/news?view=calendar')
                ->waitFor("[data-event=\"{$item->id}\"]")
                ->assertMissing('[data-day-add]');

            $this->drag($browser, "[data-event=\"{$item->id}\"]", "[data-day=\"{$target}\"]");

            $browser->pause(500)
                ->assertPresent("[data-calendar-day=\"{$this->day(10)}\"] [data-event=\"{$item->id}\"]")
                ->assertMissing("[data-calendar-day=\"{$target}\"] [data-event=\"{$item->id}\"]")
                ->visit('/admin/news?view=calendar&mode=list')
                ->waitFor("[data-event=\"{$item->id}\"]")
                ->assertMissing('[data-move-to]');

            $this->assertSame($this->day(10).' 10:30:00', $item->fresh()->active_from->format('Y-m-d H:i:s'));
        });
    }

    public function test_a_move_the_server_refuses_puts_the_news_back_and_says_why(): void
    {
        $admin = $this->admin();
        $item = $this->news('Opening', $this->day(10).' 10:30:00');
        $target = $this->day(17);

        $this->browse(function (Browser $browser) use ($admin, $item, $target): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/news?view=calendar')
                ->waitFor("[data-event=\"{$item->id}\"]");

            // The administrator's session ends behind the page's back.
            AdminUser::query()->whereKey($admin->id)->update(['is_active' => false]);

            $this->drag($browser, "[data-event=\"{$item->id}\"]", "[data-day=\"{$target}\"]");

            $browser->waitFor('[data-calendar-error]')
                ->assertPresent("[data-calendar-day=\"{$this->day(10)}\"] [data-event=\"{$item->id}\"]")
                ->assertMissing("[data-calendar-day=\"{$target}\"] [data-event=\"{$item->id}\"]");

            $this->assertSame($this->day(10).' 10:30:00', $item->fresh()->active_from->format('Y-m-d H:i:s'));
        });
    }
}
