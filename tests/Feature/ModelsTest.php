<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Atrium\Admin\Fields\Field;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The two models of the app: the demo article and the user.
 */
final class ModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_article_is_read_back_with_its_flag_and_order_as_such(): void
    {
        $article = Article::query()->create(['name' => ['en' => 'Hello'], 'code' => ['en' => 'hello'], 'active' => 0, 'sort' => '7']);

        $this->assertFalse($article->fresh()?->active);
        $this->assertSame(7, $article->fresh()?->sort);
        $this->assertTrue(Article::query()->create(['name' => ['en' => 'On'], 'code' => ['en' => 'on'], 'active' => 1])->fresh()?->active);
    }

    public function test_the_article_form_and_list_of_the_panel(): void
    {
        $fields = Article::getAdministrationFields();
        $byName = array_combine(array_map(fn (Field $field): string => $field->getName(), $fields), $fields);

        $this->assertSame('Articles', Article::getAdminLabel());
        $this->assertSame(['name', 'code', 'active', 'sort', 'preview_text', 'detail_text'], array_keys($byName));
        $this->assertSame('Name', $byName['name']->toArray()['label']);
        $this->assertContains('required', $byName['name']->getValidationRules());
        $this->assertTrue($byName['active']->getDefault());
        $this->assertSame(500, $byName['sort']->getDefault());
        $this->assertSame(
            ['id', 'name', 'active', 'updated_at'],
            array_map(fn ($column): string => $column->getName(), Article::getAdministrationListColumns()),
        );
    }

    /**
     * @return array<string,array{string,string,string}>
     */
    public static function languages(): array
    {
        return [
            'English' => ['en', 'Articles', 'Name'],
            'German' => ['de', 'Artikel', 'Name'],
            'Russian' => ['ru', 'Статьи', 'Название'],
            'Ukrainian' => ['uk', 'Статті', 'Назва'],
        ];
    }

    #[DataProvider('languages')]
    public function test_the_article_speaks_the_language_of_the_page(string $locale, string $label, string $name): void
    {
        app()->setLocale($locale);

        $this->assertSame($label, Article::getAdminLabel());
        $this->assertSame($name, Article::getAdministrationFields()[0]->toArray()['label']);
        $this->assertSame($name, Article::getAdministrationListColumns()[1]->toArray()['label']);
    }

    public function test_every_language_of_the_panel_has_its_word_for_articles(): void
    {
        foreach (array_keys((array) config('atrium.locales')) as $locale) {
            $lines = require base_path("lang/{$locale}/articles.php");

            $this->assertNotSame('', $lines['module'], $locale);
            $this->assertSame(['module'], array_keys($lines), $locale);
        }
    }

    public function test_two_articles_cannot_share_a_code(): void
    {
        $field = collect(Article::getAdministrationFields())->first(fn (Field $field): bool => $field->getName() === 'code');

        $this->assertNotNull($field);
        $this->assertContains('unique:articles,code', $field->getValidationRules());
    }

    public function test_a_user_password_is_hashed_and_hidden(): void
    {
        $user = User::query()->create(['name' => 'Ann', 'email' => 'ann@example.com', 'password' => 'secret-password']);
        $user->forceFill(['email_verified_at' => '2026-01-02 03:04:05'])->save();

        $this->assertNotSame('secret-password', $user->getAttributes()['password']);
        $this->assertTrue(Hash::check('secret-password', (string) $user->fresh()?->password));
        $this->assertInstanceOf(Carbon::class, $user->fresh()?->email_verified_at);
        $this->assertSame('2026-01-02 03:04:05', $user->fresh()?->email_verified_at?->format('Y-m-d H:i:s'));
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }
}
