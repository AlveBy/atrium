<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '1C',
    'title_runs' => 'Intercambio con 1C',

    'settings' => [
        'description' => 'El catálogo llega desde 1C:Enterprise mediante el intercambio estándar CommerceML. En 1C, en el intercambio con el sitio, indique esta dirección: :url (una configuración que pide solo el sitio toma :bitrix después de la dirección del sitio), y debajo el usuario y la contraseña que defina aquí.',
        'login' => 'Usuario',
        'login_hint' => 'Con el que 1C inicia sesión. El intercambio permanece cerrado hasta que se definan el usuario y la contraseña.',
        'password' => 'Contraseña',
        'password_hint' => 'Se guarda cifrada y no se vuelve a mostrar: para cambiarla, escriba una nueva; deje el campo vacío para conservar la actual.',
    ],

    'fields' => [
        'started_at' => 'Inicio',
        'finished_at' => 'Fin',
        'file' => 'Archivo',
        'status_label' => 'Resultado',
        'sections_created' => 'Secciones nuevas',
        'sections_updated' => 'Secciones actualizadas',
        'products_created' => 'Productos nuevos',
        'products_updated' => 'Productos actualizados',
        'offers_updated' => 'Precios y existencias actualizados',
        'skipped' => 'Ofertas omitidas (no existe el producto)',
        'errors' => 'Errores',
        'error' => 'Qué salió mal',
    ],

    'status' => [
        'running' => 'En curso',
        'done' => 'Hecho',
        'failed' => 'Fallido',
    ],

    'reply' => [
        'wrong_login' => 'Usuario o contraseña incorrectos, o el intercambio no está configurado en Módulos → 1C.',
        'denied' => 'Sin sesión iniciada: inicie el intercambio de nuevo.',
        'unknown_type' => 'Tipo de intercambio desconocido: :type.',
        'unknown_mode' => 'Paso de intercambio desconocido: :mode.',
        'bad_name' => 'No se permite este nombre de archivo.',
        'no_file' => 'No llegó el archivo :file.',
        'progress' => 'Elementos importados: :count.',
        'summary' => 'Secciones: :sections_created nuevas, :sections_updated actualizadas. Productos: :products_created nuevos, :products_updated actualizados. Precios y existencias: :offers. Omitidos: :skipped. Errores: :errors.',
        'no_orders' => 'No se intercambian pedidos: el módulo de tienda no está instalado.',
    ],
];
