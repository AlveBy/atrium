<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Integrações',
    'title_log' => 'Registro de trocas',

    'status' => [
        'ok' => 'Concluído',
        'retry' => 'Falhou, nova tentativa em breve',
        'failed' => 'Falhou',
    ],
    'direction' => [
        'out' => 'Do site',
        'in' => 'Para o site',
    ],
    'fields' => [
        'when' => 'Quando',
        'integration' => 'Integração',
        'direction' => 'Direção',
        'action' => 'Ação',
        'subject' => 'Assunto',
        'status' => 'Resultado',
        'message' => 'O que foi feito',
        'error' => 'Erro',
        'attempt' => 'Tentativa',
        'duration' => 'Tempo, ms',
    ],

    'settings' => [
        'description' => 'Quem é avisado quando um conector (1C, Bitrix24) desiste após a última tentativa e por quanto tempo o registro de trocas é mantido.',
        'alert_emails' => 'E-mails de alerta',
        'alert_emails_hint' => 'Um ou mais endereços, separados por vírgulas ou quebras de linha. Vazio: todos os superadministradores ativos. No máximo um e-mail por integração por hora.',
        'retention_days' => 'Manter o registro por, dias',
        'retention_days_hint' => 'Entradas mais antigas são excluídas toda noite.',
    ],

    'alert' => [
        'subject' => ':site: a integração :integration falhou',
        'what' => 'A integração :integration não conseguiu executar a ação :action e parou de tentar.',
        'subject_line' => 'Assunto: :subject',
        'error' => 'Erro: :error',
        'attempts' => 'Tentativas feitas: :count',
    ],
];
