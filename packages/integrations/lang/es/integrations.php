<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Integraciones',
    'title_log' => 'Registro de intercambio',

    'status' => [
        'ok' => 'Hecho',
        'retry' => 'Error, se reintentará',
        'failed' => 'Error',
    ],
    'direction' => [
        'out' => 'Desde el sitio',
        'in' => 'Hacia el sitio',
    ],
    'fields' => [
        'when' => 'Cuándo',
        'integration' => 'Integración',
        'direction' => 'Dirección',
        'action' => 'Acción',
        'subject' => 'Asunto',
        'status' => 'Resultado',
        'message' => 'Qué se hizo',
        'error' => 'Error',
        'attempt' => 'Intento',
        'duration' => 'Tiempo, ms',
    ],

    'settings' => [
        'description' => 'A quién se avisa cuando un conector (1C, Bitrix24) se rinde tras su último intento, y cuánto tiempo se conserva el registro de intercambio.',
        'alert_emails' => 'Correos de alerta',
        'alert_emails_hint' => 'Una o varias direcciones, separadas por comas o saltos de línea. Vacío: todos los superadministradores activos. Como máximo un correo por integración y hora.',
        'retention_days' => 'Conservar el registro, días',
        'retention_days_hint' => 'Las entradas más antiguas se eliminan cada noche.',
    ],

    'alert' => [
        'subject' => ':site: la integración :integration ha fallado',
        'what' => 'La integración :integration no pudo realizar la acción :action y ha dejado de intentarlo.',
        'subject_line' => 'Asunto: :subject',
        'error' => 'Error: :error',
        'attempts' => 'Intentos realizados: :count',
    ],
];
