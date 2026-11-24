<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Bitrix24',
    'title_links' => 'Bitrix24: formulários',

    'settings' => [
        'description' => 'Os envios dos formulários chegam ao seu Bitrix24 como leads, e o widget de chat do Bitrix24 aparece no site. Crie no Bitrix24 um webhook de entrada (Recursos para desenvolvedores → Outros → Webhook de entrada) com permissão para o CRM e cole o endereço dele aqui.',
        'webhook' => 'Webhook de entrada',
        'webhook_hint' => 'O endereço que o Bitrix24 mostra, como https://portal.bitrix24.com/rest/1/code/. Ele é guardado criptografado e não é mais exibido: para trocá-lo, cole um novo; deixe o campo vazio para manter o atual.',
        'send_forms' => 'Enviar os formulários para o Bitrix24',
        'send_forms_hint' => 'Um formulário sem vínculo abaixo vai como lead, com os campos distribuídos conforme o tipo.',
        'widget_code' => 'Código do widget de chat',
        'widget_code_hint' => 'O código do canal aberto ou do widget de chat do Bitrix24 (CRM → Central de contatos → Chat online → Widget). Ele é colocado no fim de todas as páginas do site; vazio significa sem widget.',
        'check' => 'Verificar a conexão',
        'connected' => 'Conectado: :portal, usuário :user.',
    ],

    'fields' => [
        'form' => 'Formulário',
        'mode' => 'Enviar como',
        'active' => 'Enviar este formulário para o Bitrix24',
        'mapping' => 'Campos',
        'mapping_hint' => 'Uma linha por campo: "campo do formulário = campo do Bitrix24", por exemplo "phone = PHONE" ou "message = COMMENTS". Um campo sem linha vai conforme o tipo (e-mail para EMAIL, telefone para PHONE); o que não tem lugar vai para o comentário. Linhas que começam com # são ignoradas.',
    ],

    'modes' => [
        'lead' => 'Lead',
        'deal' => 'Negócio com um contato',
    ],

    'errors' => [
        'no_webhook' => 'O webhook do Bitrix24 não foi definido: cole-o em Módulos → Bitrix24.',
        'unreachable' => 'O Bitrix24 não responde. Verifique o endereço do portal no webhook.',
        'portal' => 'O Bitrix24 recusou: :error',
        'mapping_line' => 'A linha :line não está no formato "campo do formulário = campo do Bitrix24".',
    ],

    'payload' => [
        'subject' => 'Envio de formulário n.º :id',
        'title' => 'Site: :form',
        'visitor' => 'Visitante do site',
        'yes' => 'Sim',
        'lead' => 'Lead n.º :id criado.',
        'deal' => 'Negócio n.º :deal criado, contato n.º :contact.',
        'gone' => 'O envio foi excluído antes de ser enviado.',
    ],
];
