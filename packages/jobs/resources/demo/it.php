<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for Italian: a made-up company in Italy.
 *
 * `country` is used when Italian is the main language of the install: the
 * offices, the people, the currency and the usual way to state a salary
 * there. `texts` are the vacancies in Italian, used whatever the main
 * country is, so they name no city, currency or law. Everything is
 * fictional; e-mail addresses are the part before the @, the domain is
 * `domain`.
 */
return [
    'country' => [
        'code' => 'IT',
        'currency' => 'EUR',
        'domain' => 'example.it',
        'offices' => [
            ['name' => 'Milano', 'street' => 'Via dell\'Esempio, 12', 'postal_code' => '20121', 'region' => 'MI'],
            ['name' => 'Bologna', 'street' => 'Viale del Modello, 7', 'postal_code' => '40121', 'region' => 'BO'],
        ],
        'contact' => ['name' => 'Giulia Moretti', 'email' => 'carriere', 'phone' => '+39 02 0000 0142'],
        // Gross. A year is how salaries are stated in Italy (RAL); interns get
        // a monthly allowance.
        'salaries' => [
            'backend' => ['from' => 35000, 'to' => 45000, 'period' => 'year'],
            'designer' => ['from' => 30000, 'to' => 40000, 'period' => 'year'],
            'account' => null,
            'intern' => ['from' => 800, 'period' => 'month'],
        ],
        'applicants' => [
            ['name' => 'Marco Rinaldi', 'email' => 'marco.rinaldi'],
            ['name' => 'Chiara Colombo', 'email' => 'c.colombo'],
            ['name' => 'Francesca Galli', 'email' => 'francesca.galli'],
            ['name' => 'Davide Bruno', 'email' => 'davide.bruno'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => 'Sviluppo',
            'design' => 'Design',
            'sales' => 'Vendite',
            'support' => 'Assistenza clienti',
        ],
        'vacancies' => [
            'backend' => [
                'name' => 'Sviluppatore backend (PHP, Laravel)',
                'code' => 'sviluppatore-backend',
                'preview_text' => 'Sviluppa i servizi alla base del nostro prodotto in un piccolo team che rilascia ogni settimana.',
                'description' => '<p>Siamo un team di otto sviluppatori. Seguirai le funzionalità dall\'inizio alla fine, dal database alle API.</p>',
                'duties' => '<ul><li>Progettare e sviluppare API REST</li><li>Revisionare il codice dei colleghi</li><li>Mantenere verdi i test</li></ul>',
                'requirements' => '<ul><li>Almeno 3 anni di esperienza con PHP</li><li>Laravel, SQL, Git</li><li>Ottime capacità di scrittura</li></ul>',
                'conditions' => '<ul><li>Due giorni a settimana in ufficio</li><li>Assicurazione sanitaria integrativa</li><li>Budget annuale per la formazione</li></ul>',
            ],
            'designer' => [
                'name' => 'Designer di prodotto',
                'code' => 'designer-di-prodotto',
                'preview_text' => 'Definisci come i clienti usano la nostra app, dal primo schizzo al rilascio.',
                'description' => '<p>Farai parte di un team di design di due persone e lavorerai a stretto contatto con i responsabili di prodotto e gli sviluppatori.</p>',
                'duties' => '<ul><li>Intervistare gli utenti e testare i prototipi</li><li>Tenere in ordine il sistema di design</li></ul>',
                'requirements' => '<ul><li>Un portfolio con prodotti rilasciati</li><li>Figma</li></ul>',
                'conditions' => '<ul><li>Lavoro da remoto da qualsiasi luogo del Paese</li><li>Portatile e monitor a tua scelta</li></ul>',
            ],
            'account' => [
                'name' => 'Responsabile clienti',
                'code' => 'responsabile-clienti',
                'preview_text' => 'Segui i nostri clienti aziendali e aiutali a ottenere di più dal prodotto.',
                'description' => '<p>Avrai circa trenta clienti tuoi e un team pronto a darti supporto.</p>',
                'duties' => '<ul><li>Accompagnare i nuovi clienti nell\'avvio</li><li>Incontrare ogni cliente almeno una volta a trimestre</li></ul>',
                'requirements' => '<ul><li>Almeno un anno di esperienza nelle vendite o nell\'assistenza clienti</li><li>Scrittura chiara e cordiale</li></ul>',
                'conditions' => '<ul><li>Bonus sui rinnovi</li><li>Un ufficio tranquillo vicino ai mezzi pubblici</li></ul>',
            ],
            'intern' => [
                'name' => 'Stagista assistenza clienti',
                'code' => 'stage-assistenza-clienti',
                'preview_text' => 'Tre mesi, venti ore a settimana, con un tutor: un ottimo inizio nell\'informatica.',
                'description' => '<p>Risponderai alle domande dei clienti via e-mail e chat e scoprirai come funziona il prodotto dall\'interno.</p>',
                'duties' => '<ul><li>Rispondere ai clienti via e-mail e chat</li><li>Annotare le domande più frequenti</li></ul>',
                'requirements' => '<ul><li>Aperto a studenti</li><li>Pazienza e voglia di aiutare</li></ul>',
                'conditions' => '<ul><li>Orari flessibili compatibili con lo studio</li><li>Proposta di assunzione per i migliori stagisti</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => 'Invito al colloquio',
                'subject' => 'La sua candidatura per :vacancy',
                'body' => "Gentile :name,\n\nla ringraziamo per la candidatura alla posizione di :vacancy. Vorremmo conoscerla: ci indichi, per favore, due o tre orari che le vanno bene la prossima settimana.\n\nCordiali saluti,\n:company",
            ],
            'rejection' => [
                'name' => 'Esito negativo',
                'subject' => 'La sua candidatura per :vacancy',
                'body' => "Gentile :name,\n\nla ringraziamo per l'interesse mostrato per la posizione di :vacancy. Questa volta abbiamo deciso di proseguire con altri candidati. Conserveremo il suo CV e la ricontatteremo se si aprirà una posizione adatta.\n\nCordiali saluti,\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            'Ciao! Ho cinque anni di esperienza con Laravel e mi piacerebbe molto entrare nel team.',
            'Lavoro con PHP e PostgreSQL da sei anni, soprattutto su API.',
            'Buongiorno! Il link al mio portfolio è nel CV.',
            'Al momento non c\'è una posizione adatta, ma mi piacerebbe lavorare con voi come tester QA.',
        ],
    ],
];
