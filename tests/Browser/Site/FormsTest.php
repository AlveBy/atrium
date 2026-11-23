<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Site;

use Atrium\Forms\Models\Form;
use Atrium\Forms\Models\FormField;
use Atrium\Forms\Models\FormSubmission;
use Facebook\WebDriver\WebDriverKeys;
use Illuminate\Support\Facades\Blade;
use Laravel\Dusk\Browser;
use Tests\Browser\Admin\AdminTestCase;

/**
 * A visitor sends a form without the page reloading: on the form's own page
 * and in a window opened by a button. Errors come to the fields, a good
 * submission is stored and mailed once.
 */
final class FormsTest extends AdminTestCase
{
    /** A page of someone's site with the form put on it in one line. */
    private const PAGE = 'dusk-forms-window.html';

    protected function setUp(): void
    {
        parent::setUp();

        // The served app counts the submissions per minute in this cache.
        cache()->flush();
    }

    protected function tearDown(): void
    {
        @unlink(public_path(self::PAGE));

        parent::tearDown();
    }

    public function test_a_visitor_sends_a_form_on_its_page_without_reloading_it(): void
    {
        $this->contactForm();
        $log = storage_path('logs/laravel.log');
        file_put_contents($log, '');

        $this->withEnv(['ATRIUM_FORMS_MIN_SECONDS' => '1', 'MAIL_MAILER' => 'log', 'LOG_CHANNEL' => 'single'], function (): void {
            $this->browse(function (Browser $browser): void {
                $browser->visit('/forms/contact')
                    ->waitFor('form[data-atrium-form]')
                    ->assertSeeIn('h1', 'Contact us');
                // Gone if the page reloads.
                $browser->script('window.stillHere = true;');

                // Nothing filled in: the server's errors, each at its field.
                $browser->pause(1100)->press('Send')
                    ->waitFor('#form-contact-name-error')
                    ->assertSeeIn('#form-contact-name-error', 'The your name field is required.')
                    ->assertSeeIn('#form-contact-email-error', 'The e-mail field is required.')
                    ->assertAttribute('#form-contact-name', 'aria-invalid', 'true')
                    ->assertAttribute('#form-contact-name', 'aria-describedby', 'form-contact-name-error')
                    ->assertAttributeMissing('#form-contact-message', 'aria-invalid');
                $this->assertSame('fields[name]', $browser->script('return document.activeElement.name;')[0]);
                $this->assertTrue($browser->script('return window.stillHere === true;')[0]);
                $this->assertSame(0, FormSubmission::query()->count());

                // The errors read at 4.5:1 or more, in the light theme and the dark one.
                foreach (['light', 'dark'] as $theme) {
                    $browser->script("document.documentElement.dataset.theme = '{$theme}';");
                    $ratio = $this->contrast($browser, '#form-contact-name-error');
                    $this->assertGreaterThanOrEqual(4.5, $ratio, "an error in the {$theme} theme reads at {$ratio}:1");
                    $browser->screenshot("forms-ajax-errors-{$theme}");
                }

                // Filled in, and sent twice at once: one submission.
                $browser->type('#form-contact-name', 'Ann')
                    ->type('#form-contact-email', 'ann@example.com')
                    ->pause(1100);
                $browser->script("const form = document.getElementById('form-contact'); form.requestSubmit(); form.requestSubmit();");
                $browser->waitForTextIn('[data-atrium-sent]', 'Thank you! Your message has been sent.')
                    ->assertMissing('[data-atrium-error]')
                    ->assertInputValue('#form-contact-name', '')
                    ->assertAttributeMissing('#form-contact-name', 'aria-invalid')
                    ->assertAttributeMissing('#form-contact-name', 'aria-describedby')
                    ->assertEnabled('button[type="submit"]');
                $this->assertTrue($browser->script('return window.stillHere === true;')[0]);
                $this->assertTrue($browser->script('return document.activeElement.matches("[data-atrium-sent]");')[0]);
                $browser->screenshot('forms-ajax-sent');
            });
        });

        $submission = FormSubmission::query()->sole();
        $this->assertSame(['Ann', 'ann@example.com', null], array_column($submission->data, 'value'));
        $mail = (string) file_get_contents($log);
        $this->assertStringContainsString('Subject: New submission: Contact us', $mail);
        $this->assertStringContainsString('To: sales@example.com', $mail);
        $this->assertStringContainsString('ann@example.com', $mail);
    }

    public function test_a_refused_submission_brings_a_new_captcha(): void
    {
        $this->contactForm();

        $this->withEnv(['ATRIUM_FORMS_MIN_SECONDS' => '0', 'ATRIUM_CAPTCHA' => 'image'], function (): void {
            $this->browse(function (Browser $browser): void {
                $browser->visit('/forms/contact')->waitFor('.atrium-captcha input[name="captcha"]');
                $first = $browser->value('[name="captcha_token"]');

                $browser->type('#form-contact-name', 'Ann')
                    ->type('#form-contact-email', 'ann@example.com')
                    ->type('.atrium-captcha input[name="captcha"]', '-1')
                    ->press('Send')
                    ->waitFor('#form-contact-captcha-error')
                    ->assertSeeIn('[data-error-for="captcha"]', 'The answer to the check is wrong. Try the new one.');
                $second = $browser->value('[name="captcha_token"]');
                $this->assertNotSame($first, $second, 'a used question is not asked again');
                $this->assertSame('captcha', $browser->script('return document.activeElement.name;')[0]);

                $browser->type('.atrium-captcha input[name="captcha"]', (string) decrypt($second)['a'])
                    ->press('Send')
                    ->waitForTextIn('[data-atrium-sent]', 'Thank you!')
                    ->assertMissing('#form-contact-captcha-error');
            });
        });

        $this->assertSame(1, FormSubmission::query()->count());
    }

    public function test_a_visitor_opens_the_form_in_a_window_and_sends_it_there(): void
    {
        $this->contactForm();
        // Drawn here and saved as a plain page: the form must work on any
        // page of a site, even one cached with an old CSRF token.
        file_put_contents(public_path(self::PAGE), Blade::render(
            '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Our office</title></head><body>'
            ."<main><h1>Our office</h1>@atriumForm('contact', ['modal' => true, 'trigger' => 'Write to us'])<p>Open every day.</p></main></body></html>"
        ));

        $this->withEnv(['ATRIUM_FORMS_MIN_SECONDS' => '0'], function (): void {
            $this->browse(function (Browser $browser): void {
                // Any page of the site gives the browser its CSRF cookie.
                $browser->visit('/')->visit('/'.self::PAGE)
                    ->assertMissing('dialog[open]')
                    ->assertAttribute('[data-atrium-form-open]', 'aria-expanded', 'false')
                    ->clickLink('Write to us')
                    ->waitFor('dialog[open]')
                    ->assertPathIs('/'.self::PAGE)
                    ->assertSeeIn('dialog h2', 'Contact us')
                    ->assertAttribute('[data-atrium-form-open]', 'aria-expanded', 'true')
                    ->assertAttribute('dialog', 'aria-labelledby', 'form-contact-title');
                $this->assertSame('fields[name]', $this->focused($browser));
                $browser->screenshot('forms-window-open');

                // The keyboard goes round the window and never leaves it.
                $this->tab($browser, back: true);
                $this->assertSame('close', $this->focused($browser));
                $this->tab($browser);
                $this->assertSame('fields[name]', $this->focused($browser));
                $this->tab($browser, back: true);
                $this->tab($browser, back: true);
                $this->assertSame('submit', $this->focused($browser));
                $this->tab($browser);
                $this->assertSame('close', $this->focused($browser));

                // Esc closes it and puts the focus back on the button.
                $browser->driver->getKeyboard()->sendKeys(WebDriverKeys::ESCAPE);
                $browser
                    ->waitUntilMissing('dialog[open]')
                    ->assertAttribute('[data-atrium-form-open]', 'aria-expanded', 'false');
                $this->assertSame('opener', $this->focused($browser));

                // Sent inside the window: errors there, then the thanks; it stays open.
                $browser->clickLink('Write to us')
                    ->waitFor('dialog[open]')
                    ->press('Send')
                    ->waitFor('dialog #form-contact-email-error')
                    ->assertVisible('dialog[open]')
                    ->type('#form-contact-name', 'Bob')
                    ->type('#form-contact-email', 'bob@example.com')
                    ->press('Send')
                    ->waitForTextIn('dialog [data-atrium-sent]', 'Thank you! Your message has been sent.')
                    ->assertVisible('dialog[open]')
                    ->screenshot('forms-window-sent');

                // The close button closes it too.
                $browser->click('[data-atrium-form-close]')->waitUntilMissing('dialog[open]');
                $this->assertSame('opener', $this->focused($browser));
            });
        });

        $this->assertSame('Bob', FormSubmission::query()->sole()->data[0]['value']);
    }

    public function test_without_the_script_the_button_leads_to_the_form_page(): void
    {
        $this->contactForm();

        $this->browse(function (Browser $browser): void {
            $browser->visit('/forms/contact')->waitFor('form');
            $href = $browser->script(
                "const wrap = document.createElement('div');
                 wrap.innerHTML = '<a data-atrium-form-open=\"none\" href=\"/forms/contact?from=button\">Go</a>';
                 document.body.append(wrap);
                 return wrap.firstChild.href;"
            )[0];

            // A button whose window is not on the page is an ordinary link.
            $browser->clickLink('Go')->waitForLocation('/forms/contact')->assertQueryStringHas('from', 'button');
            $this->assertStringEndsWith('/forms/contact?from=button', $href);
        });
    }

    private function contactForm(): Form
    {
        $form = Form::query()->create(['name' => 'Contact us', 'code' => 'contact', 'recipients' => 'sales@example.com']);

        foreach ([
            ['name' => 'name', 'type' => 'text', 'label' => 'Your name', 'required' => true, 'width' => 6],
            ['name' => 'email', 'type' => 'email', 'label' => 'E-mail', 'required' => true, 'width' => 6],
            ['name' => 'message', 'type' => 'textarea', 'label' => 'Message'],
        ] as $index => $field) {
            FormField::query()->create(['form_id' => $form->id, 'sort' => ($index + 1) * 10, ...$field]);
        }

        return $form;
    }

    /**
     * Tab or Shift+Tab from wherever the focus is, as a person presses it
     * (sending keys to an element would put the focus back on it).
     */
    private function tab(Browser $browser, bool $back = false): void
    {
        $keyboard = $browser->driver->getKeyboard();

        if ($back) {
            $keyboard->pressKey(WebDriverKeys::SHIFT);
        }

        $keyboard->sendKeys(WebDriverKeys::TAB);

        if ($back) {
            $keyboard->releaseKey(WebDriverKeys::SHIFT);
        }
    }

    /**
     * What has the focus, in a word: a field's name, the window's close
     * button, the send button or the button that opened the window.
     */
    private function focused(Browser $browser): string
    {
        return (string) $browser->script(
            "const el = document.activeElement;
             return el.matches('[data-atrium-form-close]') ? 'close'
                 : el.matches('[data-atrium-form-open]') ? 'opener'
                 : el.matches('[type=submit]') ? 'submit' : el.name;"
        )[0];
    }

    /**
     * WCAG contrast of an element's text against the page behind it.
     */
    private function contrast(Browser $browser, string $selector): float
    {
        [$text, $back] = $browser->script(
            "const el = document.querySelector('{$selector}');
             let node = el, back = 'rgba(0, 0, 0, 0)';
             while (node && (back === 'rgba(0, 0, 0, 0)' || back === 'transparent')) { back = getComputedStyle(node).backgroundColor; node = node.parentElement; }
             return [getComputedStyle(el).color, back];"
        )[0];

        $luminance = static function (string $colour): float {
            preg_match_all('/\d+(?:\.\d+)?/', $colour, $parts);
            $channels = array_map(static function (string $part): float {
                $c = (float) $part / 255;

                return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            }, array_slice($parts[0], 0, 3));

            return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        };

        [$a, $b] = [$luminance($text), $luminance($back)];

        return round((max($a, $b) + 0.05) / (min($a, $b) + 0.05), 2);
    }
}
