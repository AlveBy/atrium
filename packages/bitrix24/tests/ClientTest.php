<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24\Tests;

use Atrium\Admin\Facades\Setting;
use Atrium\Bitrix24\Support\Bitrix24Exception;
use Atrium\Bitrix24\Support\Client;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

final class ClientTest extends TestCase
{
    public function test_without_a_webhook_there_is_nothing_to_call(): void
    {
        $this->assertNull(app(Client::class)->webhook());

        $this->expectException(Bitrix24Exception::class);
        $this->expectExceptionMessage('The Bitrix24 webhook is not set');
        app(Client::class)->call('profile');
    }

    public function test_an_empty_webhook_counts_as_none(): void
    {
        Setting::save('bitrix24', ['webhook' => '']);

        $this->assertNull(app(Client::class)->webhook());
    }

    public function test_a_call_posts_the_parameters_as_json_to_the_method_under_the_webhook(): void
    {
        $this->connect();
        Http::fake(['*' => Http::response(['result' => 15])]);

        $result = app(Client::class)->call('crm.lead.add', ['fields' => ['TITLE' => 'Hi']]);

        $this->assertSame(15, $result);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://portal.example.test/rest/1/abc123def456/crm.lead.add.json'
            && $request->isJson()
            && $request->data() === ['fields' => ['TITLE' => 'Hi']]
            && $request->hasHeader('Accept', 'application/json'));
    }

    public function test_a_webhook_without_the_closing_slash_works_the_same(): void
    {
        Setting::save('bitrix24', ['webhook' => 'https://portal.example.test/rest/1/abc123def456']);
        Http::fake(['*' => Http::response(['result' => true])]);

        app(Client::class)->call('profile');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://portal.example.test/rest/1/abc123def456/profile.json');
    }

    public function test_a_call_waits_fifteen_seconds_at_most(): void
    {
        $this->connect();
        $timeouts = [];
        Http::fake(function (Request $request, array $options) use (&$timeouts) {
            $timeouts[] = $options['timeout'];

            return Http::response(['result' => true]);
        });

        app(Client::class)->call('profile');

        $this->assertSame([15], $timeouts);
    }

    public function test_the_portal_refusing_is_reported_with_its_reason(): void
    {
        $this->connect();
        Http::fake(['*' => Http::response(['error' => 'INVALID_CREDENTIALS', 'error_description' => 'Invalid request credentials'], 401)]);

        try {
            app(Client::class)->call('profile');
            $this->fail('A refusal must throw.');
        } catch (Bitrix24Exception $e) {
            $this->assertSame('Bitrix24 refused: INVALID_CREDENTIALS Invalid request credentials', $e->getMessage());
        }
    }

    public function test_a_refusal_without_a_description_is_reported_by_its_code_alone(): void
    {
        $this->connect();
        Http::fake(['*' => Http::response(['error' => 'QUERY_LIMIT_EXCEEDED'], 200)]);

        try {
            app(Client::class)->call('profile');
            $this->fail('A refusal must throw.');
        } catch (Bitrix24Exception $e) {
            $this->assertSame('Bitrix24 refused: QUERY_LIMIT_EXCEEDED', $e->getMessage());
        }
    }

    public function test_an_error_answer_without_a_reason_is_reported_by_its_status(): void
    {
        $this->connect();
        Http::fake(['*' => Http::response('<html>Bad gateway</html>', 502)]);

        $this->expectExceptionMessage('Bitrix24 refused: HTTP 502');
        app(Client::class)->call('profile');
    }

    public function test_an_error_that_is_not_text_is_reported_by_the_status(): void
    {
        $this->connect();
        Http::fake(['*' => Http::response(['error' => ['code' => 1]], 400)]);

        $this->expectExceptionMessage('Bitrix24 refused: HTTP 400');
        app(Client::class)->call('profile');
    }

    public function test_a_portal_that_does_not_answer_is_reported_without_its_address(): void
    {
        $this->connect();
        Http::fake(['*' => fn () => throw new ConnectionException('cURL error 6: Could not resolve host portal.example.test/rest/1/abc123def456')]);

        try {
            app(Client::class)->call('profile');
            $this->fail('No answer must throw.');
        } catch (Bitrix24Exception $e) {
            $this->assertSame('Bitrix24 does not answer. Check the portal address in the webhook.', $e->getMessage());
            $this->assertStringNotContainsString('abc123def456', $e->getMessage());
        }
    }

    public function test_the_webhook_pattern_takes_what_bitrix24_shows_and_nothing_else(): void
    {
        foreach (['https://portal.bitrix24.com/rest/1/abc123/', 'http://crm.example.test/rest/12/Zz9', 'https://crm.example.test:8443/rest/7/k1k1k1k1/'] as $good) {
            $this->assertSame(1, preg_match(Client::WEBHOOK_PATTERN, $good), $good);
        }

        foreach (['', 'portal.bitrix24.com/rest/1/abc/', 'https://portal.bitrix24.com/', 'https://portal.bitrix24.com/rest/x/abc/', 'https://portal.bitrix24.com/rest/1/', 'https://portal.bitrix24.com/rest/1/abc/crm.lead.add.json', 'https://portal.bitrix24.com/rest/1/ab c/', 'ftp://portal.bitrix24.com/rest/1/abc/', 'https://a b/rest/1/abc/'] as $bad) {
            $this->assertSame(0, preg_match(Client::WEBHOOK_PATTERN, $bad), $bad);
        }
    }
}
