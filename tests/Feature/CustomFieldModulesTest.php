<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use Atrium\Admin\Models\Language;
use Atrium\Admin\Models\Site;
use Atrium\Admin\Modules\Soft;
use Atrium\Admin\Resources\ResourceRegistry;
use Atrium\Brands\Models\Brand;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Atrium\CustomFields\Models\CustomField;
use Atrium\CustomFields\Support\ModuleList;
use Atrium\Faq\Models\Category;
use Atrium\Faq\Models\Question;
use Atrium\Forms\Models\Form;
use Atrium\Forms\Models\FormSubmission;
use Atrium\Menus\Models\Menu;
use Atrium\Monitoring\Models\Incident;
use Atrium\Monitoring\Models\UptimeCheck;
use Atrium\News\Models\News;
use Atrium\Pages\Models\Page;
use Atrium\Reviews\Models\Review;
use Atrium\Seo\Models\Issue;
use Atrium\Seo\Models\NotFound;
use Atrium\Seo\Models\PageMeta;
use Atrium\Seo\Models\Redirect;
use Atrium\Seo\Models\RedirectImport;
use Atrium\Seo\Models\Template;
use Atrium\Stores\Models\Store;
use Atrium\Team\Models\Department;
use Atrium\Team\Models\Member;
use Tests\TestCase;

/**
 * The "Module" select of a custom field offers what editors write, not what
 * the panel keeps for itself, whatever packages are installed.
 */
final class CustomFieldModulesTest extends TestCase
{
    /** @var list<class-string> */
    private const CONTENT = [
        Site::class, Product::class, CatalogSection::class, Brand::class, News::class, Store::class,
        Category::class, Question::class, Form::class, Page::class, Review::class, Menu::class,
        Member::class, Department::class, Article::class,
    ];

    /** @var list<class-string> */
    private const SERVICE = [
        Language::class, CustomField::class, Issue::class, NotFound::class, RedirectImport::class, Redirect::class,
        PageMeta::class, Template::class, Incident::class, UptimeCheck::class, FormSubmission::class,
    ];

    public function test_the_select_lists_the_content_modules(): void
    {
        $offered = array_keys(app(ModuleList::class)->options());

        foreach (self::CONTENT as $model) {
            $this->assertContains($model, $offered, $model);
        }
    }

    public function test_the_select_does_not_list_the_service_modules(): void
    {
        $offered = array_keys(app(ModuleList::class)->options());

        foreach (self::SERVICE as $model) {
            $this->assertNotContains($model, $offered, $model);
        }
    }

    public function test_every_module_of_the_panel_is_either_content_or_service(): void
    {
        $offered = array_keys(app(ModuleList::class)->models());
        $all = array_values(app(ResourceRegistry::class)->all());

        $this->assertEqualsCanonicalizing($all, [...$offered, ...self::SERVICE], 'a new module has to be put in one of the two lists above');
    }

    public function test_a_service_module_is_no_target_of_a_link_or_a_review_either(): void
    {
        $targets = array_keys(Soft::targets());

        $this->assertContains(Page::class, $targets);
        $this->assertContains(Product::class, $targets);
        $this->assertNotContains(Site::class, $targets);

        foreach (self::SERVICE as $model) {
            $this->assertNotContains($model, $targets, $model);
        }
    }

    public function test_the_select_is_named_in_the_language_of_the_panel(): void
    {
        $this->assertSame('Articles', app(ModuleList::class)->options()[Article::class]);

        app()->setLocale('ru');

        $this->assertSame('Статьи', app(ModuleList::class)->options()[Article::class]);
        $this->assertSame('Разделы FAQ', app(ModuleList::class)->options()[Category::class]);
        $this->assertSame('Вопросы FAQ', app(ModuleList::class)->options()[Question::class]);
        $this->assertCount(count(app(ModuleList::class)->options()), array_unique(app(ModuleList::class)->options()), 'no two modules share a name');
    }
}
