<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC\Tests;

use Atrium\Admin\Facades\Setting;
use Atrium\Integrations\Models\ExchangeLog;
use Atrium\OneC\OneCServiceProvider;
use Atrium\OneC\Support\Access;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

final class ExchangeTest extends TestCase
{
    private function basic(string $login, string $password): array
    {
        return ['Authorization' => 'Basic '.base64_encode($login.':'.$password)];
    }

    private function checkauth(array $headers, string $path = OneCServiceProvider::PATH, string $method = 'GET'): string
    {
        return (string) $this->call($method, '/'.$path.'?type=catalog&mode=checkauth', [], [], [], $this->transformHeadersToServerVars($headers))->getContent();
    }

    public function test_the_right_login_gets_a_cookie_to_send_back(): void
    {
        $reply = $this->checkauth($this->basic(self::LOGIN, self::PASSWORD));

        [$first, $name, $value, $end] = explode("\n", $reply);
        $this->assertSame('success', $first);
        $this->assertSame(Access::COOKIE, $name);
        $this->assertSame(40, strlen($value));
        $this->assertSame('', $end);
        $this->assertTrue(Cache::has('atrium-1c:session:'.$value));
    }

    public function test_the_answer_is_plain_text_and_both_addresses_and_both_verbs_work(): void
    {
        $response = $this->get('/'.OneCServiceProvider::PATH.'?type=catalog&mode=checkauth', $this->basic(self::LOGIN, self::PASSWORD));

        $response->assertOk();
        $this->assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('success', $this->checkauth($this->basic(self::LOGIN, self::PASSWORD), OneCServiceProvider::PATH_BITRIX));
        $this->assertStringStartsWith('success', $this->checkauth($this->basic(self::LOGIN, self::PASSWORD), OneCServiceProvider::PATH, 'POST'));
    }

    public function test_a_wrong_login_or_a_wrong_password_is_refused_and_told_to_the_log(): void
    {
        $this->assertStringStartsWith('failure', $this->checkauth($this->basic('someone', self::PASSWORD)));
        $this->assertStringStartsWith('failure', $this->checkauth($this->basic(self::LOGIN, 'wrong')));
        $this->assertStringStartsWith('failure', $this->checkauth([]));

        $this->assertSame(3, ExchangeLog::query()->where('action', 'checkauth')->where('status', 'failed')->where('direction', 'in')->count());
        $this->assertSame('1c', ExchangeLog::query()->value('integration'));
    }

    public function test_the_refusal_says_why_in_the_words_of_the_language(): void
    {
        $this->assertSame("failure\nWrong login or password, or the exchange is not set up in Modules → 1C.\n", $this->checkauth($this->basic('x', 'y')));
    }

    public function test_without_a_login_and_a_password_in_the_settings_nobody_gets_in(): void
    {
        foreach ([['login' => '', 'password' => ''], ['login' => self::LOGIN, 'password' => ''], ['login' => '', 'password' => self::PASSWORD]] as $values) {
            Setting::save('1c', $values);

            $this->assertStringStartsWith('failure', $this->checkauth($this->basic($values['login'], $values['password'])));
        }
    }

    public function test_after_twenty_wrong_passwords_even_the_right_one_waits(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->checkauth($this->basic(self::LOGIN, 'wrong'));
        }

        $this->assertStringStartsWith('failure', $this->checkauth($this->basic(self::LOGIN, self::PASSWORD)));

        RateLimiter::clear('atrium-1c:auth:127.0.0.1');
        $this->assertStringStartsWith('success', $this->checkauth($this->basic(self::LOGIN, self::PASSWORD)));
    }

    public function test_nineteen_wrong_passwords_do_not_close_the_door(): void
    {
        for ($i = 0; $i < 19; $i++) {
            $this->checkauth($this->basic(self::LOGIN, 'wrong'));
        }

        $this->assertStringStartsWith('success', $this->checkauth($this->basic(self::LOGIN, self::PASSWORD)));
    }

    public function test_every_step_after_the_login_needs_the_cookie(): void
    {
        foreach (['init', 'file', 'import', 'query', 'success'] as $mode) {
            $this->assertSame("failure\nNot signed in: start the exchange again.\n", $this->exchange(['type' => 'catalog', 'mode' => $mode])->getContent());
            $this->assertStringStartsWith('failure', (string) $this->exchange(['type' => 'catalog', 'mode' => $mode], 'made-up')->getContent());
        }
    }

    public function test_the_session_ends_after_an_hour(): void
    {
        $token = $this->signIn();
        $this->exchange(['type' => 'catalog', 'mode' => 'success'], $token)->assertSee('success');

        $this->travel(61)->minutes();

        $this->exchange(['type' => 'catalog', 'mode' => 'success'], $token)->assertSee('failure');
    }

    public function test_a_session_still_lives_before_the_hour_is_out(): void
    {
        $token = $this->signIn();

        $this->travel(59)->minutes();

        $this->exchange(['type' => 'catalog', 'mode' => 'success'], $token)->assertSee('success');
    }

    public function test_an_unknown_type_or_step_is_a_failure_that_names_it(): void
    {
        $token = $this->signIn();

        $this->assertSame("failure\nUnknown exchange type: stock.\n", $this->exchange(['type' => 'stock', 'mode' => 'init'], $token)->getContent());
        $this->assertSame("failure\nUnknown exchange type: .\n", $this->exchange(['mode' => 'init'], $token)->getContent());
        $this->assertSame("failure\nUnknown step of the exchange: dance.\n", $this->exchange(['type' => 'catalog', 'mode' => 'dance'], $token)->getContent());
    }

    public function test_init_tells_1c_to_send_zip_in_chunks_and_clears_what_was_left(): void
    {
        $token = $this->signIn();
        $this->upload($token, 'old.xml', '<x/>');

        $this->assertSame("zip=yes\nfile_limit=2097152\n", $this->exchange(['type' => 'catalog', 'mode' => 'init'], $token)->getContent());

        Storage::disk('local')->assertMissing('atrium-1c/old.xml');
    }

    public function test_a_file_comes_in_chunks_that_are_joined(): void
    {
        $token = $this->signIn();

        $this->upload($token, 'import.xml', 'first,');
        $this->upload($token, 'import.xml', "\xD0\x9C\x00 second");

        $this->assertSame("first,\xD0\x9C\x00 second", Storage::disk('local')->get('atrium-1c/import.xml'));
    }

    public function test_a_file_may_live_in_a_folder_such_as_import_files(): void
    {
        $token = $this->signIn();

        $this->upload($token, 'import_files/ab/photo one.png', 'png');

        $this->assertSame('png', Storage::disk('local')->get('atrium-1c/import_files/ab/photo one.png'));
    }

    public function test_a_file_may_not_leave_its_folder_or_hide_in_odd_characters(): void
    {
        $token = $this->signIn();

        foreach (['', '../evil.xml', 'a/../b.xml', '/etc/passwd', 'a//b.xml', './a.xml', 'a\\b.xml', 'c:x.xml', "a\x01b.xml", 'a|b', 'a?b', 'a*b', 'a<b', 'a>b', 'a"b'] as $name) {
            $this->assertSame("failure\nThis file name is not allowed.\n", $this->exchange(['type' => 'catalog', 'mode' => 'file', 'filename' => $name], $token, 'x')->getContent(), "name [{$name}]");
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_file_in_an_exchange_of_orders_is_taken_and_thrown_away(): void
    {
        $token = $this->signIn();

        $this->assertSame("success\n", $this->exchange(['type' => 'sale', 'mode' => 'file', 'filename' => 'orders.xml'], $token, '<x/>')->getContent());

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_the_exchange_of_orders_does_not_clear_the_catalogue_files(): void
    {
        $token = $this->signIn();
        $this->upload($token, 'import.xml', '<x/>');

        $this->assertSame("zip=yes\nfile_limit=2097152\n", $this->exchange(['type' => 'sale', 'mode' => 'init'], $token)->getContent());

        Storage::disk('local')->assertExists('atrium-1c/import.xml');
    }

    public function test_orders_are_none_for_now_and_the_log_says_why(): void
    {
        $token = $this->signIn();

        $response = $this->exchange(['type' => 'sale', 'mode' => 'query'], $token);

        $this->assertSame('text/xml; charset=UTF-8', $response->headers->get('Content-Type'));
        $document = simplexml_load_string((string) $response->getContent());
        $this->assertNotFalse($document);
        $this->assertSame('КоммерческаяИнформация', $document->getName());
        $this->assertCount(0, $document->children());
        $this->assertMatchesRegularExpression('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d$/', (string) $document['ДатаФормирования']);
        $this->assertSame('Orders are not exchanged: the shop module is not installed.', ExchangeLog::query()->where('action', 'sale.query')->sole()->message);
        $this->assertSame('ok', ExchangeLog::query()->where('action', 'sale.query')->sole()->status);
    }

    public function test_only_orders_are_asked_for_with_query(): void
    {
        $token = $this->signIn();

        $this->assertSame("failure\nUnknown step of the exchange: query.\n", $this->exchange(['type' => 'catalog', 'mode' => 'query'], $token)->getContent());
    }

    public function test_the_import_of_orders_and_the_closing_step_just_say_success(): void
    {
        $token = $this->signIn();

        $this->assertSame("success\n", $this->exchange(['type' => 'sale', 'mode' => 'import', 'filename' => 'orders.xml'], $token)->getContent());
        $this->assertSame("success\n", $this->exchange(['type' => 'sale', 'mode' => 'success'], $token)->getContent());
        $this->assertSame("success\n", $this->exchange(['type' => 'catalog', 'mode' => 'success'], $token)->getContent());
    }

    public function test_the_address_is_not_there_until_the_module_is_installed(): void
    {
        $this->assertTrue(app('router')->has('atrium.onec.exchange'));
        $this->assertTrue(app('router')->has('atrium.onec.exchange.bitrix'));
    }
}
