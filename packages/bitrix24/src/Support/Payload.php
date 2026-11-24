<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24\Support;

use Atrium\Bitrix24\Models\FormLink;
use Atrium\Forms\Models\FormSubmission;

/**
 * What a form submission becomes in Bitrix24: the fields of a lead, or of a
 * contact and a deal. A form field goes where the form's link says
 * ("phone = PHONE"); a field without a line goes by its type (an e-mail to
 * EMAIL, a phone to PHONE, "name" to NAME) and what still has no place is
 * written into COMMENTS, label and value, so nothing a visitor typed is lost.
 */
final class Payload
{
    /** Fields Bitrix24 keeps as a list of values with a type. */
    public const MULTI = ['PHONE', 'EMAIL', 'WEB', 'IM'];

    /** Fields of a contact; in a deal the rest belongs to the deal. */
    public const CONTACT = ['NAME', 'SECOND_NAME', 'LAST_NAME', 'PHONE', 'EMAIL', 'WEB', 'ADDRESS', 'POST'];

    private const LINE = '/^([a-z0-9_.\-]+)\s*=\s*([A-Z][A-Z0-9_]*)$/i';

    private const NAMES = ['name' => 'NAME', 'first_name' => 'NAME', 'fio' => 'NAME', 'full_name' => 'NAME', 'last_name' => 'LAST_NAME', 'surname' => 'LAST_NAME'];

    /**
     * The "form field = CODE" lines of a link, by form field.
     *
     * @return array<string,string>
     */
    public static function mapping(?string $text): array
    {
        $map = [];

        foreach (self::lines($text) as $line) {
            if (preg_match(self::LINE, $line, $pair) === 1) {
                $map[$pair[1]] = strtoupper($pair[2]);
            }
        }

        return $map;
    }

    /**
     * The number of the first line that is not "form field = CODE", or null.
     */
    public static function badLine(?string $text): ?int
    {
        foreach (self::lines($text) as $number => $line) {
            if (preg_match(self::LINE, $line) !== 1) {
                return $number;
            }
        }

        return null;
    }

    /**
     * The fields of the lead, or of the contact and the deal together.
     *
     * @return array<string,mixed>
     */
    public static function fields(FormSubmission $submission, ?FormLink $link): array
    {
        $map = self::mapping($link?->mapping);
        $lists = [];
        $texts = [];
        $comments = [];

        foreach ($submission->data as $item) {
            $value = self::text($item['value']);

            if ($value === '') {
                continue;
            }

            $code = $map[$item['name']] ?? self::guess($item['type'], $item['name']);

            if ($code === null) {
                $comments[] = $item['label'].': '.$value;
            } elseif (in_array($code, self::MULTI, true)) {
                $lists[$code][] = ['VALUE' => $value, 'VALUE_TYPE' => 'WORK'];
            } else {
                $texts[$code] = isset($texts[$code]) ? $texts[$code]."\n".$value : $value;
            }
        }

        $fields = [...$texts, ...$lists];

        if ($comments !== []) {
            $fields['COMMENTS'] = implode("\n", isset($fields['COMMENTS']) ? [$fields['COMMENTS'], ...$comments] : $comments);
        }

        $fields['TITLE'] ??= __('atrium-bitrix24::bitrix24.payload.title', ['form' => $submission->form_name]);
        $fields['SOURCE_ID'] ??= 'WEB';

        if (filled($submission->page_url)) {
            $fields['SOURCE_DESCRIPTION'] ??= $submission->page_url;
        }

        return $fields;
    }

    /**
     * A contact's fields and a deal's fields, from the fields of a lead. A
     * contact needs a name: a visitor who gave none is "Website visitor".
     *
     * @param  array<string,mixed>  $fields
     * @return array{0:array<string,mixed>,1:array<string,mixed>}
     */
    public static function split(array $fields): array
    {
        $contact = array_intersect_key($fields, array_flip(self::CONTACT));

        if (! isset($contact['NAME']) && ! isset($contact['LAST_NAME'])) {
            $contact['NAME'] = __('atrium-bitrix24::bitrix24.payload.visitor');
        }

        return [$contact, array_diff_key($fields, $contact)];
    }

    private static function guess(string $type, string $name): ?string
    {
        return match (true) {
            $type === 'email' => 'EMAIL',
            $type === 'tel' => 'PHONE',
            default => self::NAMES[strtolower($name)] ?? null,
        };
    }

    private static function text(mixed $value): string
    {
        return match (true) {
            $value === true => __('atrium-bitrix24::bitrix24.payload.yes'),
            is_scalar($value) => trim((string) $value),
            default => '',
        };
    }

    /**
     * The lines that say something, by their number in the text (from 1).
     *
     * @return array<int,string>
     */
    private static function lines(?string $text): array
    {
        $lines = [];

        foreach (preg_split('/\R/', (string) $text) ?: [] as $index => $line) {
            $line = trim($line);

            if ($line !== '' && ! str_starts_with($line, '#')) {
                $lines[$index + 1] = $line;
            }
        }

        return $lines;
    }
}
