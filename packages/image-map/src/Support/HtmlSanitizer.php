<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Support;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Cleans the HTML of an area before it is saved, by an allow list.
 *
 * Two levels:
 *
 *  - text: what the visual editor makes — paragraphs, headings, lists,
 *    links, pictures, tables; style only for alignment and colour.
 *  - custom: that plus layout blocks, details, video, audio, iframes from
 *    the allowed hosts, classes, ids, data-* and ARIA attributes and a
 *    filtered style. For the custom HTML of administrators who may set the
 *    module up.
 *
 * Never kept, at any level: scripts, styles, forms, objects, event
 * attributes (onclick…), and addresses other than http(s), mailto, tel and
 * relative ones (no javascript:, no data:).
 */
final class HtmlSanitizer
{
    public const TEXT = 'text';

    public const CUSTOM = 'custom';

    /** Removed together with everything inside. */
    private const DROP = [
        'script', 'style', 'template', 'noscript', 'object', 'embed', 'applet', 'frame', 'frameset',
        'form', 'input', 'button', 'select', 'option', 'textarea', 'link', 'meta', 'base', 'title',
        'head', 'svg', 'math', 'canvas', 'dialog', 'portal', 'iframe',
    ];

    private const TEXT_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'ins', 'sub', 'sup', 'small', 'mark',
        'a', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'code', 'pre', 'hr', 'img',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col',
        'figure', 'figcaption', 'span', 'div', 'dl', 'dt', 'dd', 'abbr', 'time',
    ];

    private const CUSTOM_TAGS = [
        'h1', 'section', 'article', 'aside', 'header', 'footer', 'address', 'cite', 'q', 'kbd', 'var',
        'details', 'summary', 'picture', 'source', 'video', 'audio', 'track', 'iframe',
    ];

    /** Attributes of any allowed element. */
    private const COMMON = ['title', 'lang', 'dir'];

    private const CUSTOM_COMMON = ['class', 'id', 'role'];

    /** @var array<string,array<int,string>> */
    private const PER_TAG = [
        'a' => ['href', 'target', 'rel', 'hreflang'],
        'img' => ['src', 'alt', 'width', 'height', 'loading'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan', 'scope'],
        'col' => ['span'],
        'colgroup' => ['span'],
        'ol' => ['start', 'type', 'reversed'],
        'time' => ['datetime'],
        'abbr' => ['title'],
        'q' => ['cite'],
        'blockquote' => ['cite'],
        'details' => ['open'],
        'source' => ['src', 'type', 'media', 'srcset'],
        'track' => ['src', 'kind', 'srclang', 'label', 'default'],
        'video' => ['src', 'poster', 'controls', 'width', 'height', 'muted', 'loop', 'playsinline', 'preload'],
        'audio' => ['src', 'controls', 'loop', 'muted', 'preload'],
        'iframe' => ['src', 'width', 'height', 'title', 'allow', 'allowfullscreen', 'loading', 'referrerpolicy'],
    ];

    /**
     * Empty elements the HTML 4 parser of libxml does not know: it would put
     * the following content inside them and print a closing tag.
     */
    private const VOID = ['source', 'track', 'embed', 'wbr'];

    private const URL_ATTRIBUTES = ['href', 'src', 'poster', 'cite', 'srcset'];

    private const TEXT_STYLES = ['text-align', 'color', 'background-color'];

    private const CUSTOM_STYLES = [
        'text-align', 'color', 'background-color', 'font-weight', 'font-style', 'font-size', 'line-height',
        'text-decoration', 'text-transform', 'letter-spacing', 'white-space',
        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'border', 'border-top', 'border-right', 'border-bottom', 'border-left', 'border-color', 'border-width',
        'border-style', 'border-radius', 'width', 'max-width', 'min-width', 'height', 'max-height', 'min-height',
        'display', 'gap', 'flex', 'flex-direction', 'flex-wrap', 'align-items', 'justify-content',
        'grid-template-columns', 'aspect-ratio', 'object-fit', 'vertical-align', 'list-style',
    ];

    /**
     * @param  array<int,string>  $iframeHosts  hosts an iframe may load from (custom level only)
     */
    public function __construct(private readonly array $iframeHosts = []) {}

    public function clean(?string $html, string $level = self::TEXT): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div>'.$html.'</div></body></html>',
            LIBXML_NONET,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);
        $root = $body?->firstChild;

        if (! $root instanceof DOMElement) {
            return '';
        }

        $this->cleanChildren($root, $level);

        $out = '';

        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $document->saveHTML($child);
        }

        return trim(str_replace(array_map(fn (string $tag): string => '</'.$tag.'>', self::VOID), '', $out));
    }

    private function cleanChildren(DOMNode $node, string $level): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $this->cleanElement($child, $level);
            } elseif ($child->nodeType !== XML_TEXT_NODE) {
                // Comments, CDATA, processing instructions.
                $node->removeChild($child);
            }
        }
    }

    private function cleanElement(DOMElement $element, string $level): void
    {
        $tag = strtolower($element->tagName);
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        if (in_array($tag, self::VOID, true)) {
            // Whatever the parser put inside belongs after it, cleaned first:
            // the loop above has already passed this point.
            $this->cleanChildren($element, $level);

            while ($element->lastChild !== null) {
                $parent->insertBefore($element->lastChild, $element->nextSibling);
            }
        }

        $allowed = in_array($tag, self::TEXT_TAGS, true)
            || ($level === self::CUSTOM && in_array($tag, self::CUSTOM_TAGS, true));

        if ($tag === 'iframe' && $allowed && ! $this->iframeAllowed($element->getAttribute('src'))) {
            $allowed = false;
        }

        if (! $allowed) {
            if (in_array($tag, self::DROP, true)) {
                $parent->removeChild($element);

                return;
            }

            // An unknown wrapper (<font>, <center>, <main>): keep what is inside.
            $this->cleanChildren($element, $level);

            while ($element->firstChild !== null) {
                $parent->insertBefore($element->firstChild, $element);
            }

            $parent->removeChild($element);

            return;
        }

        $this->cleanAttributes($element, $tag, $level);
        $this->cleanChildren($element, $level);
    }

    private function cleanAttributes(DOMElement $element, string $tag, string $level): void
    {
        $allowed = [...self::COMMON, ...(self::PER_TAG[$tag] ?? [])];

        if ($level === self::CUSTOM) {
            $allowed = [...$allowed, ...self::CUSTOM_COMMON];
        }

        /** @var array<int,DOMAttr> $attributes */
        $attributes = iterator_to_array($element->attributes);

        foreach ($attributes as $attribute) {
            $name = strtolower($attribute->name);
            $value = $attribute->value;

            $keep = in_array($name, $allowed, true)
                || ($level === self::CUSTOM && preg_match('/^(aria|data)-[a-z0-9][a-z0-9-]*$/', $name) === 1)
                || $name === 'style';

            if ($keep && in_array($name, self::URL_ATTRIBUTES, true)) {
                $keep = $name === 'srcset' ? $this->safeSrcset($value) : $this->safeUrl($value);
            }

            if ($keep && $name === 'style') {
                $value = $this->cleanStyle($value, $level === self::CUSTOM ? self::CUSTOM_STYLES : self::TEXT_STYLES);
                $keep = $value !== '';
            }

            if ($keep && $name === 'target') {
                $keep = in_array($value, ['_blank', '_self'], true);
            }

            if (! $keep) {
                $element->removeAttribute($attribute->name);
            } elseif ($value !== $attribute->value) {
                $element->setAttribute($attribute->name, $value);
            }
        }

        if ($tag === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    /**
     * http(s), mailto, tel, or an address without a scheme (/path, #id,
     * page.html, ?q=1).
     */
    private function safeUrl(string $url): bool
    {
        // Browsers ignore control characters and spaces inside a scheme:
        // "java\tscript:" still runs.
        $url = preg_replace('/[\x00-\x20\x7F]+/u', '', $url) ?? '';

        if ($url === '') {
            return false;
        }

        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $url, $match) === 1) {
            return in_array(strtolower($match[1]), ['http', 'https', 'mailto', 'tel'], true);
        }

        return ! str_starts_with($url, '\\');
    }

    private function safeSrcset(string $srcset): bool
    {
        foreach (explode(',', $srcset) as $candidate) {
            $url = strtok(trim($candidate), " \t\n");

            if ($url === false || ! $this->safeUrl($url)) {
                return false;
            }
        }

        return true;
    }

    private function iframeAllowed(string $src): bool
    {
        $src = trim($src);

        if (! str_starts_with(strtolower($src), 'https://')) {
            return false;
        }

        $host = strtolower((string) parse_url($src, PHP_URL_HOST));

        if ($host === '') {
            return false;
        }

        foreach ($this->iframeHosts as $allowed) {
            $allowed = strtolower(trim($allowed));

            if ($allowed !== '' && ($host === $allowed || str_ends_with($host, '.'.$allowed))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Only the listed properties, and no value that could load or run
     * anything: url(), expression(), escapes, at-rules, comments.
     *
     * @param  array<int,string>  $properties
     */
    private function cleanStyle(string $style, array $properties): string
    {
        $kept = [];

        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);

            if (! in_array($property, $properties, true) || $value === '') {
                continue;
            }

            if (preg_match('/url\s*\(|expression|javascript|\\\\|@|\/\*|<|>|&/i', $value) === 1
                || preg_match('/^[#%,.\w\s()+\-\/!]*$/u', $value) !== 1) {
                continue;
            }

            $kept[] = $property.': '.$value;
        }

        return implode('; ', $kept);
    }
}
