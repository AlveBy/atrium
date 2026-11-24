<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '1C',
    'title_runs' => 'Scambio con 1C',

    'settings' => [
        'description' => 'Il catalogo arriva da 1C:Enterprise con lo scambio standard CommerceML. In 1C, nello scambio con il sito, indica questo indirizzo: :url (una configurazione che chiede solo il sito prende :bitrix dopo l\'indirizzo del sito), e sotto il login e la password che imposti qui.',
        'login' => 'Login',
        'login_hint' => 'Con cui 1C accede. Lo scambio resta chiuso finché non sono impostati sia il login sia la password.',
        'password' => 'Password',
        'password_hint' => 'Salvata cifrata e non più mostrata: per cambiarla, scrivine una nuova; lascia il campo vuoto per mantenere quella attuale.',
    ],

    'fields' => [
        'started_at' => 'Avviato',
        'finished_at' => 'Terminato',
        'file' => 'File',
        'status_label' => 'Risultato',
        'sections_created' => 'Nuove sezioni',
        'sections_updated' => 'Sezioni aggiornate',
        'products_created' => 'Nuovi prodotti',
        'products_updated' => 'Prodotti aggiornati',
        'offers_updated' => 'Prezzi e giacenze aggiornati',
        'skipped' => 'Offerte saltate (prodotto inesistente)',
        'errors' => 'Errori',
        'error' => 'Cosa è andato storto',
    ],

    'status' => [
        'running' => 'In corso',
        'done' => 'Fatto',
        'failed' => 'Non riuscito',
    ],

    'reply' => [
        'wrong_login' => 'Login o password errati, oppure lo scambio non è impostato in Moduli → 1C.',
        'denied' => 'Accesso non effettuato: riavvia lo scambio.',
        'unknown_type' => 'Tipo di scambio sconosciuto: :type.',
        'unknown_mode' => 'Passaggio dello scambio sconosciuto: :mode.',
        'bad_name' => 'Questo nome di file non è consentito.',
        'no_file' => 'Il file :file non è arrivato.',
        'progress' => 'Elementi importati: :count.',
        'summary' => 'Sezioni: :sections_created nuove, :sections_updated aggiornate. Prodotti: :products_created nuovi, :products_updated aggiornati. Prezzi e giacenze: :offers. Saltate: :skipped. Errori: :errors.',
        'no_orders' => 'Gli ordini non vengono scambiati: il modulo negozio non è installato.',
    ],
];
