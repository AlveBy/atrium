<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.

return [
    'at' => 'Satır :line, sütun :column: :message',
    'types' => [
        'number' => 'sayı',
        'text' => 'metin',
        'boolean' => 'evet/hayır',
        'date' => 'tarih',
        'empty' => 'boş',
        'any' => 'herhangi bir değer',
        'list' => 'liste: :of',
        'record' => 'kayıt',
        'or' => ' veya ',
    ],
    'syntax' => [
        'encoding' => 'Formül geçerli bir UTF-8 metni değil.',
        'too_long' => 'Formül :limit karakterden uzun.',
        'too_deep' => 'Parantezler ve işlevler :limit düzeyden daha derine iç içe geçmiş.',
        'unknown_character' => 'Bilinmeyen karakter “:character”.',
        'unclosed_string' => 'Tırnakla açılan metin kapatılmamış.',
        'unclosed_name' => 'Köşeli parantezle açılan ad kapatılmamış.',
        'empty_name' => 'Köşeli parantezler arasında ad yok.',
        'bad_date' => 'Böyle bir tarih yok: :date.',
        'unexpected' => 'Burada “:token” beklenmiyordu.',
        'unexpected_end' => 'Formül erken bitiyor.',
        'operator_case' => 'Burada “:word” beklenmiyordu. İşleçler büyük harfle yazılır: :operator.',
        'chained_comparison' => 'Aynı anda yalnızca iki değer karşılaştırılabilir. Karşılaştırmaları AND veya OR ile birleştirin.',
    ],
    'arguments' => [
        'too_few' => ':function işlevinde bağımsız değişken eksik: en az :min, verilen: :given.',
        'too_many' => ':function işlevi daha az bağımsız değişken alır: en çok :max, verilen: :given.',
    ],
    'formula' => [
        'too_big' => 'Formülde :limit parçadan fazlası var.',
        'too_deep' => 'Formül :limit düzeyden daha derine iç içe geçmiş.',
        'too_long' => 'Kaydedilen formül :limit bayttan uzun.',
    ],
    'ir' => [
        'invalid' => 'Kaydedilen formül “:path” konumunda bozuk.',
        'version' => 'Formül burada okunamayan bir biçim sürümünde kaydedilmiş; okunan sürüm: :supported.',
    ],
    'check' => [
        'unknown_field' => '“:name” adlı bir alan yok.',
        'unknown_variable' => 'Burada “:name” adlı bir öge yok: bu yeri saran hiçbir döngü bu adı taşımıyor.',
        'unknown_member' => 'Bağlı kayıtta “:name” alanı yok.',
        'not_a_record' => '“.:name” için noktadan önce bağlı bir kayıt gerekir, bulunan: :actual.',
        'unknown_function' => '“:name” adlı bir işlev yok.',
        'permission' => ':function işlevi “:permission” iznini gerektirir.',
        'operand' => '“:operator” işleci için gereken: :expected, bulunan: :actual.',
        'argument' => ':function işlevinin :argument. bağımsız değişkeni için gereken: :expected, bulunan: :actual.',
        'compare' => '“:operator” bu türleri karşılaştıramaz: :left ve :right.',
        'condition' => 'Koşul evet veya hayır vermeli, bulunan: :actual.',
        'branches' => 'Koşulun iki sonucu aynı türde olmalı, bulunan: :then ve :otherwise.',
        'not_a_list' => 'Döngü için liste gerekir, bulunan: :actual.',
        'depth' => 'Formül izin verilen :limit düzeyden daha derine iç içe geçmiş.',
        'deep_list' => 'Listeler :limit düzeyden daha derin iç içe geçmiş.',
    ],
    'value' => [
        'empty' => 'Burada bir değer boş, oysa formül için gereken: :expected.',
        'empty_field' => '“:field” alanı boş, oysa formül için gereken: :expected.',
        'wrong_type' => 'Formül için gereken: :expected, bulunan: :actual.',
        'bad_field' => '“:field” alanındaki değer alanın türüne uymuyor: :expected.',
        'bad_return' => ':function işlevi türüne uymayan bir değer döndürdü: :expected.',
        'compare' => 'Şunlar karşılaştırılamaz: :left ve :right.',
        'too_large' => 'Bir sayı çok büyük: ondalık ayırıcıdan önce :digits basamaktan fazlası var.',
        'division_by_zero' => 'Sıfıra bölme.',
        'exponent' => 'Üs, -:limit ile :limit arasında bir tam sayı olmalı.',
        'whole_days' => 'Bir tarihe yalnızca tam gün eklenebilir veya ondan çıkarılabilir.',
        'out_of_range' => ':function işlevinin :argument. bağımsız değişkeni :min ile :max arasında bir tam sayı olmalı.',
        'negative_root' => 'Negatif bir sayının karekökü yoktur.',
        'date_out_of_range' => 'Tarih 1 ile 9999 yılları dışında.',
        'no_values' => ':function işlevinin işleyeceği hiçbir değer yok.',
        'search_too_long' => ':function işlevi en çok :limit karakter arayabilir.',
    ],
    'limit' => [
        'steps' => 'Formül durduruldu: :limit adımdan fazlası gerekti.',
        'time' => 'Formül durduruldu: :limit ms’den uzun çalıştı.',
        'memory' => 'Formül durduruldu: değerleri :limit bayttan fazla yer tuttu.',
        'recursion' => 'Formül durduruldu: :limit formülden fazlası iç içe çalışıyordu.',
    ],
];
