<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '1C',
    'title_runs' => 'Wymiana z 1C',

    'settings' => [
        'description' => 'Katalog pochodzi z 1C:Enterprise w standardowej wymianie CommerceML. W 1C, w wymianie ze stroną, podaj ten adres: :url (konfiguracja, która pyta tylko o stronę, bierze :bitrix po adresie strony), a niżej login i hasło ustawione tutaj.',
        'login' => 'Login',
        'login_hint' => 'Tym loginem loguje się 1C. Wymiana jest zamknięta, dopóki nie ustawisz i loginu, i hasła.',
        'password' => 'Hasło',
        'password_hint' => 'Przechowywane w postaci zaszyfrowanej i nie jest już pokazywane: aby zmienić, wpisz nowe; zostaw pole puste, aby zachować obecne.',
    ],

    'fields' => [
        'started_at' => 'Rozpoczęto',
        'finished_at' => 'Zakończono',
        'file' => 'Plik',
        'status_label' => 'Wynik',
        'sections_created' => 'Nowe sekcje',
        'sections_updated' => 'Zaktualizowane sekcje',
        'products_created' => 'Nowe produkty',
        'products_updated' => 'Zaktualizowane produkty',
        'offers_updated' => 'Zaktualizowane ceny i stany',
        'skipped' => 'Pominięte oferty (brak takiego produktu)',
        'errors' => 'Błędy',
        'error' => 'Co poszło nie tak',
    ],

    'status' => [
        'running' => 'W toku',
        'done' => 'Gotowe',
        'failed' => 'Niepowodzenie',
    ],

    'reply' => [
        'wrong_login' => 'Zły login lub hasło albo wymiana nie jest ustawiona w Moduły → 1C.',
        'denied' => 'Nie zalogowano: uruchom wymianę ponownie.',
        'unknown_type' => 'Nieznany typ wymiany: :type.',
        'unknown_mode' => 'Nieznany krok wymiany: :mode.',
        'bad_name' => 'Ta nazwa pliku jest niedozwolona.',
        'no_file' => 'Plik :file nie dotarł.',
        'progress' => 'Zaimportowane pozycje: :count.',
        'summary' => 'Sekcje: :sections_created nowych, :sections_updated zaktualizowanych. Produkty: :products_created nowych, :products_updated zaktualizowanych. Ceny i stany: :offers. Pominięto: :skipped. Błędy: :errors.',
        'no_orders' => 'Zamówienia nie są wymieniane: moduł sklepu nie jest zainstalowany.',
    ],
];
