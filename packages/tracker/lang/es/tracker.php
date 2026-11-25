<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Seguimiento',
    'my_tasks' => 'Mis tareas',
    'hours_short' => 'h',
    'thread_empty' => 'Todavía no hay nada.',

    'projects' => [
        'title' => 'Proyectos',
        'add' => 'Añadir proyecto',
        'fields' => [
            'name' => 'Nombre',
            'key' => 'Clave',
            'key_hint' => 'De dos a diez mayúsculas latinas o dígitos, empezando por una letra: las tareas se numeran ATR-1, ATR-2 …',
            'description' => 'Descripción',
            'lead' => 'Responsable',
            'members' => 'Miembros',
            'members_hint' => 'Los administradores que trabajan en el proyecto. Una tarea solo puede asignarse a ellos y al responsable; déjelo vacío para permitir a todos.',
            'archived' => 'Archivado',
            'archived_hint' => 'Un proyecto archivado no recibe tareas nuevas; sus tareas se quedan.',
        ],
    ],

    'tasks' => [
        'title' => 'Tareas',
        'add' => 'Añadir tarea',
        'tabs' => [
            'params' => 'Tarea',
            'files' => 'Archivos',
            'activity' => 'Comentarios e historial',
        ],
        'fields' => [
            'code' => 'Tarea',
            'project' => 'Proyecto',
            'type' => 'Tipo',
            'title' => 'Título',
            'description' => 'Descripción',
            'status' => 'Estado',
            'priority' => 'Prioridad',
            'resolution' => 'Resolución',
            'assignee' => 'Asignado a',
            'parent' => 'Tarea padre',
            'parent_hint' => 'Una subtarea de una tarea, o una tarea de una épica, en el mismo proyecto.',
            'labels' => 'Etiquetas',
            'add_label' => 'Añadir etiqueta',
            'due_date' => 'Fecha límite',
            'estimate' => 'Estimación',
            'attachments' => 'Adjuntos',
            'new_comment' => 'Comentario nuevo',
            'new_comment_hint' => 'Se añade a la tarea al guardarla.',
            'thread' => 'Comentarios e historial',
        ],
        'filters' => [
            'label' => 'Etiqueta',
            'overdue' => 'Vencidas',
            'mine' => 'Mis tareas',
        ],
    ],

    'comments' => [
        'title' => 'Comentarios de tareas',
        'fields' => [
            'body' => 'Comentario',
            'task' => 'Tarea',
            'author' => 'Autor',
            'created' => 'Escrito',
        ],
    ],

    'terms' => [
        'types' => 'Tipos de tarea',
        'priorities' => 'Prioridades',
        'statuses' => 'Estados',
        'resolutions' => 'Resoluciones',
        'fields' => [
            'code' => 'Código',
            'code_hint' => 'Letras minúsculas, dígitos y _. Las tareas guardan el código, por eso no se puede cambiar después.',
            'name' => 'Nombre',
            'name_hint' => 'Vacío: el nombre integrado, en el idioma de cada administrador.',
            'sort' => 'Orden',
            'default' => 'Por defecto',
            'default_hint' => 'Una tarea nueva recibe este. Sin marca, el primero.',
            'category' => 'Categoría',
            'symbol' => 'Símbolo',
            'symbol_hint' => 'Se muestra antes del nombre, para que la prioridad no se distinga solo por el color.',
            'top_level' => 'Sin padre',
            'top_level_hint' => 'Las tareas de este tipo van en lo más alto, como una épica.',
            'needs_parent' => 'Necesita un padre',
            'needs_parent_hint' => 'Las tareas de este tipo siempre pertenecen a otra tarea, como una subtarea.',
        ],
        'categories' => [
            'todo' => 'Por hacer',
            'active' => 'En curso',
            'done' => 'Hecha',
        ],
    ],

    'types' => [
        'task' => 'Tarea',
        'bug' => 'Error',
        'story' => 'Historia',
        'epic' => 'Épica',
        'subtask' => 'Subtarea',
    ],

    'resolutions' => [
        'done' => 'Hecha',
        'wont_do' => 'No se hará',
        'duplicate' => 'Duplicada',
        'cannot_reproduce' => 'No se puede reproducir',
    ],

    'priorities' => [
        'lowest' => 'La más baja',
        'low' => 'Baja',
        'medium' => 'Media',
        'high' => 'Alta',
        'highest' => 'La más alta',
    ],

    'statuses' => [
        'todo' => 'Por hacer',
        'in_progress' => 'En curso',
        'review' => 'En revisión',
        'done' => 'Hecha',
    ],

    'kinds' => [
        'comment' => 'Comentario',
        'created' => 'Creada',
        'status' => 'Estado',
        'priority' => 'Prioridad',
        'assignee' => 'Asignado a',
    ],

    'events' => [
        'created' => 'La tarea se creó.',
        'status' => 'Estado: :old → :new',
        'priority' => 'Prioridad: :old → :new',
        'assignee' => 'Asignado a: :old → :new',
    ],

    'errors' => [
        'archived' => 'El proyecto está archivado: no se pueden mover tareas a él.',
        'not_in_project' => 'Este administrador no trabaja en el proyecto.',
        'type_has_no_parent' => 'Una tarea de este tipo no tiene tarea padre.',
        'needs_parent' => 'Una tarea de este tipo necesita una tarea padre.',
        'in_use' => 'Todavía hay tareas que lo usan: cámbielas primero.',
        'parent_other_project' => 'La tarea padre debe ser del mismo proyecto.',
        'parent_loop' => 'Una tarea no puede ser hija de sí misma ni de una de sus subtareas.',
    ],

    'settings' => [
        'description' => 'Los correos sobre las tareas.',
        'notify_assigned' => 'Escribir al asignado',
        'notify_assigned_hint' => 'Un correo cuando se asigna una tarea a un administrador.',
        'notify_status' => 'Escribir sobre un estado nuevo',
        'notify_status_hint' => 'Un correo al asignado cuando cambia el estado de su tarea.',
        'disabled_plugins' => 'Complementos desactivados',
        'disabled_plugins_hint' => 'Los complementos marcados se ignoran; uno nuevo está activo hasta que lo marque.',
    ],

    'mail' => [
        'assigned_subject' => 'Se le asignó :code: :title',
        'assigned_line' => 'Se le asignó la tarea :code «:title».',
        'status_subject' => ':code ahora está «:status»',
        'status_line' => 'El estado de :code «:title» cambió: :old → :new.',
    ],
];
