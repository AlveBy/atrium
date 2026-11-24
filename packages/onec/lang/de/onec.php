<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '1C',
    'title_runs' => '1C-Austausch',

    'settings' => [
        'description' => 'Der Katalog kommt aus 1C:Enterprise über den Standardaustausch CommerceML. Tragen Sie in 1C im Austausch mit der Website diese Adresse ein: :url (eine Konfiguration, die nur nach der Website fragt, nimmt :bitrix nach der Website-Adresse), und darunter den Login und das Passwort, die Sie hier festlegen.',
        'login' => 'Login',
        'login_hint' => 'Damit meldet sich 1C an. Der Austausch bleibt gesperrt, bis Login und Passwort beide festgelegt sind.',
        'password' => 'Passwort',
        'password_hint' => 'Wird verschlüsselt gespeichert und nicht mehr angezeigt: Zum Ändern geben Sie ein neues ein; lassen Sie das Feld leer, um das aktuelle zu behalten.',
    ],

    'fields' => [
        'started_at' => 'Gestartet',
        'finished_at' => 'Beendet',
        'file' => 'Datei',
        'status_label' => 'Ergebnis',
        'sections_created' => 'Neue Abschnitte',
        'sections_updated' => 'Abschnitte aktualisiert',
        'products_created' => 'Neue Produkte',
        'products_updated' => 'Produkte aktualisiert',
        'offers_updated' => 'Preise und Bestände aktualisiert',
        'skipped' => 'Übersprungene Angebote (kein solches Produkt)',
        'errors' => 'Fehler',
        'error' => 'Was schiefgelaufen ist',
    ],

    'status' => [
        'running' => 'Läuft',
        'done' => 'Fertig',
        'failed' => 'Fehlgeschlagen',
    ],

    'reply' => [
        'wrong_login' => 'Falscher Login oder falsches Passwort, oder der Austausch ist unter Module → 1C nicht eingerichtet.',
        'denied' => 'Nicht angemeldet: Starten Sie den Austausch erneut.',
        'unknown_type' => 'Unbekannter Austauschtyp: :type.',
        'unknown_mode' => 'Unbekannter Austauschschritt: :mode.',
        'bad_name' => 'Dieser Dateiname ist nicht erlaubt.',
        'no_file' => 'Die Datei :file ist nicht angekommen.',
        'progress' => 'Importierte Elemente: :count.',
        'summary' => 'Abschnitte: :sections_created neu, :sections_updated aktualisiert. Produkte: :products_created neu, :products_updated aktualisiert. Preise und Bestände: :offers. Übersprungen: :skipped. Fehler: :errors.',
        'no_orders' => 'Bestellungen werden nicht ausgetauscht: Das Shop-Modul ist nicht installiert.',
    ],
];
