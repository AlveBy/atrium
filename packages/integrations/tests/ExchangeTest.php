<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Tests;

use Atrium\Integrations\Alerts\FailureMail;
use Atrium\Integrations\Enums\Direction;
use Atrium\Integrations\Enums\Status;
use Atrium\Integrations\Exchange;
use Atrium\Integrations\Models\ExchangeLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

final class ExchangeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-24 10:00:00');
        Mail::fake();
        $this->admin();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_line_has_everything_the_screen_shows(): void
    {
        $entry = app(Exchange::class)->record(
            'bitrix24', 'lead.add', Status::Ok,
            subject: 'Form submission #12', message: 'Lead 15', attempt: 2, durationMs: 340, direction: Direction::In,
        );

        $row = ExchangeLog::query()->sole();
        $this->assertTrue($row->is($entry));
        $this->assertSame('bitrix24', $row->integration);
        $this->assertSame('in', $row->direction);
        $this->assertSame('lead.add', $row->action);
        $this->assertSame('ok', $row->status);
        $this->assertSame('Form submission #12', $row->subject);
        $this->assertSame('Lead 15', $row->message);
        $this->assertNull($row->error);
        $this->assertSame(2, $row->attempt);
        $this->assertSame(340, $row->duration_ms);
        $this->assertSame('2026-11-24 10:00:00', $row->created_at?->format('Y-m-d H:i:s'));
    }

    public function test_the_defaults_are_a_call_from_the_site_on_the_first_attempt_with_nothing_said(): void
    {
        app(Exchange::class)->record('1c', 'catalog.import', Status::Ok);

        $row = ExchangeLog::query()->sole();
        $this->assertSame('out', $row->direction);
        $this->assertSame(1, $row->attempt);
        $this->assertNull($row->subject);
        $this->assertNull($row->message);
        $this->assertNull($row->duration_ms);
    }

    public function test_long_texts_are_cut_and_short_ones_kept(): void
    {
        app(Exchange::class)->record('1c', 'x', Status::Retry, subject: str_repeat('s', 300), message: str_repeat('m', 2000), error: str_repeat('e', 2001));

        $row = ExchangeLog::query()->sole();
        $this->assertSame(str_repeat('s', 250).'…', $row->subject);
        $this->assertSame(str_repeat('m', 2000), $row->message, 'exactly the limit stays whole');
        $this->assertSame(str_repeat('e', 2000).'…', $row->error);
    }

    public function test_secrets_never_reach_the_log(): void
    {
        app(Exchange::class)->record(
            'bitrix24', 'lead.add', Status::Retry,
            subject: 'To https://b24.example.test/rest/1/KEY123/', message: 'KEY123 used', error: 'cURL error 6 for https://b24.example.test/rest/1/KEY123/crm.lead.add',
            secrets: ['KEY123', '', 'other'],
        );

        $row = ExchangeLog::query()->sole();
        $this->assertSame('To https://b24.example.test/rest/1/***/', $row->subject);
        $this->assertSame('*** used', $row->message);
        $this->assertSame('cURL error 6 for https://b24.example.test/rest/1/***/crm.lead.add', $row->error);
    }

    public function test_an_empty_secret_does_not_mangle_the_text(): void
    {
        app(Exchange::class)->record('1c', 'x', Status::Retry, error: 'plain text', secrets: ['']);

        $this->assertSame('plain text', ExchangeLog::query()->sole()->error);
    }

    public function test_only_a_final_failure_tells_the_administrators(): void
    {
        $exchange = app(Exchange::class);

        $exchange->record('1c', 'x', Status::Ok);
        $exchange->record('1c', 'x', Status::Retry, error: 'again');
        Mail::assertNothingOutgoing();

        $exchange->record('1c', 'x', Status::Failed, error: 'gave up');
        Mail::assertSent(FailureMail::class, 1);
    }
}
