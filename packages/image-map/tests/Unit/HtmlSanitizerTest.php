<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Tests\Unit;

use Atrium\ImageMap\Support\HtmlSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HtmlSanitizerTest extends TestCase
{
    private function clean(?string $html, string $level = HtmlSanitizer::TEXT): string
    {
        return (new HtmlSanitizer(['www.youtube-nocookie.com', 'player.example.org']))->clean($html, $level);
    }

    public function test_nothing_in_nothing_out(): void
    {
        $this->assertSame('', $this->clean(null));
        $this->assertSame('', $this->clean(''));
        $this->assertSame('', $this->clean("  \n "));
        $this->assertSame('', $this->clean('<script>alert(1)</script>'));
    }

    public function test_the_text_of_the_visual_editor_is_kept_as_it_is(): void
    {
        $html = '<h2>Opening hours</h2><p><strong>Daily</strong> 10:00–22:00, <a href="/plan#food">food court</a>.</p>'
            .'<ul><li>Café</li><li>Kids’ club</li></ul><table><tbody><tr><th scope="row">Hall 1</th><td colspan="2">IMAX</td></tr></tbody></table>';

        $this->assertSame($html, $this->clean($html));
        $this->assertSame($html, $this->clean($html, HtmlSanitizer::CUSTOM));
    }

    public function test_cyrillic_and_cjk_text_survives(): void
    {
        $this->assertSame('<p>Фуд-корт · 美食广场 · フードコート</p>', $this->clean('<p>Фуд-корт · 美食广场 · フードコート</p>'));
    }

    /**
     * @return iterable<string,array{0:string,1:string}>
     */
    public static function dangerous(): iterable
    {
        yield 'script with its text' => ['<p>Hi</p><script>steal()</script>', '<p>Hi</p>'];
        yield 'uppercase script' => ['<SCRIPT SRC="//x.example/a.js"></SCRIPT><p>ok</p>', '<p>ok</p>'];
        yield 'style block' => ['<style>body{display:none}</style><p>ok</p>', '<p>ok</p>'];
        yield 'event attribute' => ['<p onclick="steal()" onmouseover="x()">ok</p>', '<p>ok</p>'];
        yield 'image error handler' => ['<img src="/a.png" onerror="steal()" alt="Plan">', '<img src="/a.png" alt="Plan">'];
        yield 'javascript link' => ['<a href="javascript:alert(1)">x</a>', '<a>x</a>'];
        yield 'javascript in capitals' => ['<a href="JaVaScRiPt:alert(1)">x</a>', '<a>x</a>'];
        yield 'javascript with a tab inside' => ["<a href=\"java\tscript:alert(1)\">x</a>", '<a>x</a>'];
        yield 'javascript with an entity tab' => ['<a href="java&#9;script:alert(1)">x</a>', '<a>x</a>'];
        yield 'javascript after spaces' => ['<a href="   javascript:alert(1)">x</a>', '<a>x</a>'];
        yield 'vbscript' => ['<a href="vbscript:msgbox(1)">x</a>', '<a>x</a>'];
        yield 'data picture' => ['<img src="data:image/svg+xml;base64,PHN2Zz4=" alt="x">', '<img alt="x">'];
        yield 'backslash address' => ['<a href="\\\\evil.example\\share">x</a>', '<a>x</a>'];
        yield 'form' => ['<form action="/x"><input name="card"><button>Pay</button></form><p>ok</p>', '<p>ok</p>'];
        yield 'object and embed' => ['<object data="x.swf"></object><embed src="x.swf"><p>ok</p>', '<p>ok</p>'];
        yield 'handler hidden after an embed' => ['<embed src="x.swf"><img src="/a.png" onerror="steal()" alt="">', '<img src="/a.png" alt="">'];
        yield 'inline svg' => ['<svg onload="steal()"><circle r="5"/></svg><p>ok</p>', '<p>ok</p>'];
        yield 'comment' => ['<p>a<!-- secret -->b</p>', '<p>ab</p>'];
        yield 'conditional comment' => ['<!--[if IE]><script>x()</script><![endif]--><p>ok</p>', '<p>ok</p>'];
        yield 'meta refresh' => ['<meta http-equiv="refresh" content="0;url=https://evil.example"><p>ok</p>', '<p>ok</p>'];
        yield 'base tag' => ['<base href="https://evil.example/"><p>ok</p>', '<p>ok</p>'];
        yield 'style with url' => ['<p style="background-color: red; background-image: url(https://evil.example/t.gif)">x</p>', '<p style="background-color: red">x</p>'];
        yield 'style with expression' => ['<p style="color: expression(alert(1))">x</p>', '<p>x</p>'];
        yield 'style with an escape' => ['<p style="color: \\72 ed">x</p>', '<p>x</p>'];
        yield 'style with a comment' => ['<p style="color: red/**/">x</p>', '<p>x</p>'];
        yield 'style property not allowed' => ['<p style="position: fixed; top: 0">x</p>', '<p>x</p>'];
        yield 'target other than blank or self' => ['<a href="/x" target="_parent">x</a>', '<a href="/x">x</a>'];
    }

    #[DataProvider('dangerous')]
    public function test_dangerous_html_is_removed(string $html, string $expected): void
    {
        $this->assertSame($expected, $this->clean($html));
        $this->assertSame($expected, $this->clean($html, HtmlSanitizer::CUSTOM), 'The custom level is no less strict here');
    }

    public function test_safe_addresses_stay(): void
    {
        foreach (['https://example.org/a', 'http://example.org', 'mailto:info@example.org', 'tel:+10000000000', '/plan', '#unit-12', 'unit.html', '?floor=2'] as $url) {
            $this->assertSame('<a href="'.$url.'">x</a>', $this->clean('<a href="'.$url.'">x</a>'), $url);
        }
    }

    public function test_a_link_to_a_new_tab_cannot_reach_back(): void
    {
        $this->assertSame('<a href="https://example.org" target="_blank" rel="noopener noreferrer">x</a>', $this->clean('<a href="https://example.org" target="_blank" rel="opener">x</a>'));
    }

    public function test_unknown_wrappers_are_unwrapped_and_their_content_cleaned(): void
    {
        $this->assertSame('<p>Big <b>sale</b></p>', $this->clean('<p><font color="red">Big <b onclick="x()">sale</b></font></p>'));
        $this->assertSame('<p>centred</p>', $this->clean('<center><p>centred</p></center>'));
    }

    public function test_text_keeps_alignment_and_colour_only(): void
    {
        $this->assertSame('<p style="text-align: center; color: #1a1a1a">x</p>', $this->clean('<p style="text-align:center;color:#1a1a1a;font-size:40px;">x</p>'));
        $this->assertSame('<p style="text-align: center; font-size: 40px">x</p>', $this->clean('<p style="text-align:center;font-size:40px;">x</p>', HtmlSanitizer::CUSTOM));
    }

    public function test_layout_markup_only_at_the_custom_level(): void
    {
        $html = '<section class="promo" id="sale" data-unit="12" aria-label="Sale"><details open><summary>More</summary><p>Text</p></details></section>';

        $this->assertSame($html, $this->clean($html, HtmlSanitizer::CUSTOM));
        $this->assertSame('More<p>Text</p>', $this->clean($html), 'The text level keeps the words, not the layout');
    }

    public function test_odd_data_attribute_names_are_dropped(): void
    {
        $this->assertSame('<div>x</div>', $this->clean('<div data-x_y="1" data-="2">x</div>', HtmlSanitizer::CUSTOM));
    }

    public function test_iframes_load_only_from_allowed_hosts_over_https(): void
    {
        $video = '<iframe src="https://www.youtube-nocookie.com/embed/abc" title="Tour" allowfullscreen></iframe>';
        $this->assertSame('<iframe src="https://www.youtube-nocookie.com/embed/abc" title="Tour" allowfullscreen></iframe>', $this->clean($video, HtmlSanitizer::CUSTOM));
        $this->assertSame('<iframe src="https://cdn.player.example.org/v/1"></iframe>', $this->clean('<iframe src="https://cdn.player.example.org/v/1" onload="x()"></iframe>', HtmlSanitizer::CUSTOM), 'A subdomain of an allowed host');

        $this->assertSame('', $this->clean($video), 'Never at the text level');
        $this->assertSame('', $this->clean('<iframe src="http://www.youtube-nocookie.com/embed/abc"></iframe>', HtmlSanitizer::CUSTOM), 'Not over plain http');
        $this->assertSame('', $this->clean('<iframe src="https://evil.example/"></iframe>', HtmlSanitizer::CUSTOM));
        $this->assertSame('', $this->clean('<iframe src="https://notplayer.example.org.evil.example/"></iframe>', HtmlSanitizer::CUSTOM));
        $this->assertSame('', $this->clean('<iframe src="https://"></iframe>', HtmlSanitizer::CUSTOM));
        $this->assertSame('', (new HtmlSanitizer)->clean($video, HtmlSanitizer::CUSTOM), 'No host is allowed by default');
        $this->assertSame('', (new HtmlSanitizer(['', ' ']))->clean($video, HtmlSanitizer::CUSTOM));
    }

    public function test_srcset_is_checked_address_by_address(): void
    {
        $this->assertSame('<picture><source srcset="/a.webp 1x, /a@2x.webp 2x" type="image/webp"><img src="/a.jpg" alt=""></picture>', $this->clean('<picture><source srcset="/a.webp 1x, /a@2x.webp 2x" type="image/webp"><img src="/a.jpg" alt=""></picture>', HtmlSanitizer::CUSTOM));
        $this->assertSame('<picture><source type="image/webp"></picture>', $this->clean('<picture><source srcset="/a.webp 1x, javascript:x 2x" type="image/webp"></picture>', HtmlSanitizer::CUSTOM));
        $this->assertSame('<video controls><track kind="captions" src="/c.vtt" srclang="en"><a href="/v.mp4">Download</a></video>', $this->clean('<video controls><track kind="captions" src="/c.vtt" srclang="en"><a href="/v.mp4" onclick="x()">Download</a></video>', HtmlSanitizer::CUSTOM));
        $this->assertSame('<picture><source></picture>', $this->clean('<picture><source srcset=" , "></picture>', HtmlSanitizer::CUSTOM));
    }

    public function test_text_outside_any_tag_is_kept_and_escaped(): void
    {
        $this->assertSame('Hall &lt;1&gt; &amp; more', $this->clean('Hall &lt;1&gt; &amp; more'));
    }
}
