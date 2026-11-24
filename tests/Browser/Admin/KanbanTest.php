<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Models\AdminUser;
use Atrium\Faq\Models\Category;
use Atrium\Faq\Models\Question;
use Atrium\Reviews\Models\Review;
use Facebook\WebDriver\WebDriverBy;
use Laravel\Dusk\Browser;

/**
 * The board: reviews by status and questions by category. A card is dragged
 * to another column, and dragged down inside its column, and the server has
 * it saved; without the right to update nothing is dragged.
 */
final class KanbanTest extends AdminTestCase
{
    /**
     * A drag a person makes: a press, a few moves, a release.
     */
    private function drag(Browser $browser, string $card, string $to, int $offset = 20): void
    {
        $actions = $browser->driver->action();
        $actions->moveToElement($browser->driver->findElement(WebDriverBy::cssSelector($card)))
            ->clickAndHold()
            ->moveByOffset(8, 8)
            ->moveByOffset(8, 8)
            ->moveToElement($browser->driver->findElement(WebDriverBy::cssSelector($to)), 10, $offset)
            ->moveByOffset(2, 2)
            ->release()
            ->perform();
    }

    private function review(string $author, string $status): Review
    {
        return Review::query()->create(['author_name' => $author, 'rating' => 5, 'text' => 'Fine', 'status' => $status]);
    }

    public function test_a_card_dragged_to_another_column_is_saved_there(): void
    {
        $admin = $this->admin();
        $anna = $this->review('Anna', 'new');
        $boris = $this->review('Boris', 'new');

        $this->browse(function (Browser $browser) use ($admin, $anna, $boris): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/reviews')
                ->waitFor('[data-view-switcher]')
                ->assertAttribute('[data-view-choice="table"]', 'aria-pressed', 'true')
                ->click('[data-view-choice="kanban"]')
                ->waitFor('[data-kanban]')
                ->assertQueryStringHas('view', 'kanban')
                ->assertAttribute('[data-view-choice="kanban"]', 'aria-pressed', 'true')
                ->assertPresent("[data-kanban-column=\"new\"] [data-card=\"{$anna->id}\"]")
                ->assertSeeIn('[data-kanban-column="new"] [data-column-count]', 'Cards: 2');

            $this->drag($browser, "[data-card=\"{$anna->id}\"]", '[data-kanban-column="published"] [data-column]');

            $browser->waitFor("[data-kanban-column=\"published\"] [data-card=\"{$anna->id}\"]")
                ->assertPresent("[data-kanban-column=\"new\"] [data-card=\"{$boris->id}\"]")
                ->assertMissing("[data-kanban-column=\"new\"] [data-card=\"{$anna->id}\"]")
                ->assertSeeIn('[data-kanban-column="new"] [data-column-count]', 'Cards: 1')
                ->assertSeeIn('[data-kanban-column="published"] [data-column-count]', 'Cards: 1')
                ->assertMissing('[data-kanban-error]');

            $this->assertSame('published', $anna->fresh()->status->value);
            $this->assertSame('new', $boris->fresh()->status->value);

            // The look is remembered: the list opens as a board next time.
            $browser->visit('/admin/reviews')->waitFor('[data-kanban]');
        });
    }

    public function test_the_select_on_a_card_moves_it_without_dragging(): void
    {
        $admin = $this->admin();
        $anna = $this->review('Anna', 'new');

        $this->browse(function (Browser $browser) use ($admin, $anna): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/reviews?view=kanban')
                ->waitFor("[data-card=\"{$anna->id}\"]")
                ->select("[data-card=\"{$anna->id}\"] [data-move-to]", 'rejected')
                ->waitFor("[data-kanban-column=\"rejected\"] [data-card=\"{$anna->id}\"]");

            $this->assertSame('rejected', $anna->fresh()->status->value);
        });
    }

    public function test_a_card_dragged_down_inside_its_column_keeps_the_new_order(): void
    {
        $admin = $this->admin();
        $category = Category::query()->create(['name' => 'General', 'code' => 'general']);
        $first = Question::query()->create(['category_id' => $category->id, 'question' => 'First?', 'answer' => 'A', 'sort' => 10]);
        $second = Question::query()->create(['category_id' => $category->id, 'question' => 'Second?', 'answer' => 'B', 'sort' => 20]);

        $this->browse(function (Browser $browser) use ($admin, $first, $second): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/faq_questions?view=kanban')
                ->waitFor("[data-card=\"{$first->id}\"]");

            $this->drag($browser, "[data-card=\"{$first->id}\"]", "[data-card=\"{$second->id}\"]", 12);

            $browser->waitUsing(5, 100, fn (): bool => $first->fresh()->sort > $second->fresh()->sort);
            $this->assertSame([$second->id, $first->id], Question::query()->orderBy('sort')->pluck('id')->all());
            $this->assertSame([$second->id, $first->id], array_map(
                fn ($node): int => (int) $node->getAttribute('data-card'),
                $browser->driver->findElements(WebDriverBy::cssSelector('[data-kanban] [data-card]')),
            ));
        });
    }

    public function test_a_column_with_more_cards_loads_them_on_request(): void
    {
        $admin = $this->admin();

        foreach (range(1, 22) as $number) {
            $this->review("Author {$number}", 'new');
        }

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/reviews?view=kanban')
                ->waitFor('[data-column-more]')
                ->assertSeeIn('[data-kanban-column="new"] [data-column-count]', 'Cards: 22');

            $this->assertCount(20, $browser->driver->findElements(WebDriverBy::cssSelector('[data-kanban-column="new"] [data-card]')));

            $browser->click('[data-column-more]')
                ->waitUntilMissing('[data-column-more]');

            $this->assertCount(22, $browser->driver->findElements(WebDriverBy::cssSelector('[data-kanban-column="new"] [data-card]')));
        });
    }

    public function test_the_search_narrows_the_board(): void
    {
        $admin = $this->admin();
        $anna = $this->review('Anna', 'new');
        $boris = $this->review('Boris', 'new');

        $this->browse(function (Browser $browser) use ($admin, $anna, $boris): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/reviews?view=kanban')
                ->waitFor("[data-card=\"{$boris->id}\"]")
                ->type('[data-search-input]', 'Anna')
                ->click('[data-action="search"]')
                ->waitUntilMissing("[data-card=\"{$boris->id}\"]")
                ->assertPresent("[data-card=\"{$anna->id}\"]")
                ->assertQueryStringHas('search', 'Anna')
                ->assertPresent('[data-kanban]');
        });
    }

    public function test_without_the_right_to_update_nothing_can_be_moved(): void
    {
        $reader = AdminUser::query()->create(['name' => 'Reader', 'email' => 'reader@example.com', 'password' => 'correct-horse', 'is_active' => true]);
        $reader->roles()->attach(AdminRole::query()->create(['name' => 'Reader', 'permissions' => ['reviews.view']]));
        $anna = $this->review('Anna', 'new');

        $this->browse(function (Browser $browser) use ($reader, $anna): void {
            $browser->loginAs($reader, 'admin')
                ->visit('/admin/reviews?view=kanban')
                ->waitFor("[data-card=\"{$anna->id}\"]")
                ->assertMissing('[data-move-to]');

            $this->drag($browser, "[data-card=\"{$anna->id}\"]", '[data-kanban-column="published"] [data-column]');

            $browser->pause(500)
                ->assertPresent("[data-kanban-column=\"new\"] [data-card=\"{$anna->id}\"]")
                ->assertMissing("[data-kanban-column=\"published\"] [data-card=\"{$anna->id}\"]");

            $this->assertSame('new', $anna->fresh()->status->value);
        });
    }

    public function test_a_move_the_server_refuses_puts_the_card_back_and_says_why(): void
    {
        $admin = $this->admin();
        $anna = $this->review('Anna', 'new');

        $this->browse(function (Browser $browser) use ($admin, $anna): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/reviews?view=kanban')
                ->waitFor("[data-card=\"{$anna->id}\"]");

            // The administrator's session ends behind the page's back.
            AdminUser::query()->whereKey($admin->id)->update(['is_active' => false]);

            $browser->select("[data-card=\"{$anna->id}\"] [data-move-to]", 'published')
                ->waitFor('[data-kanban-error]')
                ->assertPresent("[data-kanban-column=\"new\"] [data-card=\"{$anna->id}\"]")
                ->assertMissing("[data-kanban-column=\"published\"] [data-card=\"{$anna->id}\"]");

            $this->assertSame('new', $anna->fresh()->status->value);
        });
    }
}
