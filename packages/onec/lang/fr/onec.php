<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '1C',
    'title_runs' => 'Échange 1C',

    'settings' => [
        'description' => 'Le catalogue provient de 1C:Enterprise par l\'échange standard CommerceML. Dans 1C, dans l\'échange avec le site, indiquez cette adresse : :url (une configuration qui ne demande que le site prend :bitrix après l\'adresse du site), et en dessous l\'identifiant et le mot de passe que vous définissez ici.',
        'login' => 'Identifiant',
        'login_hint' => 'Celui avec lequel 1C se connecte. L\'échange reste fermé tant que l\'identifiant et le mot de passe ne sont pas tous deux définis.',
        'password' => 'Mot de passe',
        'password_hint' => 'Enregistré chiffré et jamais réaffiché : pour le changer, saisissez-en un nouveau ; laissez le champ vide pour conserver l\'actuel.',
    ],

    'fields' => [
        'started_at' => 'Début',
        'finished_at' => 'Fin',
        'file' => 'Fichier',
        'status_label' => 'Résultat',
        'sections_created' => 'Nouvelles sections',
        'sections_updated' => 'Sections mises à jour',
        'products_created' => 'Nouveaux produits',
        'products_updated' => 'Produits mis à jour',
        'offers_updated' => 'Prix et stocks mis à jour',
        'skipped' => 'Offres ignorées (produit inconnu)',
        'errors' => 'Erreurs',
        'error' => 'Ce qui a échoué',
    ],

    'status' => [
        'running' => 'En cours',
        'done' => 'Terminé',
        'failed' => 'Échec',
    ],

    'reply' => [
        'wrong_login' => 'Identifiant ou mot de passe incorrect, ou l\'échange n\'est pas configuré dans Modules → 1C.',
        'denied' => 'Non connecté : relancez l\'échange.',
        'unknown_type' => 'Type d\'échange inconnu : :type.',
        'unknown_mode' => 'Étape d\'échange inconnue : :mode.',
        'bad_name' => 'Ce nom de fichier n\'est pas autorisé.',
        'no_file' => 'Le fichier :file n\'est pas arrivé.',
        'progress' => 'Éléments importés : :count.',
        'summary' => 'Sections : :sections_created nouvelles, :sections_updated mises à jour. Produits : :products_created nouveaux, :products_updated mis à jour. Prix et stocks : :offers. Ignorées : :skipped. Erreurs : :errors.',
        'no_orders' => 'Les commandes ne sont pas échangées : le module boutique n\'est pas installé.',
    ],
];
