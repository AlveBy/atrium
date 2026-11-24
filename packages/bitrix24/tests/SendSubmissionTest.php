<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24\Tests;

use Atrium\Admin\Facades\Setting;
use Atrium\Bitrix24\Jobs\SendSubmission;
use Atrium\Bitrix24\Models\FormLink;
use Atrium\Forms\Models\FormSubmission;
use Atrium\Integrations\Exchange;
use Atrium\Integrations\Models\ExchangeLog;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

final class SendSubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->connect();
        Mail::fake();
        // The listener would run the job on the sync queue the moment a submission is stored.
        Queue::fake();
    }

    private function send(int $submissionId): void
    {
        (new SendSubmission($submissionId))->handle(app(Exchange::class));
    }

    private function url(string $method): string
    {
        return 'https://portal.example.test/rest/1/abc123def456/'.$method.'.json';
    }

    public function test_a_submission_becomes_a_lead_and_the_log_says_which(): void
    {
        Http::fake([$this->url('crm.lead.add') => Http::response(['result' => 77])]);
        $submission = $this->submit();

        $this->send($submission->id);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === $this->url('crm.lead.add')
            && $request['fields']['NAME'] === 'Ann'
            && $request['fields']['TITLE'] === 'Website: Contact us'
            && $request['params'] === ['REGISTER_SONET_EVENT' => 'N']);
        $row = ExchangeLog::query()->sole();
        $this->assertSame(['bitrix24', 'lead.add', 'ok', 'Form submission #'.$submission->id, 'Lead #77 created.'], [$row->integration, $row->action, $row->status, $row->subject, $row->message]);
    }

    public function test_a_form_linked_as_a_deal_makes_a_contact_and_a_deal_that_points_to_it(): void
    {
        Http::fake([
            $this->url('crm.contact.add') => Http::response(['result' => 31]),
            $this->url('crm.deal.add') => Http::response(['result' => 52]),
        ]);
        $form = $this->form();
        FormLink::query()->create(['form_id' => $form->id, 'mode' => 'deal', 'active' => true, 'mapping' => 'message = OPPORTUNITY']);
        $submission = $this->submit($form);

        $this->send($submission->id);

        Http::assertSentCount(2);
        Http::assertSentInOrder([
            fn (Request $request): bool => $request->url() === $this->url('crm.contact.add')
                && $request['fields']['NAME'] === 'Ann'
                && ! isset($request['fields']['TITLE'])
                && $request['params'] === ['REGISTER_SONET_EVENT' => 'N'],
            fn (Request $request): bool => $request->url() === $this->url('crm.deal.add')
                && $request['fields']['CONTACT_ID'] === 31
                && $request['fields']['TITLE'] === 'Website: Contact us'
                && $request['fields']['OPPORTUNITY'] === 'Where is my parcel?'
                && ! isset($request['fields']['NAME'])
                && $request['params'] === ['REGISTER_SONET_EVENT' => 'N'],
        ]);
        $row = ExchangeLog::query()->sole();
        $this->assertSame(['deal.add', 'ok', 'Deal #52 created, contact #31.'], [$row->action, $row->status, $row->message]);
    }

    public function test_a_retry_after_the_deal_failed_reuses_the_contact(): void
    {
        $form = $this->form();
        FormLink::query()->create(['form_id' => $form->id, 'mode' => 'deal', 'active' => true]);
        $submission = $this->submit($form);
        Http::fake([
            $this->url('crm.contact.add') => Http::response(['result' => 31]),
            $this->url('crm.deal.add') => Http::sequence()->push(['error' => 'ERROR_CORE', 'error_description' => 'Down'], 500)->push(['result' => 52]),
        ]);

        try {
            $this->send($submission->id);
            $this->fail('The first attempt must fail.');
        } catch (\Throwable) {
        }
        $this->send($submission->id);

        Http::assertSentCount(3);
        $contacts = Http::recorded(fn (Request $request): bool => $request->url() === $this->url('crm.contact.add'));
        $this->assertCount(1, $contacts, 'the contact is made once');
        $this->assertSame('ok', ExchangeLog::query()->latest('id')->first()?->status);
    }

    public function test_the_contact_of_one_submission_is_not_the_contact_of_another(): void
    {
        $form = $this->form();
        FormLink::query()->create(['form_id' => $form->id, 'mode' => 'deal', 'active' => true]);
        Http::fake([
            $this->url('crm.contact.add') => Http::sequence()->push(['result' => 31])->push(['result' => 32]),
            $this->url('crm.deal.add') => Http::response(['result' => 52]),
        ]);

        $this->send($this->submit($form)->id);
        $this->send($this->submit($form)->id);

        Http::assertSent(fn (Request $request): bool => $request->url() === $this->url('crm.deal.add') && $request['fields']['CONTACT_ID'] === 32);
        $this->assertNotNull(Cache::get('atrium-bitrix24:contact:'.$this->lastSubmissionId()));
    }

    private function lastSubmissionId(): int
    {
        return (int) FormSubmission::withoutGlobalScopes()->max('id');
    }

    public function test_a_submission_that_was_deleted_meanwhile_is_not_an_error(): void
    {
        Http::fake();

        $this->send(999);

        Http::assertNothingSent();
        $row = ExchangeLog::query()->sole();
        $this->assertSame(['ok', 'lead.add', 'Form submission #999', 'The submission was deleted before it was sent.'], [$row->status, $row->action, $row->subject, $row->message]);
    }

    public function test_a_refusal_fails_the_attempt_and_the_webhook_never_reaches_the_log(): void
    {
        Http::fake(['*' => Http::response(['error' => 'INVALID_CREDENTIALS', 'error_description' => 'Bad call to '.self::WEBHOOK], 401)]);
        $submission = $this->submit();

        try {
            $this->send($submission->id);
            $this->fail('A refusal must throw.');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('INVALID_CREDENTIALS', $e->getMessage());
        }

        // Not on a queue worker: no attempt is known to come, so the line waits for failed().
        $this->assertSame(0, ExchangeLog::query()->count());

        (new SendSubmission($submission->id))->failed($e);
        $row = ExchangeLog::query()->sole();
        $this->assertSame('failed', $row->status);
        $this->assertStringContainsString('INVALID_CREDENTIALS', (string) $row->error);
        $this->assertStringNotContainsString('abc123def456', (string) $row->error);
        $this->assertStringNotContainsString('portal.example.test', (string) $row->error);
    }

    public function test_the_submission_of_another_site_is_found_in_the_worker(): void
    {
        Http::fake(['*' => Http::response(['result' => 1])]);
        $submission = $this->submit();
        FormSubmission::withoutGlobalScopes()->whereKey($submission->id)->toBase()->update(['site_id' => 4242]);

        $this->send($submission->id);

        Http::assertSentCount(1);
        $this->assertSame('ok', ExchangeLog::query()->sole()->status);
    }

    public function test_a_submission_of_a_deleted_form_goes_as_a_lead(): void
    {
        Http::fake(['*' => Http::response(['result' => 3])]);
        $submission = $this->submit();
        FormSubmission::withoutGlobalScopes()->whereKey($submission->id)->toBase()->update(['form_id' => null]);

        $this->send($submission->id);

        Http::assertSent(fn (Request $request): bool => $request->url() === $this->url('crm.lead.add'));
    }

    public function test_the_webhook_is_what_the_log_must_hide_and_without_one_there_is_nothing_to_hide(): void
    {
        $secrets = new \ReflectionMethod(SendSubmission::class, 'secrets');

        $this->assertSame([self::WEBHOOK], $secrets->invoke(new SendSubmission(1)));

        Setting::save('bitrix24', ['webhook' => '']);
        $this->assertSame([''], $secrets->invoke(new SendSubmission(1)));
    }

    public function test_the_job_is_tried_five_times_on_the_queue_and_is_named_after_bitrix24(): void
    {
        $job = new SendSubmission(1);

        $this->assertSame(5, $job->tries);
        $this->assertSame([10, 60, 300, 900], $job->backoff());
    }
}
