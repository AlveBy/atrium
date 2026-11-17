<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for Brazilian Portuguese: a made-up company in Brazil.
 *
 * `country` is used when Brazilian Portuguese is the main language of the
 * install: the offices, the people, the currency and the usual way to
 * state a salary there. `texts` are the vacancies in Brazilian Portuguese,
 * used whatever the main country is, so they name no city, currency or
 * law. Everything is fictional; e-mail addresses are the part before the
 * @, the domain is `domain`.
 */
return [
    'country' => [
        'code' => 'BR',
        'currency' => 'BRL',
        'domain' => 'example.com.br',
        'offices' => [
            ['name' => 'São Paulo', 'street' => 'Rua do Exemplo, 1200, conj. 304', 'postal_code' => '01310-000', 'region' => 'SP'],
            ['name' => 'Florianópolis', 'street' => 'Avenida Modelo, 400', 'postal_code' => '88010-000', 'region' => 'SC'],
        ],
        'contact' => ['name' => 'Mariana Costa', 'email' => 'vagas', 'phone' => '+55 11 0000-0142'],
        // Gross. A month is how salaries are stated in Brazil; interns get a
        // monthly allowance (bolsa).
        'salaries' => [
            'backend' => ['from' => 10000, 'to' => 14000, 'period' => 'month'],
            'designer' => ['from' => 8000, 'to' => 11000, 'period' => 'month'],
            'account' => null,
            'intern' => ['from' => 1800, 'period' => 'month'],
        ],
        'applicants' => [
            ['name' => 'Rafael Oliveira', 'email' => 'rafael.oliveira'],
            ['name' => 'Fernanda Lima', 'email' => 'f.lima'],
            ['name' => 'Beatriz Souza', 'email' => 'beatriz.souza'],
            ['name' => 'Lucas Pereira', 'email' => 'lucas.pereira'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => 'Engenharia',
            'design' => 'Design',
            'sales' => 'Vendas',
            'support' => 'Atendimento ao cliente',
        ],
        'vacancies' => [
            'backend' => [
                'name' => 'Desenvolvedor(a) back-end (PHP, Laravel)',
                'code' => 'desenvolvedor-back-end',
                'preview_text' => 'Construa os serviços por trás do nosso produto em um time pequeno que entrega toda semana.',
                'description' => '<p>Somos um time de oito desenvolvedores. Você vai cuidar das funcionalidades de ponta a ponta, do banco de dados à API.</p>',
                'duties' => '<ul><li>Projetar e desenvolver APIs REST</li><li>Revisar o código dos colegas</li><li>Manter os testes passando</li></ul>',
                'requirements' => '<ul><li>3 anos ou mais de experiência com PHP</li><li>Laravel, SQL, Git</li><li>Boa comunicação escrita</li></ul>',
                'conditions' => '<ul><li>Dois dias por semana no escritório</li><li>Plano de saúde</li><li>Verba anual para cursos</li></ul>',
            ],
            'designer' => [
                'name' => 'Designer de produto',
                'code' => 'designer-de-produto',
                'preview_text' => 'Defina como os clientes usam nosso aplicativo, do primeiro rascunho ao lançamento.',
                'description' => '<p>Nosso time de design tem duas pessoas, e você vai trabalhar lado a lado com gerentes de produto e desenvolvedores.</p>',
                'duties' => '<ul><li>Entrevistar usuários e testar protótipos</li><li>Manter o sistema de design em ordem</li></ul>',
                'requirements' => '<ul><li>Portfólio com produtos lançados</li><li>Figma</li></ul>',
                'conditions' => '<ul><li>Trabalho remoto de qualquer lugar do país</li><li>Notebook e monitor à sua escolha</li></ul>',
            ],
            'account' => [
                'name' => 'Gerente de contas',
                'code' => 'gerente-de-contas',
                'preview_text' => 'Acompanhe nossos clientes corporativos e ajude-os a aproveitar melhor o produto.',
                'description' => '<p>Você vai cuidar de cerca de trinta clientes e terá um time inteiro para dar apoio.</p>',
                'duties' => '<ul><li>Fazer a implantação de novos clientes</li><li>Encontrar cada cliente pelo menos uma vez por trimestre</li></ul>',
                'requirements' => '<ul><li>Um ano ou mais em vendas ou sucesso do cliente</li><li>Escrita clara e cordial</li></ul>',
                'conditions' => '<ul><li>Bônus por renovação</li><li>Escritório tranquilo, perto do transporte público</li></ul>',
            ],
            'intern' => [
                'name' => 'Estagiário(a) de atendimento ao cliente',
                'code' => 'estagio-atendimento',
                'preview_text' => 'Três meses, vinte horas por semana, com mentoria: um ótimo começo na área de tecnologia.',
                'description' => '<p>Você vai responder às dúvidas dos clientes por e-mail e chat e aprender como o produto funciona por dentro.</p>',
                'duties' => '<ul><li>Atender clientes por e-mail e chat</li><li>Registrar as dúvidas mais frequentes</li></ul>',
                'requirements' => '<ul><li>Estudantes são bem-vindos</li><li>Paciência e vontade de ajudar</li></ul>',
                'conditions' => '<ul><li>Horário flexível, que respeita seus estudos</li><li>Proposta de efetivação para os melhores estagiários</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => 'Convite para entrevista',
                'subject' => 'Sua candidatura para :vacancy',
                'body' => "Olá, :name!\n\nObrigado por se candidatar à vaga de :vacancy. Gostaríamos de conhecer você: responda com dois ou três horários que funcionem para você na próxima semana.\n\nAtenciosamente,\n:company",
            ],
            'rejection' => [
                'name' => 'Recusa',
                'subject' => 'Sua candidatura para :vacancy',
                'body' => "Olá, :name!\n\nObrigado pelo interesse na vaga de :vacancy. Desta vez, decidimos seguir com outros candidatos. Vamos manter seu currículo e entraremos em contato se surgir uma vaga adequada.\n\nAtenciosamente,\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            'Oi! Tenho cinco anos de experiência com Laravel e adoraria fazer parte do time.',
            'Trabalho com PHP e PostgreSQL há seis anos, principalmente com APIs.',
            'Boa tarde! O link do meu portfólio está no currículo.',
            'Ainda não há uma vaga para mim, mas eu gostaria de trabalhar com vocês como QA.',
        ],
    ],
];
