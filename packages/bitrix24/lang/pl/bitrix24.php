<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Bitrix24',
    'title_links' => 'Bitrix24: formularze',

    'settings' => [
        'description' => 'Zgłoszenia z formularzy trafiają do Twojego Bitrix24 jako leady, a widżet czatu Bitrix24 jest wyświetlany na stronie. Utwórz w Bitrix24 webhook przychodzący (Zasoby dla programistów → Inne → Webhook przychodzący) z uprawnieniem do CRM i wklej tutaj jego adres.',
        'webhook' => 'Webhook przychodzący',
        'webhook_hint' => 'Adres, który pokazuje Bitrix24, w postaci https://portal.bitrix24.com/rest/1/code/. Jest przechowywany w postaci zaszyfrowanej i nie jest już wyświetlany: aby go zmienić, wklej nowy; zostaw pole puste, aby zachować obecny.',
        'send_forms' => 'Wysyłaj zgłoszenia z formularzy do Bitrix24',
        'send_forms_hint' => 'Formularz bez powiązania poniżej trafia jako lead, a jego pola są rozmieszczane według typu.',
        'widget_code' => 'Kod widżetu czatu',
        'widget_code_hint' => 'Kod otwartego kanału lub widżetu czatu z Bitrix24 (CRM → Centrum kontaktu → Czat online → Widżet). Jest umieszczany na końcu każdej strony serwisu; puste pole oznacza brak widżetu.',
        'check' => 'Sprawdź połączenie',
        'connected' => 'Połączono: :portal, użytkownik :user.',
    ],

    'fields' => [
        'form' => 'Formularz',
        'mode' => 'Wyślij jako',
        'active' => 'Wysyłaj ten formularz do Bitrix24',
        'mapping' => 'Pola',
        'mapping_hint' => 'Jedna linia na pole: "pole formularza = pole Bitrix24", na przykład "phone = PHONE" lub "message = COMMENTS". Pole bez linii trafia według swojego typu (e-mail do EMAIL, telefon do PHONE); to, co nie ma miejsca, trafia do komentarza. Linie zaczynające się od # są pomijane.',
    ],

    'modes' => [
        'lead' => 'Lead',
        'deal' => 'Deal z kontaktem',
    ],

    'errors' => [
        'no_webhook' => 'Webhook Bitrix24 nie jest ustawiony: wklej go w Moduły → Bitrix24.',
        'unreachable' => 'Bitrix24 nie odpowiada. Sprawdź adres portalu w webhooku.',
        'portal' => 'Bitrix24 odmówił: :error',
        'mapping_line' => 'Linia :line nie ma postaci "pole formularza = pole Bitrix24".',
    ],

    'payload' => [
        'subject' => 'Zgłoszenie z formularza nr :id',
        'title' => 'Strona: :form',
        'visitor' => 'Odwiedzający stronę',
        'yes' => 'Tak',
        'lead' => 'Utworzono lead nr :id.',
        'deal' => 'Utworzono deal nr :deal, kontakt nr :contact.',
        'gone' => 'Zgłoszenie zostało usunięte przed wysłaniem.',
    ],
];
