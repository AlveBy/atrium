<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '1C',
    'title_runs' => 'Troca com o 1C',

    'settings' => [
        'description' => 'O catálogo vem do 1C:Enterprise pela troca padrão CommerceML. No 1C, na troca com o site, informe este endereço: :url (uma configuração que pergunta apenas o site usa :bitrix depois do endereço do site) e, abaixo, o login e a senha definidos aqui.',
        'login' => 'Login',
        'login_hint' => 'Com o que o 1C faz login. A troca fica fechada até que o login e a senha estejam definidos.',
        'password' => 'Senha',
        'password_hint' => 'Armazenada criptografada e nunca mais exibida: para alterar, digite uma nova; deixe o campo vazio para manter a atual.',
    ],

    'fields' => [
        'started_at' => 'Início',
        'finished_at' => 'Fim',
        'file' => 'Arquivo',
        'status_label' => 'Resultado',
        'sections_created' => 'Novas seções',
        'sections_updated' => 'Seções atualizadas',
        'products_created' => 'Novos produtos',
        'products_updated' => 'Produtos atualizados',
        'offers_updated' => 'Preços e estoques atualizados',
        'skipped' => 'Ofertas ignoradas (produto inexistente)',
        'errors' => 'Erros',
        'error' => 'O que deu errado',
    ],

    'status' => [
        'running' => 'Em andamento',
        'done' => 'Concluído',
        'failed' => 'Falhou',
    ],

    'reply' => [
        'wrong_login' => 'Login ou senha incorretos, ou a troca não está configurada em Módulos → 1C.',
        'denied' => 'Sem login: inicie a troca novamente.',
        'unknown_type' => 'Tipo de troca desconhecido: :type.',
        'unknown_mode' => 'Etapa de troca desconhecida: :mode.',
        'bad_name' => 'Este nome de arquivo não é permitido.',
        'no_file' => 'O arquivo :file não chegou.',
        'progress' => 'Itens importados: :count.',
        'summary' => 'Seções: :sections_created novas, :sections_updated atualizadas. Produtos: :products_created novos, :products_updated atualizados. Preços e estoques: :offers. Ignoradas: :skipped. Erros: :errors.',
        'no_orders' => 'Os pedidos não são trocados: o módulo da loja não está instalado.',
    ],
];
