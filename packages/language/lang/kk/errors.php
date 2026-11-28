<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.

return [
    'at' => ':line-жол, :column-орын: :message',
    'types' => [
        'number' => 'сан',
        'text' => 'мәтін',
        'boolean' => 'иә/жоқ',
        'date' => 'күн',
        'empty' => 'бос',
        'any' => 'кез келген мән',
        'list' => 'тізім: :of',
        'record' => 'жазба',
        'or' => ' немесе ',
    ],
    'syntax' => [
        'encoding' => 'Формула жарамды UTF-8 мәтіні емес.',
        'too_long' => 'Формула :limit таңбадан ұзын.',
        'too_deep' => 'Жақшалар мен функциялар :limit деңгейден тереңірек салынған.',
        'unknown_character' => 'Белгісіз таңба: «:character».',
        'unclosed_string' => 'Тырнақшамен ашылған мәтін жабылмаған.',
        'unclosed_name' => 'Шаршы жақшамен ашылған атау жабылмаған.',
        'empty_name' => 'Шаршы жақшалардың арасында атау жоқ.',
        'bad_date' => 'Мұндай күн жоқ: :date.',
        'unexpected' => 'Мұнда «:token» күтілмеген.',
        'unexpected_end' => 'Формула ерте аяқталды.',
        'operator_case' => 'Мұнда «:word» күтілмеген. Операторлар бас әріптермен жазылады: :operator.',
        'chained_comparison' => 'Бір уақытта тек екі мәнді салыстыруға болады. Салыстыруларды AND немесе OR арқылы біріктіріңіз.',
    ],
    'arguments' => [
        'too_few' => ':function функциясына аргумент жетіспейді: кемінде :min, берілгені: :given.',
        'too_many' => ':function функциясы аз аргумент қабылдайды: ең көбі :max, берілгені: :given.',
    ],
    'formula' => [
        'too_big' => 'Формулада :limit бөліктен көп.',
        'too_deep' => 'Формула :limit деңгейден тереңірек салынған.',
        'too_long' => 'Сақталған формула :limit байттан ұзын.',
    ],
    'ir' => [
        'invalid' => 'Сақталған формула «:path» орнында бүлінген.',
        'version' => 'Формула мұнда оқылмайтын пішім нұсқасында сақталған; оқылатын нұсқа: :supported.',
    ],
    'check' => [
        'unknown_field' => '«:name» өрісі жоқ.',
        'unknown_variable' => 'Мұнда «:name» элементі жоқ: бұл орынды қоршаған циклдердің ешқайсысы бұлай аталмаған.',
        'unknown_member' => 'Байланысты жазбада «:name» өрісі жоқ.',
        'not_a_record' => '«.:name» үшін нүктенің алдында байланысты жазба қажет, табылғаны: :actual.',
        'unknown_function' => '«:name» функциясы жоқ.',
        'permission' => ':function функциясына «:permission» рұқсаты қажет.',
        'operand' => '«:operator» операторына қажет: :expected, табылғаны: :actual.',
        'argument' => ':function функциясының :argument-аргументіне қажет: :expected, табылғаны: :actual.',
        'compare' => '«:operator» бұл түрлерді салыстыра алмайды: :left және :right.',
        'condition' => 'Шарт иә немесе жоқ беруі керек, табылғаны: :actual.',
        'branches' => 'Шарттың екі нәтижесі де бір түрде болуы керек, табылғаны: :then және :otherwise.',
        'not_a_list' => 'Циклге тізім қажет, табылғаны: :actual.',
        'depth' => 'Формула рұқсат етілген :limit деңгейден тереңірек салынған.',
        'deep_list' => 'Тізімдер бір-бірінің ішіне :limit деңгейден тереңірек салынған.',
    ],
    'value' => [
        'empty' => 'Мұнда мән бос, ал формулаға қажет: :expected.',
        'empty_field' => '«:field» өрісі бос, ал формулаға қажет: :expected.',
        'wrong_type' => 'Формулаға қажет: :expected, табылғаны: :actual.',
        'bad_field' => '«:field» өрісіндегі мән оның түріне сәйкес емес: :expected.',
        'bad_return' => ':function функциясы өз түріне сәйкес келмейтін мән қайтарды: :expected.',
        'compare' => 'Бұларды салыстыруға болмайды: :left және :right.',
        'too_large' => 'Сан тым үлкен: үтірге дейін :digits цифрдан көп.',
        'division_by_zero' => 'Нөлге бөлу.',
        'exponent' => 'Дәреже -:limit мен :limit аралығындағы бүтін сан болуы керек.',
        'whole_days' => 'Күнге тек бүтін күндерді қосуға немесе одан алуға болады.',
        'out_of_range' => ':function функциясының :argument-аргументі :min мен :max аралығындағы бүтін сан болуы керек.',
        'negative_root' => 'Теріс санның квадрат түбірі жоқ.',
        'date_out_of_range' => 'Күн 1–9999 жылдардан тыс.',
        'no_values' => ':function функциясы өңдейтін бірде-бір мән жоқ.',
        'search_too_long' => ':function функциясы ең көбі :limit таңбаны іздей алады.',
    ],
    'limit' => [
        'steps' => 'Формула тоқтатылды: :limit қадамнан көп.',
        'time' => 'Формула тоқтатылды: :limit мс-тан ұзақ орындалды.',
        'memory' => 'Формула тоқтатылды: мәндері :limit байттан көп орын алды.',
        'recursion' => 'Формула тоқтатылды: :limit формуладан көбі бірінің ішінде бірі орындалып жатты.',
    ],
];
