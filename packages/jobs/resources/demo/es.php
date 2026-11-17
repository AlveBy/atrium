<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for Spanish: a made-up company in Spain.
 *
 * `country` is used when Spanish is the main language of the install: the
 * offices, the people, the currency and the usual way to state a salary
 * there. `texts` are the vacancies in Spanish, used whatever the main
 * country is, so they name no city, currency or law. Everything is
 * fictional; e-mail addresses are the part before the @, the domain is
 * `domain`.
 */
return [
    'country' => [
        'code' => 'ES',
        'currency' => 'EUR',
        'domain' => 'example.es',
        'offices' => [
            ['name' => 'Madrid', 'street' => 'Calle del Ejemplo, 12, 3.º B', 'postal_code' => '28010', 'region' => 'Madrid'],
            ['name' => 'Valencia', 'street' => 'Avenida del Modelo, 7', 'postal_code' => '46002', 'region' => 'Valencia'],
        ],
        'contact' => ['name' => 'Lucía Romero', 'email' => 'empleo', 'phone' => '+34 910 000 142'],
        // Gross. A year is how salaries are stated in Spain (bruto anual);
        // interns get a monthly grant.
        'salaries' => [
            'backend' => ['from' => 38000, 'to' => 48000, 'period' => 'year'],
            'designer' => ['from' => 32000, 'to' => 42000, 'period' => 'year'],
            'account' => null,
            'intern' => ['from' => 700, 'period' => 'month'],
        ],
        'applicants' => [
            ['name' => 'Javier Morales', 'email' => 'javier.morales'],
            ['name' => 'Carmen Navarro', 'email' => 'c.navarro'],
            ['name' => 'Laura Ortega', 'email' => 'laura.ortega'],
            ['name' => 'Pablo Serrano', 'email' => 'pablo.serrano'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => 'Desarrollo',
            'design' => 'Diseño',
            'sales' => 'Ventas',
            'support' => 'Atención al cliente',
        ],
        'vacancies' => [
            'backend' => [
                'name' => 'Desarrollador/a backend (PHP, Laravel)',
                'code' => 'desarrollador-backend',
                'preview_text' => 'Desarrolla los servicios que hay detrás de nuestro producto en un equipo pequeño que publica cada semana.',
                'description' => '<p>Somos un equipo de ocho desarrolladores. Te encargarás de funcionalidades completas, desde la base de datos hasta la API.</p>',
                'duties' => '<ul><li>Diseñar y desarrollar API REST</li><li>Revisar el código de tus compañeros</li><li>Mantener las pruebas en verde</li></ul>',
                'requirements' => '<ul><li>Más de 3 años de experiencia con PHP</li><li>Laravel, SQL, Git</li><li>Buena redacción</li></ul>',
                'conditions' => '<ul><li>Dos días a la semana en la oficina</li><li>Seguro médico</li><li>Presupuesto anual para formación</li></ul>',
            ],
            'designer' => [
                'name' => 'Diseñador/a de producto',
                'code' => 'disenador-de-producto',
                'preview_text' => 'Define cómo usan nuestros clientes la aplicación, desde el primer boceto hasta el lanzamiento.',
                'description' => '<p>Formarás parte de un equipo de diseño de dos personas y trabajarás codo con codo con producto y desarrollo.</p>',
                'duties' => '<ul><li>Entrevistar a usuarios y probar prototipos</li><li>Mantener en orden el sistema de diseño</li></ul>',
                'requirements' => '<ul><li>Un portfolio con productos lanzados</li><li>Figma</li></ul>',
                'conditions' => '<ul><li>Teletrabajo desde cualquier lugar del país</li><li>Portátil y monitor a tu elección</li></ul>',
            ],
            'account' => [
                'name' => 'Gestor/a de cuentas',
                'code' => 'gestor-de-cuentas',
                'preview_text' => 'Acompaña a nuestros clientes empresariales y ayúdales a sacar más partido al producto.',
                'description' => '<p>Llevarás una cartera de unos treinta clientes propios, con un equipo que te respalda.</p>',
                'duties' => '<ul><li>Dar la bienvenida a los nuevos clientes y ponerlos en marcha</li><li>Reunirte con cada cliente al menos una vez por trimestre</li></ul>',
                'requirements' => '<ul><li>Al menos un año de experiencia en ventas o atención al cliente</li><li>Escritura clara y cercana</li></ul>',
                'conditions' => '<ul><li>Incentivos por renovaciones</li><li>Oficina tranquila y bien comunicada en transporte público</li></ul>',
            ],
            'intern' => [
                'name' => 'Becario/a de atención al cliente',
                'code' => 'becario-atencion-al-cliente',
                'preview_text' => 'Tres meses, veinte horas a la semana y con mentor: un buen comienzo en el sector tecnológico.',
                'description' => '<p>Responderás las preguntas de los clientes por correo y chat, y aprenderás cómo funciona el producto por dentro.</p>',
                'duties' => '<ul><li>Atender a los clientes por correo y chat</li><li>Anotar las preguntas más frecuentes</li></ul>',
                'requirements' => '<ul><li>Abierto a estudiantes</li><li>Paciencia y ganas de ayudar</li></ul>',
                'conditions' => '<ul><li>Horario flexible compatible con tus estudios</li><li>Oferta de empleo para los mejores becarios</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => 'Invitación a entrevista',
                'subject' => 'Tu candidatura para :vacancy',
                'body' => "Hola, :name:\n\nGracias por presentar tu candidatura para :vacancy. Nos gustaría conocerte: respóndenos con dos o tres horarios que te vengan bien la próxima semana.\n\nUn saludo,\n:company",
            ],
            'rejection' => [
                'name' => 'Descarte',
                'subject' => 'Tu candidatura para :vacancy',
                'body' => "Hola, :name:\n\nGracias por tu interés en :vacancy. En esta ocasión hemos decidido seguir adelante con otras candidaturas. Guardaremos tu CV y te escribiremos si se abre un puesto que encaje contigo.\n\nUn saludo,\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            '¡Hola! Llevo cinco años trabajando con Laravel y me encantaría unirme al equipo.',
            'Trabajo con PHP y PostgreSQL desde hace seis años, sobre todo en API.',
            '¡Buenas tardes! El enlace a mi portfolio está en el CV.',
            'Ahora mismo no hay ninguna oferta que encaje, pero me gustaría trabajar con vosotros como ingeniero de QA.',
        ],
    ],
];
