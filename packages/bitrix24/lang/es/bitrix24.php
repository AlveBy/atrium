<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Bitrix24',
    'title_links' => 'Bitrix24: formularios',

    'settings' => [
        'description' => 'Los envíos de los formularios llegan a su Bitrix24 como leads, y el widget de chat de Bitrix24 se muestra en el sitio. Cree en Bitrix24 un webhook entrante (Recursos para desarrolladores → Otros → Webhook entrante) con permiso para el CRM y pegue aquí su dirección.',
        'webhook' => 'Webhook entrante',
        'webhook_hint' => 'La dirección que muestra Bitrix24, del tipo https://portal.bitrix24.com/rest/1/code/. Se guarda cifrada y no se vuelve a mostrar: para cambiarla, pegue una nueva; deje el campo vacío para conservar la actual.',
        'send_forms' => 'Enviar los formularios a Bitrix24',
        'send_forms_hint' => 'Un formulario sin vínculo más abajo se envía como lead, con los campos colocados según su tipo.',
        'widget_code' => 'Código del widget de chat',
        'widget_code_hint' => 'El código de la línea abierta o del widget de chat de Bitrix24 (CRM → Centro de contacto → Chat en línea → Widget). Se coloca al final de cada página del sitio; si está vacío, no hay widget.',
        'check' => 'Comprobar la conexión',
        'connected' => 'Conectado: :portal, usuario :user.',
    ],

    'fields' => [
        'form' => 'Formulario',
        'mode' => 'Enviar como',
        'active' => 'Enviar este formulario a Bitrix24',
        'mapping' => 'Campos',
        'mapping_hint' => 'Una línea por campo: "campo del formulario = campo de Bitrix24", por ejemplo "phone = PHONE" o "message = COMMENTS". Un campo sin línea se coloca según su tipo (un correo en EMAIL, un teléfono en PHONE); lo que no tiene sitio va al comentario. Las líneas que empiezan por # se ignoran.',
    ],

    'modes' => [
        'lead' => 'Lead',
        'deal' => 'Negociación con un contacto',
    ],

    'errors' => [
        'no_webhook' => 'No se ha indicado el webhook de Bitrix24: péguelo en Módulos → Bitrix24.',
        'unreachable' => 'Bitrix24 no responde. Compruebe la dirección del portal en el webhook.',
        'portal' => 'Bitrix24 lo ha rechazado: :error',
        'mapping_line' => 'La línea :line no tiene el formato "campo del formulario = campo de Bitrix24".',
    ],

    'payload' => [
        'subject' => 'Envío de formulario n.º :id',
        'title' => 'Sitio web: :form',
        'visitor' => 'Visitante del sitio',
        'yes' => 'Sí',
        'lead' => 'Lead n.º :id creado.',
        'deal' => 'Negociación n.º :deal creada, contacto n.º :contact.',
        'gone' => 'El envío se eliminó antes de enviarse.',
    ],
];
