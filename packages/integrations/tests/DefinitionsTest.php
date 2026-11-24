<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Tests;

use Atrium\Admin\Columns\Column;
use Atrium\Admin\Fields\Field;
use Atrium\Integrations\IntegrationsServiceProvider;
use Atrium\Integrations\Models\ExchangeLog;
use Illuminate\Support\ServiceProvider;
use ReflectionMethod;

/**
 * What the package hands to the application and to the admin: its config,
 * what it offers to publish, and how a log line is drawn.
 */
final class DefinitionsTest extends TestCase
{
    public function test_the_config_ships_with_the_package_and_is_ready_without_publishing(): void
    {
        $this->assertSame([10, 60, 300, 900], config('atrium-integrations.backoff'));
        $this->assertSame(60, config('atrium-integrations.alert_interval'));
        $this->assertSame(90, config('atrium-integrations.retention_days'));
        $this->assertTrue(config('atrium-integrations.schedule'));
    }

    public function test_the_config_can_be_published_to_the_application(): void
    {
        $paths = ServiceProvider::pathsToPublish(IntegrationsServiceProvider::class, 'atrium-integrations-config');

        $this->assertSame([config_path('atrium-integrations.php')], array_values($paths));
        $source = array_key_first($paths);
        $this->assertFileExists($source);
        $this->assertSame('atrium-integrations.php', basename($source));
        $this->assertStringEndsWith('/packages/integrations/src/../config/atrium-integrations.php', $source);
    }

    public function test_a_number_comes_back_a_number_whatever_the_database_says(): void
    {
        $line = new ExchangeLog(['attempt' => '3', 'duration_ms' => '250']);

        $this->assertSame(3, $line->attempt);
        $this->assertSame(250, $line->duration_ms);
    }

    public function test_the_admin_reads_and_deletes_but_never_writes(): void
    {
        $this->assertFalse(ExchangeLog::isCreatableInAdmin());
        $this->assertFalse(ExchangeLog::isEditableInAdmin());
        // The model forwards a call it cannot make from outside, so ask how the method is declared.
        $this->assertTrue((new ReflectionMethod(ExchangeLog::class, 'isCreatableInAdmin'))->isPublic());
        $this->assertTrue((new ReflectionMethod(ExchangeLog::class, 'isEditableInAdmin'))->isPublic());
    }

    public function test_a_line_opens_with_these_fields_all_read_only(): void
    {
        $fields = array_map(static fn (Field $field): array => $field->toArray(), ExchangeLog::getAdministrationFields());

        $this->assertSame(
            ['created_at', 'integration', 'direction_label', 'action', 'subject', 'status_label', 'message', 'error', 'attempt', 'duration_ms'],
            array_column($fields, 'name'),
        );
        $this->assertSame([true], array_values(array_unique(array_column($fields, 'readonly'))));
        $rows = array_column($fields, 'rows', 'name');
        $this->assertSame(6, $rows['message']);
        $this->assertSame(6, $rows['error']);
    }

    public function test_the_list_has_these_columns(): void
    {
        $columns = array_map(static fn (Column $column): array => $column->toArray(), ExchangeLog::getAdministrationListColumns());

        $this->assertSame(['id', 'created_at', 'integration', 'action', 'subject', 'status_label', 'error'], array_column($columns, 'name'));
        $this->assertSame(80, array_column($columns, 'limit', 'name')['error']);
        $this->assertTrue(array_column($columns, 'sortable', 'name')['id']);
    }

    public function test_the_result_filter_offers_every_result_with_its_word(): void
    {
        $filter = ExchangeLog::getAdministrationFilters()[0]->toArray();

        $this->assertSame('status', $filter['name']);
        $this->assertSame(
            [['value' => 'ok', 'label' => 'Done'], ['value' => 'retry', 'label' => 'Failed, will retry'], ['value' => 'failed', 'label' => 'Failed']],
            $filter['options'],
        );
    }
}
