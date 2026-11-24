<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Blocks\Tests\Feature;

use Atrium\Admin\Embeds\Embeds;
use Atrium\Admin\Models\Site;
use Atrium\Admin\Modules\Modules;
use Atrium\Admin\Support\Sites;
use Atrium\Blocks\Blocks;
use Atrium\Blocks\Models\Block;
use Atrium\Blocks\Tests\TestCase;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;

/**
 * What a template and a text show of a block: the text as written, in the
 * language of the page, kept in the cache until a block changes.
 */
final class BlocksTest extends TestCase
{
    private function blocks(): Blocks
    {
        return $this->app->make(Blocks::class);
    }

    public function test_a_block_is_its_content_as_written(): void
    {
        $this->block('phone', '<a href="tel:+100">+1 00</a>');

        $this->assertSame('<a href="tel:+100">+1 00</a>', $this->blocks()->html('phone'));
    }

    public function test_a_block_that_is_off_or_missing_is_the_empty_string(): void
    {
        $this->block('banner', 'Sale', ['active' => false]);

        $this->assertSame('', $this->blocks()->html('banner'));
        $this->assertSame('', $this->blocks()->html('nothing'));
        $this->assertSame('', $this->blocks()->html(''));
    }

    public function test_a_block_without_content_is_the_empty_string(): void
    {
        Block::query()->create(['name' => 'Empty', 'code' => 'empty']);

        $this->assertSame('', $this->blocks()->html('empty'));
    }

    public function test_the_text_comes_in_the_language_of_the_page_and_falls_back_to_the_default(): void
    {
        Block::query()->create(['name' => 'Hours', 'code' => 'hours', 'content' => ['en' => 'Open daily', 'de' => 'Täglich geöffnet']]);

        $this->assertSame('Open daily', $this->blocks()->html('hours'));

        app()->setLocale('de');
        $this->assertSame('Täglich geöffnet', $this->blocks()->html('hours'));

        app()->setLocale('fr');
        $this->assertSame('Open daily', $this->blocks()->html('hours'), 'a language without a text shows the default one');
    }

    public function test_a_block_is_read_once_until_one_is_saved_or_deleted(): void
    {
        $phone = $this->block('phone', '+1 00');
        $this->blocks()->html('phone');

        DB::enableQueryLog();
        $this->assertSame('+1 00', $this->blocks()->html('phone'));
        $this->assertSame([], DB::getQueryLog(), 'the second read does not ask the database');
        DB::disableQueryLog();

        $phone->update(['content' => ['en' => '+2 00']]);
        $this->assertSame('+2 00', $this->blocks()->html('phone'));

        $this->block('footer', 'Hello');
        $this->assertSame('Hello', $this->blocks()->html('footer'));

        $phone->delete();
        $this->assertSame('', $this->blocks()->html('phone'));
        $this->assertSame('Hello', $this->blocks()->html('footer'));
    }

    public function test_a_missing_block_is_remembered_too_and_found_once_it_is_made(): void
    {
        $this->assertSame('', $this->blocks()->html('later'));

        DB::enableQueryLog();
        $this->assertSame('', $this->blocks()->html('later'));
        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();

        $this->block('later', 'Now');
        $this->assertSame('Now', $this->blocks()->html('later'));
    }

    public function test_every_language_is_kept_apart_in_the_cache(): void
    {
        Block::query()->create(['name' => 'Hours', 'code' => 'hours', 'content' => ['en' => 'Open', 'de' => 'Offen']]);

        $this->assertSame('Open', $this->blocks()->html('hours'));
        app()->setLocale('de');
        $this->assertSame('Offen', $this->blocks()->html('hours'));
        app()->setLocale('en');
        $this->assertSame('Open', $this->blocks()->html('hours'));
    }

    public function test_the_text_is_kept_for_an_hour(): void
    {
        $this->travelTo('2026-03-10 12:00:00');
        $this->block('phone', '+1 00');
        $this->blocks()->html('phone');

        // Behind the cache's back: a write that fires no model event.
        DB::table('blocks')->update(['content' => json_encode(['en' => '+2 00'])]);

        $this->travelTo('2026-03-10 12:59:59');
        $this->assertSame('+1 00', $this->blocks()->html('phone'));

        $this->travelTo('2026-03-10 13:00:00');
        $this->assertSame('+2 00', $this->blocks()->html('phone'));
    }

    public function test_the_directive_and_the_helper_print_the_block(): void
    {
        $this->block('phone', '<b>+1 00</b>');

        // A new text each run: a compiled copy of an earlier one would hide a missing directive.
        $run = uniqid();

        $this->assertSame("<p id=\"{$run}\"><b>+1 00</b></p>", Blade::render("<p id=\"{$run}\">@block('phone')</p>"));
        $this->assertSame('<b>+1 00</b>', atrium_block('phone'));
        $this->assertSame("<p id=\"{$run}-2\"></p>", Blade::render("<p id=\"{$run}-2\">@block('nothing')</p>"));
    }

    public function test_a_page_of_the_site_shows_its_block_and_not_the_block_of_another_site(): void
    {
        $this->get('/shop-page')->assertOk()->assertSee('<footer></footer>', false);

        $this->block('phone', '+1 00');
        $this->get('/shop-page')->assertOk()->assertSee('<footer>+1 00</footer>', false);

        $other = Site::query()->create(['code' => 'shop', 'name' => 'Shop', 'domains' => ['shop.example.org'], 'languages' => ['en']]);
        DB::table('blocks')->update(['site_id' => $other->id]);
        $this->app->make(Blocks::class)->flush();

        $this->get('/shop-page')->assertOk()->assertSee('<footer></footer>', false);
    }

    public function test_a_block_may_hold_another_block_and_a_form(): void
    {
        $inner = $this->block('inner', '<i>inner</i>');
        $this->block('outer', 'Outer '.$this->placeholder($inner->id));

        $this->assertSame('Outer <i>inner</i>', $this->blocks()->html('outer'));
        // What is cached is the block as written; the inner one is read on each page.
        $inner->update(['content' => ['en' => '<i>changed</i>']]);
        $this->assertSame('Outer <i>changed</i>', $this->blocks()->html('outer'));
    }

    public function test_a_block_that_holds_itself_stops_there(): void
    {
        $self = $this->block('self', 'a');
        $self->update(['content' => ['en' => 'a'.$this->placeholder($self->id).'b']]);

        $this->assertSame('ab', $this->blocks()->html('self'));
    }

    public function test_two_blocks_that_hold_each_other_stop_too(): void
    {
        $first = $this->block('first', 'one');
        $second = $this->block('second', 'two'.$this->placeholder($first->id));
        $first->update(['content' => ['en' => 'one'.$this->placeholder($second->id)]]);

        $this->assertSame('onetwo', $this->blocks()->html('first'));
        $this->assertSame('twoone', $this->blocks()->html('second'));
    }

    public function test_the_same_block_twice_on_a_page_is_not_a_loop(): void
    {
        $inner = $this->block('inner', 'x');
        $this->block('outer', $this->placeholder($inner->id).'-'.$this->placeholder($inner->id));

        $this->assertSame('x-x', $this->blocks()->html('outer'));
    }

    public function test_a_failing_read_does_not_leave_the_block_marked_as_being_drawn(): void
    {
        $calls = 0;
        $cache = Mockery::mock(Repository::class);
        $cache->shouldReceive('rememberForever')->andReturnUsing(function () use (&$calls): string {
            if (++$calls === 1) {
                throw new RuntimeException('The cache is down.');
            }

            return 'v';
        });
        $cache->shouldReceive('remember')->andReturn(['html' => '+1 00']);
        $blocks = new Blocks($cache, $this->app->make(Sites::class), $this->app->make(Modules::class), $this->app->make(Embeds::class));

        try {
            $blocks->html('phone');
            $this->fail('The cache should have failed the read.');
        } catch (RuntimeException $e) {
            $this->assertSame('The cache is down.', $e->getMessage());
        }

        $this->assertSame('+1 00', $blocks->html('phone'));
    }

    public function test_codes_that_only_look_alike_are_different_blocks(): void
    {
        // "10" and "1e1" are the same number to a loose comparison.
        $inner = $this->block('1e1', 'inner');
        $this->block('10', 'outer '.$this->placeholder($inner->id));

        $this->assertSame('outer inner', $this->blocks()->html('10'));
    }
}
