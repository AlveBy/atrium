<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24\Tests;

use Atrium\Admin\Facades\Setting;

/**
 * The chat widget code of the settings, at the end of the pages of the site.
 */
final class WidgetTest extends TestCase
{
    private const CODE = '<script>window.b24 = "chat";</script>';

    protected function setUp(): void
    {
        parent::setUp();

        Setting::save('bitrix24', ['widget_code' => self::CODE]);
    }

    public function test_the_code_goes_just_before_the_closing_body_tag(): void
    {
        $this->get('/page')->assertOk()->assertSee('<p>Hello</p>'.self::CODE."\n</body></html>", false);
    }

    public function test_the_code_is_trimmed_before_it_goes_in(): void
    {
        Setting::save('bitrix24', ['widget_code' => "\n  ".self::CODE."  \n"]);

        $this->get('/page')->assertSee('<p>Hello</p>'.self::CODE."\n</body>", false);
    }

    public function test_an_empty_code_changes_nothing(): void
    {
        Setting::save('bitrix24', ['widget_code' => "  \n "]);

        $this->get('/page')->assertOk()->assertSee('<p>Hello</p></body></html>', false);
    }

    public function test_no_code_at_all_changes_nothing(): void
    {
        Setting::save('bitrix24', ['widget_code' => null]);

        $this->get('/page')->assertDontSee('b24', false);
    }

    public function test_the_last_closing_body_tag_is_the_one_used(): void
    {
        $this->app['router']->get('/nested', fn () => response('<html><body><pre>&lt;/body&gt; </body>text</pre></body></html>', 200, ['Content-Type' => 'text/html']))->middleware('web');

        $content = (string) $this->get('/nested')->getContent();

        $this->assertSame(1, substr_count($content, self::CODE));
        $this->assertStringEndsWith(self::CODE."\n</body></html>", $content);
    }

    public function test_the_body_tag_in_capitals_is_found_too(): void
    {
        $this->app['router']->get('/loud', fn () => response('<HTML><BODY>x</BODY></HTML>', 200, ['Content-Type' => 'text/html']))->middleware('web');

        $this->get('/loud')->assertSee('x'.self::CODE."\n</BODY>", false);
    }

    public function test_a_page_without_a_body_is_left_alone(): void
    {
        $this->get('/fragment')->assertOk()->assertDontSee('b24', false)->assertSee('<p>no body tag</p>', false);
    }

    public function test_only_html_that_is_a_page_gets_it(): void
    {
        $this->get('/data')->assertOk()->assertExactJson(['ok' => true]);
        $this->get('/moved')->assertRedirect('/page')->assertDontSee('b24', false);
        $this->get('/missing')->assertNotFound()->assertDontSee('b24', false);
        $this->get('/file')->assertDontSee('b24', false);
        $this->get('/page', ['X-Inertia' => 'true'])->assertDontSee('b24', false);
    }

    public function test_the_admin_never_gets_it(): void
    {
        $this->get('/admin/login')->assertDontSee('b24', false);
        $this->actingAs($this->admin(), 'admin')->get('/admin/dashboard')->assertDontSee('window.b24', false);
    }

    public function test_a_site_whose_admin_lives_elsewhere_keeps_it_out_of_that_prefix(): void
    {
        config(['atrium.prefix' => 'cp']);
        $this->app['router']->get('/cp/screen', fn () => response('<html><body>x</body></html>', 200, ['Content-Type' => 'text/html']))->middleware('web');
        $this->app['router']->get('/cpx', fn () => response('<html><body>y</body></html>', 200, ['Content-Type' => 'text/html']))->middleware('web');

        $this->get('/cp/screen')->assertDontSee('b24', false);
        $this->get('/cpx')->assertSee('y'.self::CODE, false);
    }

    public function test_the_prefix_of_the_admin_is_compared_without_its_slashes(): void
    {
        config(['atrium.prefix' => '/cp/']);
        $this->app['router']->get('/cp/screen', fn () => response('<html><body>x</body></html>', 200, ['Content-Type' => 'text/html']))->middleware('web');

        $this->get('/cp/screen')->assertDontSee('b24', false);
    }

    public function test_a_status_of_three_hundred_is_not_a_page(): void
    {
        $this->app['router']->get('/multiple', fn () => response('<html><body>pick one</body></html>', 300, ['Content-Type' => 'text/html']))->middleware('web');

        $this->get('/multiple')->assertDontSee('b24', false);
    }

    public function test_when_the_module_is_not_installed_nothing_is_added(): void
    {
        config(['atrium.modules' => ['alveby/atrium-forms']]);

        $this->get('/page')->assertDontSee('b24', false);
    }
}
