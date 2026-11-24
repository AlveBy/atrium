<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Bitrix24',
    'title_links' => 'Bitrix24 : formulaires',

    'settings' => [
        'description' => 'Les envois des formulaires arrivent dans votre Bitrix24 sous forme de leads, et le widget de chat de Bitrix24 s\'affiche sur le site. Créez dans Bitrix24 un webhook entrant (Ressources pour développeurs → Autre → Webhook entrant) avec le droit d\'accès au CRM et collez ici son adresse.',
        'webhook' => 'Webhook entrant',
        'webhook_hint' => 'L\'adresse affichée par Bitrix24, du type https://portal.bitrix24.com/rest/1/code/. Elle est stockée chiffrée et n\'est plus jamais affichée : pour la changer, collez-en une nouvelle ; laissez le champ vide pour garder l\'actuelle.',
        'send_forms' => 'Envoyer les formulaires à Bitrix24',
        'send_forms_hint' => 'Un formulaire sans lien ci-dessous part comme un lead, avec ses champs placés selon leur type.',
        'widget_code' => 'Code du widget de chat',
        'widget_code_hint' => 'Le code de la ligne ouverte ou du widget de chat de Bitrix24 (CRM → Centre de contact → Chat en ligne → Widget). Il est placé à la fin de chaque page du site ; vide, il n\'y a pas de widget.',
        'check' => 'Vérifier la connexion',
        'connected' => 'Connecté : :portal, utilisateur :user.',
    ],

    'fields' => [
        'form' => 'Formulaire',
        'mode' => 'Envoyer comme',
        'active' => 'Envoyer ce formulaire à Bitrix24',
        'mapping' => 'Champs',
        'mapping_hint' => 'Une ligne par champ : "champ du formulaire = champ Bitrix24", par exemple "phone = PHONE" ou "message = COMMENTS". Un champ sans ligne est placé selon son type (un e-mail dans EMAIL, un téléphone dans PHONE) ; ce qui n\'a pas de place va dans le commentaire. Les lignes qui commencent par # sont ignorées.',
    ],

    'modes' => [
        'lead' => 'Lead',
        'deal' => 'Affaire avec un contact',
    ],

    'errors' => [
        'no_webhook' => 'Le webhook Bitrix24 n\'est pas renseigné : collez-le dans Modules → Bitrix24.',
        'unreachable' => 'Bitrix24 ne répond pas. Vérifiez l\'adresse du portail dans le webhook.',
        'portal' => 'Bitrix24 a refusé : :error',
        'mapping_line' => 'La ligne :line n\'est pas au format "champ du formulaire = champ Bitrix24".',
    ],

    'payload' => [
        'subject' => 'Envoi de formulaire n° :id',
        'title' => 'Site web : :form',
        'visitor' => 'Visiteur du site',
        'yes' => 'Oui',
        'lead' => 'Lead n° :id créé.',
        'deal' => 'Affaire n° :deal créée, contact n° :contact.',
        'gone' => 'L\'envoi a été supprimé avant d\'être transmis.',
    ],
];
