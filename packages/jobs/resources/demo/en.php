<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for English: a made-up company in the United States.
 *
 * `country` is used when English is the main language of the install: the
 * offices, the people, the currency and the usual way to state a salary
 * there. `texts` are the vacancies in English, used whatever the main
 * country is, so they name no city, currency or law. Everything is
 * fictional; e-mail addresses are the part before the @, the domain is
 * `domain`.
 */
return [
    'country' => [
        'code' => 'US',
        'currency' => 'USD',
        'domain' => 'example.com',
        'offices' => [
            ['name' => 'Portland', 'street' => '1200 Example Avenue, Suite 300', 'postal_code' => '97201', 'region' => 'OR'],
            ['name' => 'Austin', 'street' => '400 Sample Street', 'postal_code' => '78701', 'region' => 'TX'],
        ],
        'contact' => ['name' => 'Alex Morgan', 'email' => 'jobs', 'phone' => '+1 503 555 0142'],
        // Gross. A year is how salaries are stated in the US; interns by the hour.
        'salaries' => [
            'backend' => ['from' => 110000, 'to' => 140000, 'period' => 'year'],
            'designer' => ['from' => 85000, 'to' => 105000, 'period' => 'year'],
            'account' => null,
            'intern' => ['from' => 20, 'period' => 'hour'],
        ],
        'applicants' => [
            ['name' => 'Jamie Sample', 'email' => 'jamie.sample'],
            ['name' => 'Taylor Brooks', 'email' => 't.brooks'],
            ['name' => 'Jordan Reyes', 'email' => 'jordan.reyes'],
            ['name' => 'Casey Holt', 'email' => 'casey.holt'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => 'Engineering',
            'design' => 'Design',
            'sales' => 'Sales',
            'support' => 'Customer support',
        ],
        'vacancies' => [
            'backend' => [
                'name' => 'Backend developer (PHP, Laravel)',
                'code' => 'backend-developer',
                'preview_text' => 'Build the services behind our product with a small team that ships every week.',
                'description' => '<p>We are a team of eight developers. You will own features from the database to the API.</p>',
                'duties' => '<ul><li>Design and build REST APIs</li><li>Review your colleagues\' code</li><li>Keep the test suite green</li></ul>',
                'requirements' => '<ul><li>3+ years of PHP</li><li>Laravel, SQL, Git</li><li>Good written English</li></ul>',
                'conditions' => '<ul><li>Two office days a week</li><li>Health insurance</li><li>A yearly learning budget</li></ul>',
            ],
            'designer' => [
                'name' => 'Product designer',
                'code' => 'product-designer',
                'preview_text' => 'Shape how customers use our app, from the first sketch to the release.',
                'description' => '<p>You will be one of two designers, working closely with product managers and developers.</p>',
                'duties' => '<ul><li>Interview users and test prototypes</li><li>Keep the design system in order</li></ul>',
                'requirements' => '<ul><li>A portfolio with shipped products</li><li>Figma</li></ul>',
                'conditions' => '<ul><li>Work from anywhere in the country</li><li>A laptop and a monitor of your choice</li></ul>',
            ],
            'account' => [
                'name' => 'Account manager',
                'code' => 'account-manager',
                'preview_text' => 'Look after our business customers and help them get more out of the product.',
                'description' => '<p>You will have about thirty customers of your own and a team that backs you up.</p>',
                'duties' => '<ul><li>Onboard new customers</li><li>Meet every customer at least once a quarter</li></ul>',
                'requirements' => '<ul><li>One year or more in sales or customer success</li><li>Clear, friendly writing</li></ul>',
                'conditions' => '<ul><li>A bonus on renewals</li><li>A quiet office near public transport</li></ul>',
            ],
            'intern' => [
                'name' => 'Customer support intern',
                'code' => 'support-intern',
                'preview_text' => 'Three months, twenty hours a week, with a mentor: a good start in IT.',
                'description' => '<p>You will answer customers\' questions by e-mail and chat and learn how the product works inside.</p>',
                'duties' => '<ul><li>Answer customers by e-mail and chat</li><li>Write down the questions that come up often</li></ul>',
                'requirements' => '<ul><li>Students welcome</li><li>Patience and a wish to help</li></ul>',
                'conditions' => '<ul><li>Flexible hours around your studies</li><li>A job offer for the best interns</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => 'Interview invitation',
                'subject' => 'Your application for :vacancy',
                'body' => "Hello :name,\n\nthank you for applying for :vacancy. We would like to meet you: please reply with two or three times that suit you next week.\n\nBest regards,\n:company",
            ],
            'rejection' => [
                'name' => 'Rejection',
                'subject' => 'Your application for :vacancy',
                'body' => "Hello :name,\n\nthank you for your interest in :vacancy. We have decided to continue with other candidates this time. We will keep your CV and contact you if a suitable position opens.\n\nBest regards,\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            'Hi! I have five years of Laravel behind me and would love to join the team.',
            'I have worked with PHP and PostgreSQL for six years, mostly on APIs.',
            'Good afternoon! My portfolio is linked in the CV.',
            'No vacancy fits yet, but I would like to work with you as a QA engineer.',
        ],
    ],
];
