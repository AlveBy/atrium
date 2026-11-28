<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.

return [
    'at' => ':line-qator, :column-ustun: :message',
    'types' => [
        'number' => 'son',
        'text' => 'matn',
        'boolean' => 'ha/yoʻq',
        'date' => 'sana',
        'empty' => 'boʻsh',
        'any' => 'istalgan qiymat',
        'list' => 'roʻyxat: :of',
        'record' => 'yozuv',
        'or' => ' yoki ',
    ],
    'syntax' => [
        'encoding' => 'Formula yaroqli UTF-8 matni emas.',
        'too_long' => 'Formula :limit belgidan uzun.',
        'too_deep' => 'Qavslar va funksiyalar :limit darajadan chuqurroq joylashtirilgan.',
        'unknown_character' => 'Nomaʼlum belgi: «:character».',
        'unclosed_string' => 'Qoʻshtirnoq bilan ochilgan matn yopilmagan.',
        'unclosed_name' => 'Kvadrat qavs bilan ochilgan nom yopilmagan.',
        'empty_name' => 'Kvadrat qavslar orasida nom yoʻq.',
        'bad_date' => 'Bunday sana yoʻq: :date.',
        'unexpected' => 'Bu yerda «:token» kutilmagan edi.',
        'unexpected_end' => 'Formula vaqtidan oldin tugayapti.',
        'operator_case' => 'Bu yerda «:word» kutilmagan edi. Operatorlar bosh harflar bilan yoziladi: :operator.',
        'chained_comparison' => 'Bir vaqtda faqat ikkita qiymatni solishtirish mumkin. Solishtirishlarni AND yoki OR bilan birlashtiring.',
    ],
    'arguments' => [
        'too_few' => ':function funksiyasiga argument yetishmayapti: kamida :min, berilgan: :given.',
        'too_many' => ':function funksiyasi kamroq argument oladi: koʻpi bilan :max, berilgan: :given.',
    ],
    'formula' => [
        'too_big' => 'Formulada :limit tadan koʻp qism bor.',
        'too_deep' => 'Formula :limit darajadan chuqurroq joylashtirilgan.',
        'too_long' => 'Saqlangan formula :limit baytdan uzun.',
    ],
    'ir' => [
        'invalid' => 'Saqlangan formula «:path» joyida buzilgan.',
        'version' => 'Formula bu yerda oʻqib boʻlmaydigan format versiyasida saqlangan; oʻqiladigan versiya: :supported.',
    ],
    'check' => [
        'unknown_field' => '«:name» maydoni yoʻq.',
        'unknown_variable' => 'Bu yerda «:name» elementi yoʻq: bu joyni oʻragan sikllarning birortasi bunday nomlanmagan.',
        'unknown_member' => 'Bogʻlangan yozuvda «:name» maydoni yoʻq.',
        'not_a_record' => '«.:name» uchun nuqtadan oldin bogʻlangan yozuv kerak, topilgani: :actual.',
        'unknown_function' => '«:name» funksiyasi yoʻq.',
        'permission' => ':function funksiyasi uchun «:permission» ruxsati kerak.',
        'operand' => '«:operator» operatoriga kerak: :expected, topilgani: :actual.',
        'argument' => ':function funksiyasining :argument-argumentiga kerak: :expected, topilgani: :actual.',
        'compare' => '«:operator» bu turlarni solishtira olmaydi: :left va :right.',
        'condition' => 'Shart ha yoki yoʻq berishi kerak, topilgani: :actual.',
        'branches' => 'Shartning ikkala natijasi bir turda boʻlishi kerak, topilgani: :then va :otherwise.',
        'not_a_list' => 'Siklga roʻyxat kerak, topilgani: :actual.',
        'depth' => 'Formula ruxsat etilgan :limit darajadan chuqurroq joylashtirilgan.',
        'deep_list' => 'Roʻyxatlar bir-birining ichiga :limit darajadan chuqurroq joylangan.',
    ],
    'value' => [
        'empty' => 'Bu yerda qiymat boʻsh, formulaga esa kerak: :expected.',
        'empty_field' => '«:field» maydoni boʻsh, formulaga esa kerak: :expected.',
        'wrong_type' => 'Formulaga kerak: :expected, topilgani: :actual.',
        'bad_field' => '«:field» maydonidagi qiymat uning turiga mos emas: :expected.',
        'bad_return' => ':function funksiyasi oʻz turiga mos kelmaydigan qiymat qaytardi: :expected.',
        'compare' => 'Bularni solishtirib boʻlmaydi: :left va :right.',
        'too_large' => 'Son juda katta: verguldan oldin :digits tadan koʻp raqam bor.',
        'division_by_zero' => 'Nolga boʻlish.',
        'exponent' => 'Daraja -:limit dan :limit gacha boʻlgan butun son boʻlishi kerak.',
        'whole_days' => 'Sanaga faqat butun kunlarni qoʻshish yoki undan ayirish mumkin.',
        'out_of_range' => ':function funksiyasining :argument-argumenti :min dan :max gacha boʻlgan butun son boʻlishi kerak.',
        'negative_root' => 'Manfiy sonning kvadrat ildizi yoʻq.',
        'date_out_of_range' => 'Sana 1–9999-yillardan tashqarida.',
        'no_values' => ':function funksiyasi ishlashi uchun birorta ham qiymat yoʻq.',
        'search_too_long' => ':function funksiyasi koʻpi bilan :limit ta belgini qidira oladi.',
    ],
    'limit' => [
        'steps' => 'Formula toʻxtatildi: :limit tadan koʻp qadam.',
        'time' => 'Formula toʻxtatildi: :limit ms dan uzoq ishladi.',
        'memory' => 'Formula toʻxtatildi: qiymatlari :limit baytdan koʻp joy egalladi.',
        'recursion' => 'Formula toʻxtatildi: :limit tadan koʻp formula bir-birining ichida ishlayotgan edi.',
    ],
];
