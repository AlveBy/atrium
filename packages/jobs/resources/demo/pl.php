<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for Polish: a made-up company in Poland.
 *
 * `country` is used when Polish is the main language of the install: the
 * offices, the people, the currency and the usual way to state a salary
 * there. `texts` are the vacancies in Polish, used whatever the main
 * country is, so they name no city, currency or law. Everything is
 * fictional; e-mail addresses are the part before the @, the domain is
 * `domain`.
 */
return [
    'country' => [
        'code' => 'PL',
        'currency' => 'PLN',
        'domain' => 'example.pl',
        'offices' => [
            ['name' => 'Warszawa', 'street' => 'ul. Przykładowa 12 lok. 304', 'postal_code' => '00-120', 'region' => null],
            ['name' => 'Kraków', 'street' => 'ul. Wzorcowa 7', 'postal_code' => '31-150', 'region' => null],
        ],
        'contact' => ['name' => 'Katarzyna Wiśniewska', 'email' => 'kariera', 'phone' => '+48 22 000 01 42'],
        // Gross (brutto). A month is how salaries are stated in Poland;
        // interns by the hour.
        'salaries' => [
            'backend' => ['from' => 14000, 'to' => 19000, 'period' => 'month'],
            'designer' => ['from' => 11000, 'to' => 15000, 'period' => 'month'],
            'account' => null,
            'intern' => ['from' => 35, 'period' => 'hour'],
        ],
        'applicants' => [
            ['name' => 'Michał Kowalczyk', 'email' => 'michal.kowalczyk'],
            ['name' => 'Agnieszka Lewandowska', 'email' => 'a.lewandowska'],
            ['name' => 'Zuzanna Wójcik', 'email' => 'zuzanna.wojcik'],
            ['name' => 'Tomasz Kamiński', 'email' => 'tomasz.kaminski'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => 'Rozwój oprogramowania',
            'design' => 'Projektowanie',
            'sales' => 'Sprzedaż',
            'support' => 'Obsługa klienta',
        ],
        'vacancies' => [
            'backend' => [
                'name' => 'Programista backend (PHP, Laravel)',
                'code' => 'programista-backend',
                'preview_text' => 'Twórz usługi, na których opiera się nasz produkt, w małym zespole, który wdraża zmiany co tydzień.',
                'description' => '<p>Nasz zespół to ośmiu programistów. Będziesz odpowiadać za funkcje od bazy danych aż po API.</p>',
                'duties' => '<ul><li>Projektowanie i tworzenie REST API</li><li>Przeglądanie kodu koleżanek i kolegów</li><li>Dbanie o to, by testy zawsze przechodziły</li></ul>',
                'requirements' => '<ul><li>Min. 3 lata doświadczenia z PHP</li><li>Laravel, SQL, Git</li><li>Poprawna polszczyzna w mowie i piśmie</li></ul>',
                'conditions' => '<ul><li>Dwa dni w tygodniu w biurze</li><li>Prywatna opieka medyczna</li><li>Roczny budżet szkoleniowy</li></ul>',
            ],
            'designer' => [
                'name' => 'Projektant produktu',
                'code' => 'projektant-produktu',
                'preview_text' => 'Kształtuj sposób, w jaki klienci korzystają z naszej aplikacji, od pierwszego szkicu po wdrożenie.',
                'description' => '<p>Dołączysz do dwuosobowego zespołu projektowego i będziesz blisko współpracować z menedżerami produktu i programistami.</p>',
                'duties' => '<ul><li>Wywiady z użytkownikami i testy prototypów</li><li>Dbanie o porządek w systemie projektowym</li></ul>',
                'requirements' => '<ul><li>Portfolio z wdrożonymi produktami</li><li>Figma</li></ul>',
                'conditions' => '<ul><li>Praca zdalna z dowolnego miejsca w kraju</li><li>Laptop i monitor do wyboru</li></ul>',
            ],
            'account' => [
                'name' => 'Opiekun klienta biznesowego',
                'code' => 'opiekun-klienta',
                'preview_text' => 'Opiekuj się naszymi klientami biznesowymi i pomagaj im w pełni wykorzystać produkt.',
                'description' => '<p>Będziesz mieć około trzydziestu własnych klientów i wsparcie całego zespołu.</p>',
                'duties' => '<ul><li>Wdrażanie nowych klientów</li><li>Spotkania z każdym klientem co najmniej raz na kwartał</li></ul>',
                'requirements' => '<ul><li>Min. rok doświadczenia w sprzedaży lub obsłudze klienta</li><li>Jasny i życzliwy styl pisania</li></ul>',
                'conditions' => '<ul><li>Premia za przedłużenia umów</li><li>Ciche biuro blisko komunikacji miejskiej</li></ul>',
            ],
            'intern' => [
                'name' => 'Stażysta w dziale obsługi klienta',
                'code' => 'staz-obsluga-klienta',
                'preview_text' => 'Trzy miesiące, dwadzieścia godzin w tygodniu, z opiekunem: dobry start w IT.',
                'description' => '<p>Będziesz odpowiadać na pytania klientów mailowo i na czacie oraz poznasz produkt od środka.</p>',
                'duties' => '<ul><li>Odpowiadanie klientom mailowo i na czacie</li><li>Spisywanie najczęściej zadawanych pytań</li></ul>',
                'requirements' => '<ul><li>Zapraszamy studentów</li><li>Cierpliwość i chęć pomagania</li></ul>',
                'conditions' => '<ul><li>Elastyczne godziny dopasowane do zajęć</li><li>Propozycja pracy dla najlepszych stażystów</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => 'Zaproszenie na rozmowę',
                'subject' => 'Twoja aplikacja na stanowisko :vacancy',
                'body' => "Dzień dobry, :name!\n\nDziękujemy za aplikację na stanowisko :vacancy. Chcielibyśmy Cię poznać: podaj, proszę, dwa lub trzy dogodne terminy w przyszłym tygodniu.\n\nPozdrawiamy serdecznie\n:company",
            ],
            'rejection' => [
                'name' => 'Odmowa',
                'subject' => 'Twoja aplikacja na stanowisko :vacancy',
                'body' => "Dzień dobry, :name!\n\nDziękujemy za zainteresowanie stanowiskiem :vacancy. Tym razem zdecydowaliśmy się kontynuować rekrutację z innymi kandydatami. Zachowamy Twoje CV i odezwiemy się, gdy pojawi się odpowiednie stanowisko.\n\nPozdrawiamy serdecznie\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            'Dzień dobry! Mam pięć lat doświadczenia z Laravel i chętnie dołączę do zespołu.',
            'Od sześciu lat pracuję z PHP i PostgreSQL, głównie przy API.',
            'Dzień dobry! Link do portfolio jest w CV.',
            'Na razie żadna oferta do mnie nie pasuje, ale chętnie podejmę u Państwa pracę jako tester QA.',
        ],
    ],
];
