<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Інтэграцыі',
    'title_log' => 'Журнал абмену',

    'status' => [
        'ok' => 'Выканана',
        'retry' => 'Памылка, паўторым',
        'failed' => 'Памылка',
    ],
    'direction' => [
        'out' => 'З сайта',
        'in' => 'На сайт',
    ],
    'fields' => [
        'when' => 'Калі',
        'integration' => 'Інтэграцыя',
        'direction' => 'Кірунак',
        'action' => 'Дзеянне',
        'subject' => 'Пра што',
        'status' => 'Вынік',
        'message' => 'Што зроблена',
        'error' => 'Памылка',
        'attempt' => 'Спроба',
        'duration' => 'Час, мс',
    ],

    'settings' => [
        'description' => 'Каго апавяшчаць, калі канектар (1C, Bitrix24) вычарпаў апошнюю спробу, і як доўга захоўваецца журнал абмену.',
        'alert_emails' => 'E-mail для апавяшчэнняў',
        'alert_emails_hint' => 'Адзін або некалькі адрасоў праз коску або з новага радка. Пуста: усе актыўныя суперадміністратары. Не больш за адзін ліст у гадзіну на інтэграцыю.',
        'retention_days' => 'Захоўваць журнал, дзён',
        'retention_days_hint' => 'Старыя запісы выдаляюцца кожную ноч.',
    ],

    'alert' => [
        'subject' => ':site: збой інтэграцыі :integration',
        'what' => 'Інтэграцыя :integration не здолела выканаць дзеянне :action і спыніла спробы.',
        'subject_line' => 'Пра што: :subject',
        'error' => 'Памылка: :error',
        'attempts' => 'Зроблена спроб: :count',
    ],
];
