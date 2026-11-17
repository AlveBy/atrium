<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Tests\Feature;

use Atrium\Jobs\Models\Application;
use Atrium\Jobs\Models\Vacancy;
use Atrium\Jobs\Tests\TestCase;
use Illuminate\Testing\TestResponse;

/**
 * An AI agent or another program works with the vacancies through the
 * panel's API and MCP server, with a token and only what its owner may do.
 */
final class ApiTest extends TestCase
{
    public function test_an_agent_publishes_a_vacancy_with_the_right_to(): void
    {
        $token = $this->token(['vacancies.view', 'vacancies.create'], ['vacancies:read', 'vacancies:write']);

        $this->withToken($token)->postJson('/api/v1/vacancies', [
            'name' => ['en' => 'Data engineer'],
            'code' => ['en' => 'data-engineer'],
            'active' => true,
            'work_mode' => 'remote',
            'employment_type' => 'full_time',
        ])->assertCreated();

        $this->assertSame('Data engineer', Vacancy::query()->sole()->getTranslation('name', 'en'));
        $this->get('/jobs')->assertSee('Data engineer');
    }

    public function test_a_token_without_the_right_cannot_publish_or_read_applications(): void
    {
        $this->stored();
        $token = $this->token(['vacancies.view'], ['vacancies:read', 'vacancies:write', 'job_applications:read']);

        $this->withToken($token)->postJson('/api/v1/vacancies', ['name' => ['en' => 'Nope'], 'code' => ['en' => 'nope']])->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/job_applications')->assertForbidden();
        $this->assertSame(0, Vacancy::query()->count());
    }

    public function test_applications_are_read_only_with_the_right_and_cannot_be_created(): void
    {
        $application = $this->stored();
        $token = $this->token(['job_applications.view', 'job_applications.create', 'job_applications.update'], ['job_applications:read', 'job_applications:write']);

        $this->withToken($token)->getJson('/api/v1/job_applications')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Ann')
            ->assertJsonPath('data.0.status_label', 'New');

        $this->withToken($token)->getJson("/api/v1/job_applications/{$application->id}")->assertOk()->assertJsonPath('data.email', 'ann@example.com');

        $this->withToken($token)->postJson('/api/v1/job_applications', ['name' => 'Fake', 'email' => 'fake@example.com'])->assertForbidden();

        $this->withToken($token)->patchJson("/api/v1/job_applications/{$application->id}", ['status' => 'interview'])->assertOk();
        $this->assertSame('interview', $application->fresh()?->status->value);
    }

    public function test_the_mcp_server_lists_the_modules_the_token_allows(): void
    {
        $token = $this->token(['vacancies.view', 'vacancies.create', 'job_applications.view'], ['vacancies:read', 'vacancies:write', 'job_applications:read']);

        $result = $this->mcp($token, 'tools/call', ['name' => 'list_modules', 'arguments' => []])->json('result');
        $this->assertFalse($result['isError']);
        $modules = collect(json_decode($result['content'][0]['text'], true));

        $this->assertEqualsCanonicalizing(['vacancies', 'job_applications'], $modules->pluck('module')->all());
        $this->assertSame(['view'], $modules->firstWhere('module', 'job_applications')['allowed']);
    }

    /**
     * @param  array<int,string>  $permissions
     * @param  array<int,string>  $abilities
     */
    private function token(array $permissions, array $abilities): string
    {
        return $this->editor($permissions)->createToken('agent', $abilities)->plainTextToken;
    }

    /**
     * @param  array<string,mixed>  $params
     */
    private function mcp(string $token, string $method, array $params = []): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params])->assertOk();
    }

    private function stored(): Application
    {
        $application = new Application;
        $application->forceFill(['name' => 'Ann', 'email' => 'ann@example.com', 'consent_at' => now()])->save();

        return $application;
    }
}
