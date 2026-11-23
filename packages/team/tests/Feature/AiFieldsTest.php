<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Team\Tests\Feature;

use Atrium\Admin\Fields\Field;
use Atrium\Team\Models\Member;
use Atrium\Team\Tests\TestCase;

/**
 * Which texts of the module the AI helps with: the ones the editors write for
 * the site, and not the ones visitors write or the ones that are code.
 */
final class AiFieldsTest extends TestCase
{
    public function test_the_biography_of_a_member_can_be_written_by_the_ai(): void
    {
        $this->assertSame(['task' => 'describe', 'from' => []], $this->field(Member::class, 'detail_text')->toArray()['ai'] ?? null);
    }

    public function test_the_summary_of_a_member_is_written_from_the_biography(): void
    {
        $this->assertSame(['task' => 'preview', 'from' => ['detail_text']], $this->field(Member::class, 'preview_text')->toArray()['ai'] ?? null);
    }

    public function test_the_position_and_the_email_of_a_member_are_left_alone(): void
    {
        foreach (['position', 'email'] as $name) {
            $this->assertNull($this->field(Member::class, $name)->toArray()['ai'] ?? null, $name);
        }
    }

    /**
     * @param  class-string  $model
     */
    private function field(string $model, string $name): Field
    {
        $fields = method_exists($model, 'getAdminFieldsWithTabs') ? $model::getAdminFieldsWithTabs() : $model::getAdministrationFields();

        return collect($fields)->first(fn (Field $field): bool => $field->getName() === $name) ?? $this->fail("No field {$name} in {$model}.");
    }
}
