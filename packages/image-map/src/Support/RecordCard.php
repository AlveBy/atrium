<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Support;

use Atrium\Admin\Support\MediaUrl;
use Atrium\Admin\Support\PublicUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * The card of a record of another module an area points to: its name, a
 * picture, a line of text and its page, read from the attributes modules
 * usually have. A module that knows better gives `imageMapCard(): array`
 * with the same keys.
 */
final class RecordCard
{
    private const IMAGE = ['preview_picture', 'image', 'photo', 'picture', 'logo', 'cover', 'detail_picture'];

    private const TEXT = ['preview_text', 'short_description', 'description', 'address', 'detail_text', 'text'];

    /**
     * @return array{title:string,image:?string,text:?string,url:?string,lines:array<int,string>}|null
     */
    public static function for(?Model $record): ?array
    {
        // Deleted, its module removed, or switched off: the area shows its
        // own title and description instead.
        if ($record === null || $record->getAttribute('active') === false) {
            return null;
        }

        if (method_exists($record, 'imageMapCard')) {
            /** @var array<string,mixed> $card */
            $card = $record->imageMapCard();

            return [
                'title' => (string) ($card['title'] ?? ''),
                'image' => isset($card['image']) ? MediaUrl::to($card['image']) : null,
                'text' => isset($card['text']) ? (string) $card['text'] : null,
                'url' => isset($card['url']) ? (string) $card['url'] : null,
                'lines' => array_values(array_map('strval', (array) ($card['lines'] ?? []))),
            ];
        }

        $title = method_exists($record, 'getAdminTitle') ? (string) $record->getAdminTitle() : (string) ($record->getAttribute('name') ?? '');

        return [
            'title' => $title,
            'image' => MediaUrl::to(self::first($record, self::IMAGE)),
            'text' => self::text($record),
            'url' => PublicUrl::forRecord($record),
            'lines' => self::lines($record),
        ];
    }

    private static function first(Model $record, array $attributes): ?string
    {
        foreach ($attributes as $attribute) {
            $value = $record->getAttribute($attribute);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private static function text(Model $record): ?string
    {
        foreach (self::TEXT as $attribute) {
            $value = $record->getAttribute($attribute);

            if (is_string($value) && ($text = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5))) !== '') {
                return Str::limit(preg_replace('/\s+/u', ' ', $text) ?? $text, 240);
            }
        }

        return null;
    }

    /**
     * Contacts, when the record has them: a store's phone and opening hours.
     *
     * @return array<int,string>
     */
    private static function lines(Model $record): array
    {
        $lines = [];

        foreach (['phone', 'phones', 'opens_at', 'email'] as $attribute) {
            $value = $record->getAttribute($attribute);

            if (is_array($value)) {
                $value = implode(', ', array_filter(array_map(static fn ($v): string => is_scalar($v) ? (string) $v : '', $value)));
            }

            if (is_string($value) && trim($value) !== '') {
                $lines[] = trim($value);
            }
        }

        return $lines;
    }
}
