<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24\Tests;

use Atrium\Bitrix24\Models\FormLink;
use Atrium\Bitrix24\Support\Payload;

final class PayloadTest extends TestCase
{
    private function link(string $mapping, string $mode = 'lead'): FormLink
    {
        return new FormLink(['mode' => $mode, 'mapping' => $mapping]);
    }

    public function test_the_mapping_reads_one_pair_per_line_and_upper_cases_the_code(): void
    {
        $this->assertSame(
            ['phone' => 'PHONE', 'msg' => 'COMMENTS', 'a.b-c_1' => 'UF_CRM_1', 'x' => 'ADDRESS'],
            Payload::mapping("phone = PHONE\n  msg=comments  \n\r\na.b-c_1 =  uf_crm_1\nx\t=\tADDRESS"),
        );
        $this->assertSame([], Payload::mapping(null));
        $this->assertSame([], Payload::mapping(''));
    }

    public function test_comments_and_blank_lines_and_bad_lines_are_not_pairs(): void
    {
        $this->assertSame(['a' => 'NAME'], Payload::mapping("# a note\n\n   # indented note\nnonsense\na = NAME\nb = 9X\n= NAME\nc = "));
    }

    public function test_a_line_is_matched_whole(): void
    {
        $this->assertSame([], Payload::mapping("phone = PHONE extra\nx phone = PHONE\nphone = PHONE;"));
    }

    public function test_the_first_bad_line_is_named_by_its_number_counting_every_line(): void
    {
        $this->assertNull(Payload::badLine("a = NAME\n# fine\n\nb = PHONE"));
        $this->assertNull(Payload::badLine(null));
        $this->assertNull(Payload::badLine(''));
        $this->assertSame(1, Payload::badLine('what'));
        $this->assertSame(4, Payload::badLine("a = NAME\n# fine\n\nwhat is this\nand this"));
        $this->assertSame(2, Payload::badLine("a = NAME\r\nb = \r\nc = PHONE"));
    }

    public function test_fields_go_by_the_type_when_there_is_no_link_and_the_rest_to_the_comment(): void
    {
        $fields = Payload::fields($this->submit(), null);

        $this->assertSame([
            'NAME' => 'Ann',
            'EMAIL' => [['VALUE' => 'ann@example.com', 'VALUE_TYPE' => 'WORK']],
            'PHONE' => [['VALUE' => '+100 200 300', 'VALUE_TYPE' => 'WORK']],
            'COMMENTS' => 'Message: Where is my parcel?',
            'TITLE' => 'Website: Contact us',
            'SOURCE_ID' => 'WEB',
            'SOURCE_DESCRIPTION' => 'https://site.example.test/contacts',
        ], $fields);
    }

    public function test_a_line_of_the_link_beats_the_type(): void
    {
        $fields = Payload::fields($this->submit(), $this->link("message = UF_CRM_MESSAGE\nemail = WEB\nname = LAST_NAME"));

        $this->assertSame('Ann', $fields['LAST_NAME']);
        $this->assertArrayNotHasKey('NAME', $fields);
        $this->assertSame([['VALUE' => 'ann@example.com', 'VALUE_TYPE' => 'WORK']], $fields['WEB']);
        $this->assertArrayNotHasKey('EMAIL', $fields);
        $this->assertSame('Where is my parcel?', $fields['UF_CRM_MESSAGE']);
        $this->assertArrayNotHasKey('COMMENTS', $fields);
    }

    public function test_the_known_names_of_a_person_are_found_in_any_case(): void
    {
        foreach (['name' => 'NAME', 'First_Name' => 'NAME', 'FIO' => 'NAME', 'full_name' => 'NAME', 'last_name' => 'LAST_NAME', 'Surname' => 'LAST_NAME'] as $name => $code) {
            $fields = Payload::fields($this->submit(data: [['name' => $name, 'type' => 'text', 'label' => 'L', 'value' => 'V']]), null);

            $this->assertSame('V', $fields[$code], $name);
        }
    }

    public function test_a_field_that_is_neither_typed_nor_known_is_not_a_name(): void
    {
        $fields = Payload::fields($this->submit(data: [['name' => 'nickname', 'type' => 'text', 'label' => 'Nick', 'value' => 'ann']]), null);

        $this->assertArrayNotHasKey('NAME', $fields);
        $this->assertSame('Nick: ann', $fields['COMMENTS']);
    }

    public function test_two_values_for_one_plain_field_are_joined_and_for_a_list_field_kept_apart(): void
    {
        $fields = Payload::fields($this->submit(data: [
            ['name' => 'a', 'type' => 'text', 'label' => 'A', 'value' => 'one'],
            ['name' => 'b', 'type' => 'text', 'label' => 'B', 'value' => 'two'],
            ['name' => 'p1', 'type' => 'tel', 'label' => 'P1', 'value' => '111'],
            ['name' => 'p2', 'type' => 'tel', 'label' => 'P2', 'value' => '222'],
        ]), $this->link("a = ADDRESS\nb = ADDRESS"));

        $this->assertSame("one\ntwo", $fields['ADDRESS']);
        $this->assertSame([['VALUE' => '111', 'VALUE_TYPE' => 'WORK'], ['VALUE' => '222', 'VALUE_TYPE' => 'WORK']], $fields['PHONE']);
    }

    public function test_the_comment_keeps_what_the_link_put_there_and_adds_the_rest_below(): void
    {
        $fields = Payload::fields($this->submit(data: [
            ['name' => 'note', 'type' => 'textarea', 'label' => 'Note', 'value' => 'first'],
            ['name' => 'extra', 'type' => 'text', 'label' => 'Extra', 'value' => 'second'],
            ['name' => 'more', 'type' => 'text', 'label' => 'More', 'value' => 'third'],
        ]), $this->link('note = COMMENTS'));

        $this->assertSame("first\nExtra: second\nMore: third", $fields['COMMENTS']);
    }

    public function test_empty_values_are_left_out_and_a_ticked_box_says_yes(): void
    {
        $fields = Payload::fields($this->submit(data: [
            ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'value' => '  '],
            ['name' => 'a', 'type' => 'text', 'label' => 'A', 'value' => ''],
            ['name' => 'b', 'type' => 'text', 'label' => 'B', 'value' => null],
            ['name' => 'c', 'type' => 'text', 'label' => 'C', 'value' => ['x', 'y']],
            ['name' => 'consent', 'type' => 'checkbox', 'label' => 'I agree', 'value' => true],
            ['name' => 'news', 'type' => 'checkbox', 'label' => 'News', 'value' => false],
            ['name' => 'age', 'type' => 'number', 'label' => 'Age', 'value' => 41],
            ['name' => 'real', 'type' => 'text', 'label' => 'Real', 'value' => ' padded '],
        ]), null);

        $this->assertArrayNotHasKey('NAME', $fields);
        $this->assertSame("I agree: Yes\nAge: 41\nReal: padded", $fields['COMMENTS']);
    }

    public function test_a_title_or_source_from_the_link_stays_and_the_page_is_only_named_when_known(): void
    {
        $fields = Payload::fields($this->submit(data: [['name' => 't', 'type' => 'text', 'label' => 'T', 'value' => 'Own title']], pageUrl: null), $this->link('t = TITLE'));

        $this->assertSame('Own title', $fields['TITLE']);
        $this->assertSame('WEB', $fields['SOURCE_ID']);
        $this->assertArrayNotHasKey('SOURCE_DESCRIPTION', $fields);

        $fields = Payload::fields($this->submit(data: [['name' => 's', 'type' => 'text', 'label' => 'S', 'value' => 'PARTNER'], ['name' => 'd', 'type' => 'text', 'label' => 'D', 'value' => 'ad']]), $this->link("s = SOURCE_ID\nd = SOURCE_DESCRIPTION"));

        $this->assertSame('PARTNER', $fields['SOURCE_ID']);
        $this->assertSame('ad', $fields['SOURCE_DESCRIPTION']);

        $this->assertArrayNotHasKey('SOURCE_DESCRIPTION', Payload::fields($this->submit(pageUrl: ''), null));
    }

    public function test_a_contact_takes_its_own_fields_and_the_deal_the_rest(): void
    {
        [$contact, $deal] = Payload::split(Payload::fields($this->submit(), $this->link('message = OPPORTUNITY')));

        $this->assertSame(['NAME', 'EMAIL', 'PHONE'], array_keys($contact));
        $this->assertSame(['OPPORTUNITY', 'TITLE', 'SOURCE_ID', 'SOURCE_DESCRIPTION'], array_keys($deal));
    }

    public function test_every_contact_field_goes_to_the_contact(): void
    {
        $fields = array_fill_keys(Payload::CONTACT, 'x') + ['TITLE' => 't'];

        [$contact, $deal] = Payload::split($fields);

        $this->assertSame(Payload::CONTACT, array_keys($contact));
        $this->assertSame(['TITLE'], array_keys($deal));
    }

    public function test_a_contact_without_a_name_is_a_website_visitor_unless_it_has_a_last_name(): void
    {
        [$contact] = Payload::split(['PHONE' => [['VALUE' => '1', 'VALUE_TYPE' => 'WORK']], 'TITLE' => 't']);
        $this->assertSame('Website visitor', $contact['NAME']);

        [$contact] = Payload::split(['LAST_NAME' => 'Doe']);
        $this->assertArrayNotHasKey('NAME', $contact);

        [$contact] = Payload::split(['NAME' => 'Ann']);
        $this->assertSame('Ann', $contact['NAME']);
    }
}
