<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for German: a made-up company in Germany.
 *
 * `country` is used when German is the main language of the install: the
 * offices, the people, the currency and the usual way to state a salary
 * there. `texts` are the vacancies in German, used whatever the main
 * country is, so they name no city, currency or law. Everything is
 * fictional; e-mail addresses are the part before the @, the domain is
 * `domain`.
 */
return [
    'country' => [
        'code' => 'DE',
        'currency' => 'EUR',
        'domain' => 'example.de',
        'offices' => [
            ['name' => 'Berlin', 'street' => 'Beispielstraße 12', 'postal_code' => '10115', 'region' => null],
            ['name' => 'München', 'street' => 'Musterweg 7', 'postal_code' => '80331', 'region' => null],
        ],
        'contact' => ['name' => 'Julia Becker', 'email' => 'karriere', 'phone' => '+49 30 0000 0142'],
        // Gross. A year is how salaries are stated in Germany; interns and
        // working students by the hour.
        'salaries' => [
            'backend' => ['from' => 62000, 'to' => 75000, 'period' => 'year'],
            'designer' => ['from' => 55000, 'to' => 68000, 'period' => 'year'],
            'account' => null,
            'intern' => ['from' => 16, 'period' => 'hour'],
        ],
        'applicants' => [
            ['name' => 'Lukas Schneider', 'email' => 'lukas.schneider'],
            ['name' => 'Anna Weber', 'email' => 'a.weber'],
            ['name' => 'Sophie Hoffmann', 'email' => 'sophie.hoffmann'],
            ['name' => 'Jonas Fischer', 'email' => 'jonas.fischer'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => 'Entwicklung',
            'design' => 'Design',
            'sales' => 'Vertrieb',
            'support' => 'Kundensupport',
        ],
        'vacancies' => [
            'backend' => [
                'name' => 'Backend-Entwickler PHP/Laravel (m/w/d)',
                'code' => 'backend-entwickler',
                'preview_text' => 'Sie entwickeln die Dienste hinter unserem Produkt – in einem kleinen Team, das jede Woche ausliefert.',
                'description' => '<p>Unser Entwicklungsteam zählt acht Köpfe. Sie verantworten Funktionen von der Datenbank bis zur API.</p>',
                'duties' => '<ul><li>REST-APIs konzipieren und umsetzen</li><li>Den Code von Kolleginnen und Kollegen prüfen</li><li>Dafür sorgen, dass die Tests grün bleiben</li></ul>',
                'requirements' => '<ul><li>Mindestens drei Jahre Erfahrung mit PHP</li><li>Laravel, SQL, Git</li><li>Sehr gute Deutschkenntnisse in Wort und Schrift</li></ul>',
                'conditions' => '<ul><li>Zwei Bürotage pro Woche</li><li>Zuschuss zur Krankenzusatzversicherung</li><li>Jährliches Weiterbildungsbudget</li></ul>',
            ],
            'designer' => [
                'name' => 'Produktdesigner (m/w/d)',
                'code' => 'produktdesigner',
                'preview_text' => 'Sie gestalten, wie unsere Kundschaft die App nutzt – von der ersten Skizze bis zur Veröffentlichung.',
                'description' => '<p>Sie sind Teil eines zweiköpfigen Designteams und arbeiten eng mit Produktmanagement und Entwicklung zusammen.</p>',
                'duties' => '<ul><li>Interviews mit Nutzerinnen und Nutzern führen und Prototypen testen</li><li>Das Designsystem pflegen</li></ul>',
                'requirements' => '<ul><li>Ein Portfolio mit veröffentlichten Produkten</li><li>Figma</li></ul>',
                'conditions' => '<ul><li>Arbeiten von überall im Land</li><li>Laptop und Monitor nach Wahl</li></ul>',
            ],
            'account' => [
                'name' => 'Kundenbetreuer (m/w/d)',
                'code' => 'kundenbetreuer',
                'preview_text' => 'Sie betreuen unsere Geschäftskunden und helfen ihnen, mehr aus dem Produkt herauszuholen.',
                'description' => '<p>Sie kümmern sich um rund dreißig eigene Kunden – mit einem Team, das Ihnen den Rücken stärkt.</p>',
                'duties' => '<ul><li>Neukunden beim Einstieg begleiten</li><li>Jeden Kunden mindestens einmal im Quartal treffen</li></ul>',
                'requirements' => '<ul><li>Mindestens ein Jahr Erfahrung im Vertrieb oder in der Kundenbetreuung</li><li>Ein klarer, freundlicher Schreibstil</li></ul>',
                'conditions' => '<ul><li>Bonus bei Vertragsverlängerungen</li><li>Ein ruhiges Büro mit guter Anbindung an öffentliche Verkehrsmittel</li></ul>',
            ],
            'intern' => [
                'name' => 'Praktikant im Kundensupport (m/w/d)',
                'code' => 'praktikum-kundensupport',
                'preview_text' => 'Drei Monate, zwanzig Stunden pro Woche, mit Mentor: ein guter Einstieg in die IT.',
                'description' => '<p>Sie beantworten Kundenfragen per E-Mail und Chat und lernen, wie das Produkt von innen funktioniert.</p>',
                'duties' => '<ul><li>Kundenanfragen per E-Mail und Chat beantworten</li><li>Häufige Fragen festhalten</li></ul>',
                'requirements' => '<ul><li>Studierende willkommen</li><li>Geduld und Freude daran, anderen zu helfen</li></ul>',
                'conditions' => '<ul><li>Flexible Arbeitszeiten neben dem Studium</li><li>Übernahmeangebot für die Besten</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => 'Einladung zum Gespräch',
                'subject' => 'Ihre Bewerbung: :vacancy',
                'body' => "Guten Tag :name,\n\nvielen Dank für Ihre Bewerbung auf die Stelle „:vacancy“. Wir würden Sie gern kennenlernen: Bitte nennen Sie uns zwei oder drei passende Termine in der nächsten Woche.\n\nMit freundlichen Grüßen\n:company",
            ],
            'rejection' => [
                'name' => 'Absage',
                'subject' => 'Ihre Bewerbung: :vacancy',
                'body' => "Guten Tag :name,\n\nvielen Dank für Ihr Interesse an der Stelle „:vacancy“. Diesmal haben wir uns für andere Bewerberinnen und Bewerber entschieden. Gern behalten wir Ihre Unterlagen und melden uns, wenn eine passende Stelle frei wird.\n\nMit freundlichen Grüßen\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            'Hallo! Ich arbeite seit fünf Jahren mit Laravel und würde sehr gern Teil des Teams werden.',
            'Ich arbeite seit sechs Jahren mit PHP und PostgreSQL, vor allem an APIs.',
            'Guten Tag! Den Link zu meinem Portfolio finden Sie im Lebenslauf.',
            'Gerade ist keine passende Stelle ausgeschrieben, aber ich würde gern in der Qualitätssicherung (QA) bei Ihnen arbeiten.',
        ],
    ],
];
