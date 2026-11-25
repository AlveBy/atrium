<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Rastreador',
    'my_tasks' => 'Minhas tarefas',
    'hours_short' => 'h',
    'thread_empty' => 'Ainda não há nada.',

    'projects' => [
        'title' => 'Projetos',
        'add' => 'Adicionar projeto',
        'fields' => [
            'name' => 'Nome',
            'key' => 'Chave',
            'key_hint' => 'De duas a dez letras latinas maiúsculas ou dígitos, começando por uma letra: as tarefas são numeradas ATR-1, ATR-2 …',
            'description' => 'Descrição',
            'lead' => 'Responsável',
            'members' => 'Membros',
            'members_hint' => 'Os administradores que trabalham no projeto. Uma tarefa só pode ser atribuída a eles e ao responsável; deixe vazio para permitir todos.',
            'archived' => 'Arquivado',
            'archived_hint' => 'Um projeto arquivado não recebe tarefas novas; as tarefas dele ficam.',
        ],
    ],

    'tasks' => [
        'title' => 'Tarefas',
        'add' => 'Adicionar tarefa',
        'tabs' => [
            'params' => 'Tarefa',
            'files' => 'Arquivos',
            'activity' => 'Comentários e histórico',
        ],
        'fields' => [
            'code' => 'Tarefa',
            'project' => 'Projeto',
            'type' => 'Tipo',
            'title' => 'Título',
            'description' => 'Descrição',
            'status' => 'Status',
            'priority' => 'Prioridade',
            'resolution' => 'Resolução',
            'assignee' => 'Responsável pela tarefa',
            'parent' => 'Tarefa pai',
            'parent_hint' => 'Uma subtarefa de uma tarefa, ou uma tarefa de um épico, no mesmo projeto.',
            'labels' => 'Etiquetas',
            'add_label' => 'Adicionar etiqueta',
            'due_date' => 'Prazo',
            'estimate' => 'Estimativa',
            'attachments' => 'Anexos',
            'new_comment' => 'Novo comentário',
            'new_comment_hint' => 'Adicionado à tarefa quando você salvar.',
            'thread' => 'Comentários e histórico',
        ],
        'filters' => [
            'label' => 'Etiqueta',
            'overdue' => 'Atrasadas',
            'mine' => 'Minhas tarefas',
        ],
    ],

    'comments' => [
        'title' => 'Comentários das tarefas',
        'fields' => [
            'body' => 'Comentário',
            'task' => 'Tarefa',
            'author' => 'Autor',
            'created' => 'Escrito em',
        ],
    ],

    'terms' => [
        'types' => 'Tipos de tarefa',
        'priorities' => 'Prioridades',
        'statuses' => 'Status',
        'resolutions' => 'Resoluções',
        'fields' => [
            'code' => 'Código',
            'code_hint' => 'Letras minúsculas, dígitos e _. As tarefas guardam o código, por isso ele não pode ser alterado depois.',
            'name' => 'Nome',
            'name_hint' => 'Vazio: o nome embutido, no idioma de cada administrador.',
            'sort' => 'Ordem',
            'default' => 'Padrão',
            'default_hint' => 'Uma tarefa nova recebe este. Sem marca, o primeiro.',
            'category' => 'Categoria',
            'symbol' => 'Símbolo',
            'symbol_hint' => 'Exibido antes do nome, para que a prioridade não se distinga só pela cor.',
            'top_level' => 'Sem pai',
            'top_level_hint' => 'As tarefas deste tipo ficam no topo, como um épico.',
            'needs_parent' => 'Precisa de um pai',
            'needs_parent_hint' => 'As tarefas deste tipo sempre pertencem a outra tarefa, como uma subtarefa.',
        ],
        'categories' => [
            'todo' => 'A fazer',
            'active' => 'Em andamento',
            'done' => 'Concluída',
        ],
    ],

    'types' => [
        'task' => 'Tarefa',
        'bug' => 'Bug',
        'story' => 'História',
        'epic' => 'Épico',
        'subtask' => 'Subtarefa',
    ],

    'resolutions' => [
        'done' => 'Concluída',
        'wont_do' => 'Não será feita',
        'duplicate' => 'Duplicada',
        'cannot_reproduce' => 'Não reproduzível',
    ],

    'priorities' => [
        'lowest' => 'Mais baixa',
        'low' => 'Baixa',
        'medium' => 'Média',
        'high' => 'Alta',
        'highest' => 'Mais alta',
    ],

    'statuses' => [
        'todo' => 'A fazer',
        'in_progress' => 'Em andamento',
        'review' => 'Em revisão',
        'done' => 'Concluída',
    ],

    'kinds' => [
        'comment' => 'Comentário',
        'created' => 'Criada',
        'status' => 'Status',
        'priority' => 'Prioridade',
        'assignee' => 'Responsável pela tarefa',
    ],

    'events' => [
        'created' => 'A tarefa foi criada.',
        'status' => 'Status: :old → :new',
        'priority' => 'Prioridade: :old → :new',
        'assignee' => 'Responsável pela tarefa: :old → :new',
    ],

    'errors' => [
        'archived' => 'O projeto está arquivado: não é possível mover tarefas para ele.',
        'not_in_project' => 'Este administrador não trabalha no projeto.',
        'type_has_no_parent' => 'Uma tarefa deste tipo não tem tarefa pai.',
        'needs_parent' => 'Uma tarefa deste tipo precisa de uma tarefa pai.',
        'in_use' => 'Ainda há tarefas que usam isto: altere-as primeiro.',
        'parent_other_project' => 'A tarefa pai deve ser do mesmo projeto.',
        'parent_loop' => 'Uma tarefa não pode ser filha de si mesma nem de uma de suas subtarefas.',
    ],

    'settings' => [
        'description' => 'Os e-mails sobre as tarefas.',
        'notify_assigned' => 'Escrever ao responsável',
        'notify_assigned_hint' => 'Um e-mail quando uma tarefa é atribuída a um administrador.',
        'notify_status' => 'Escrever sobre um novo status',
        'notify_status_hint' => 'Um e-mail ao responsável quando o status da tarefa dele muda.',
        'disabled_plugins' => 'Plugins desativados',
        'disabled_plugins_hint' => 'Os plugins marcados são ignorados; um plugin novo fica ativo até você marcá-lo.',
    ],

    'mail' => [
        'assigned_subject' => ':code foi atribuída a você: :title',
        'assigned_line' => 'A tarefa :code «:title» foi atribuída a você.',
        'status_subject' => ':code agora está «:status»',
        'status_line' => 'O status de :code «:title» mudou: :old → :new.',
    ],
];
