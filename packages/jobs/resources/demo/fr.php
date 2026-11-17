<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for French: a made-up company in France.
 *
 * `country` is used when French is the main language of the install: the
 * offices, the people, the currency and the usual way to state a salary
 * there. `texts` are the vacancies in French, used whatever the main
 * country is, so they name no city, currency or law. Everything is
 * fictional; e-mail addresses are the part before the @, the domain is
 * `domain`.
 */
return [
    'country' => [
        'code' => 'FR',
        'currency' => 'EUR',
        'domain' => 'example.fr',
        'offices' => [
            ['name' => 'Paris', 'street' => '12 rue de l\'Exemple', 'postal_code' => '75011', 'region' => null],
            ['name' => 'Lyon', 'street' => '7 avenue du Modèle', 'postal_code' => '69003', 'region' => null],
        ],
        'contact' => ['name' => 'Camille Laurent', 'email' => 'rh', 'phone' => '+33 1 00 00 01 42'],
        // Gross. A year is how salaries are stated in France (brut annuel);
        // interns get a monthly allowance.
        'salaries' => [
            'backend' => ['from' => 45000, 'to' => 55000, 'period' => 'year'],
            'designer' => ['from' => 40000, 'to' => 50000, 'period' => 'year'],
            'account' => null,
            'intern' => ['from' => 800, 'period' => 'month'],
        ],
        'applicants' => [
            ['name' => 'Thomas Girard', 'email' => 'thomas.girard'],
            ['name' => 'Léa Fontaine', 'email' => 'l.fontaine'],
            ['name' => 'Manon Dubois', 'email' => 'manon.dubois'],
            ['name' => 'Hugo Lefèvre', 'email' => 'hugo.lefevre'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => 'Développement',
            'design' => 'Design',
            'sales' => 'Commercial',
            'support' => 'Support client',
        ],
        'vacancies' => [
            'backend' => [
                'name' => 'Développeur back-end PHP/Laravel (H/F)',
                'code' => 'developpeur-back-end',
                'preview_text' => 'Développez les services qui font tourner notre produit, au sein d\'une petite équipe qui livre chaque semaine.',
                'description' => '<p>Notre équipe compte huit développeurs. Vous prendrez en charge des fonctionnalités de bout en bout, de la base de données à l\'API.</p>',
                'duties' => '<ul><li>Concevoir et développer des API REST</li><li>Relire le code de vos collègues</li><li>Veiller à ce que les tests restent au vert</li></ul>',
                'requirements' => '<ul><li>Au moins 3 ans d\'expérience en PHP</li><li>Laravel, SQL, Git</li><li>Une très bonne expression écrite</li></ul>',
                'conditions' => '<ul><li>Deux jours par semaine au bureau</li><li>Une complémentaire santé</li><li>Un budget formation annuel</li></ul>',
            ],
            'designer' => [
                'name' => 'Designer produit (H/F)',
                'code' => 'designer-produit',
                'preview_text' => 'Imaginez la façon dont nos clients utilisent notre application, du premier croquis à la mise en production.',
                'description' => '<p>Au sein d\'une équipe design de deux personnes, vous travaillerez main dans la main avec les chefs de produit et les développeurs.</p>',
                'duties' => '<ul><li>Mener des entretiens utilisateurs et tester des prototypes</li><li>Faire vivre le système de design</li></ul>',
                'requirements' => '<ul><li>Un portfolio avec des produits en production</li><li>Figma</li></ul>',
                'conditions' => '<ul><li>Télétravail depuis n\'importe où dans le pays</li><li>L\'ordinateur portable et l\'écran de votre choix</li></ul>',
            ],
            'account' => [
                'name' => 'Chargé de clientèle (H/F)',
                'code' => 'charge-de-clientele',
                'preview_text' => 'Accompagnez nos clients professionnels et aidez-les à tirer le meilleur du produit.',
                'description' => '<p>Vous suivrez une trentaine de clients en propre, avec une équipe sur qui compter.</p>',
                'duties' => '<ul><li>Accueillir et prendre en main les nouveaux clients</li><li>Rencontrer chaque client au moins une fois par trimestre</li></ul>',
                'requirements' => '<ul><li>Au moins un an d\'expérience en vente ou en relation client</li><li>Une écriture claire et chaleureuse</li></ul>',
                'conditions' => '<ul><li>Une prime sur les renouvellements</li><li>Des bureaux calmes, proches des transports en commun</li></ul>',
            ],
            'intern' => [
                'name' => 'Stagiaire support client (H/F)',
                'code' => 'stage-support-client',
                'preview_text' => 'Trois mois, vingt heures par semaine, avec un tuteur : un bon tremplin dans l\'informatique.',
                'description' => '<p>Vous répondrez aux questions des clients par e-mail et par chat, et découvrirez le fonctionnement du produit de l\'intérieur.</p>',
                'duties' => '<ul><li>Répondre aux clients par e-mail et par chat</li><li>Recenser les questions qui reviennent souvent</li></ul>',
                'requirements' => '<ul><li>Étudiants bienvenus</li><li>De la patience et l\'envie d\'aider</li></ul>',
                'conditions' => '<ul><li>Des horaires souples, compatibles avec vos études</li><li>Une proposition d\'embauche pour les meilleurs stagiaires</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => 'Invitation à un entretien',
                'subject' => 'Votre candidature au poste de :vacancy',
                'body' => "Bonjour :name,\n\nMerci pour votre candidature au poste de :vacancy. Nous aimerions vous rencontrer : pourriez-vous nous proposer deux ou trois créneaux qui vous conviennent la semaine prochaine ?\n\nBien cordialement,\n:company",
            ],
            'rejection' => [
                'name' => 'Refus',
                'subject' => 'Votre candidature au poste de :vacancy',
                'body' => "Bonjour :name,\n\nMerci de l'intérêt que vous portez au poste de :vacancy. Nous avons choisi de poursuivre avec d'autres candidats cette fois-ci. Nous conservons votre CV et reviendrons vers vous si un poste correspondant se libère.\n\nBien cordialement,\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            'Bonjour ! J\'ai cinq ans d\'expérience avec Laravel et j\'aimerais beaucoup rejoindre l\'équipe.',
            'Je travaille avec PHP et PostgreSQL depuis six ans, surtout sur des API.',
            'Bonjour ! Le lien vers mon portfolio se trouve dans mon CV.',
            'Aucune offre ne correspond pour l\'instant, mais j\'aimerais travailler chez vous comme ingénieur QA.',
        ],
    ],
];
