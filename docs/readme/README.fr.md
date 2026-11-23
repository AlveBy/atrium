# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · **Français** · [Español](README.es.md) · [Português (Brasil)](README.pt-BR.md) · [Italiano](README.it.md) · [Polski](README.pl.md) · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml) [![coverage 100%](https://img.shields.io/badge/coverage-100%25-brightgreen)](../testing.md)

_Cette page a été traduite automatiquement depuis l'anglais. Les corrections sont les bienvenues, voir [CONTRIBUTING.md](../../CONTRIBUTING.md)._

Un seul panneau d'administration pour autant de sites multilingues que vous voulez, construit sur Laravel 13, avec un migrateur qui reprend un site 1C-Bitrix existant.

Vous décrivez un module une seule fois, sous la forme d'un modèle avec ses champs et ses colonnes de liste. Atrium lui donne une liste avec recherche et tri, un formulaire à onglets, une entrée de menu, une API et, avec le thème, des pages publiques.

![La liste des produits de la boutique de démo](../images/admin-list.webp)

> **Avant la première version.** Atrium fonctionne et est couvert par des tests, mais la version 0.1 n'est pas encore sortie et les paquets ne sont pas sur Packagist. Essayez-le depuis ce dépôt.

## Ce qu'il fait

- **Multisite.** Une administration et une base de données pour de nombreux sites, chacun reconnu à son domaine ou à un dossier de celui-ci (`example.com/site2/`) et doté de ses propres langues.
- **Multilingue.** L'administration parle 15 langues, et chaque administrateur choisit la sienne. Les langues du contenu s'ajoutent dans l'administration, n'importe quelle colonne d'un module peut être traduisible, et chaque langue a ses propres URL (`/de/…`) et son `hreflang`.
- **20 types de champs.** Texte, nombres, dates, listes, liens vers d'autres modules, images, galeries, fichiers, un éditeur visuel, des cartes, des valeurs répétables. Les formulaires ont des onglets, comme dans 1C-Bitrix.
- **Rôles et droits.** Qui voit quel module et ce qu'il peut y faire : voir, créer, modifier, supprimer.
- **SEO sans code.** Titres et descriptions pour chaque fiche, URL lisibles avec redirection depuis les anciennes, `sitemap.xml` et `robots.txt` pour chaque site. Google Tag, Yandex Metrica et les codes de vérification se règlent par site dans l'administration.
- **API.** Des jetons limités aux modules et aux actions choisis ; `/api/v1` se décrit lui-même. Les assistants IA (Claude, ChatGPT, Cursor) se connectent à `/mcp` avec le même jeton et exactement ses droits.
- **Code de connexion par e-mail.** La connexion à deux facteurs est activée par défaut.
- **Migrateur 1C-Bitrix.** Infoblocs, propriétés, rubriques, formulaires, dispositions des listes, SEO et médiathèque deviennent des modèles Laravel, des migrations et des données, et un rapport compare l'ancien site au nouveau.
- **Thème public.** Un site neutre qui affiche chaque module d'après son formulaire d'administration, en clair et en sombre, lisible quelle que soit la perception des couleurs.
- **Testé.** PHPUnit sur SQLite, MySQL et PostgreSQL, tests dans le navigateur avec Laravel Dusk.

| Le formulaire d'une fiche | Recherche dans l'administration |
|---|---|
| ![Le formulaire d'une fiche](../images/admin-form.webp) | ![Recherche dans l'administration](../images/admin-search.webp) |

| Menus | Tableau de bord |
|---|---|
| ![Menus](../images/admin-menus.webp) | ![Tableau de bord](../images/admin-dashboard.webp) |

| Le site, thème clair | Le site, thème sombre |
|---|---|
| ![Le site, thème clair](../images/site-light.webp) | ![Le site, thème sombre](../images/site-dark.webp) |

## Qualité

- Plus de 6 500 tests PHPUnit et plus de 80 tests navigateur avec Laravel Dusk tournent à chaque pull request.
- Sur SQLite, MySQL et PostgreSQL, sous PHP 8.3 et 8.4.
- Chaque paquet a 100 % de ses lignes couvertes, et la CI refuse une modification qui laisse une ligne sans test.
- Le code modifié doit atteindre un score de mutation de 100 % : Infection altère chaque ligne modifiée, et un test doit échouer pour chaque altération.
- Rien n’est exclu de ces contrôles : aucune annotation d’exclusion, aucun mutateur désactivé.
- Chaque push est vérifié pour les dépendances aux vulnérabilités connues et pour les secrets dans l’historique git.

[Comment fonctionnent les tests et les contrôles](../testing.md).

## Essayer

Avec Docker, rien d'autre n'est nécessaire :

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

Le premier démarrage prend quelques minutes. Ouvrez ensuite http://localhost:8000/admin et connectez-vous avec `admin@example.com` et le mot de passe `password` (uniquement pour des essais en local). Le site se trouve sur http://localhost:8000.

Sans Docker, il vous faut PHP 8.3 ou plus récent et Composer. La base de données est SQLite par défaut et l'administration est livrée déjà compilée, donc ni MySQL ni Node ne sont nécessaires :

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
composer install
cp .env.example .env
php artisan key:generate
php artisan atrium:install --demo
php artisan serve
```

L'installateur demande le nom, l'adresse e-mail et le mot de passe du premier administrateur. Le code de connexion est envoyé par e-mail ; sur une machine locale sans messagerie, il se trouve à la fin de `storage/logs/laravel.log`, ou désactivez-le avec `ATRIUM_2FA_ENABLED=false` dans `.env`.

`--demo` ajoute une petite boutique : des produits avec tous les types de champs, des marques, des magasins, des actualités en trois langues et un second site. Pour repartir de zéro, supprimez `database/database.sqlite` et réinstallez.

## Votre premier module

```bash
php artisan atrium:make-module Article
php artisan migrate
```

Cela crée un modèle, une migration et une ligne dans `config/atrium.php`, et « Articles » apparaît dans le menu. Le modèle décrit son formulaire et sa liste :

```php
class Article extends Model implements Administrable
{
    use InteractsWithAdmin, Translatable;

    protected array $translatable = ['name', 'code', 'preview_text', 'detail_text'];

    public static function getAdministrationFields(): array
    {
        return [
            TextField::make('name')->label(__('atrium::fields.standard.name'))->required(),
            SlugField::make('code')->label(__('atrium::fields.standard.code'))->from('name')->unique('articles', 'code'),
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            ContentField::make('detail_text')->label(__('atrium::fields.standard.detail_text')),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('name')->label(__('atrium::fields.standard.name'))->sortable()->searchable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
        ];
    }
}
```

Pour donner au module des pages sur le site, ajoutez-le à `public_urls` dans `config/atrium.php` :

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

## Migrer depuis 1C-Bitrix

```bash
composer require alveby/atrium-bitrix --dev
php artisan atrium:bitrix:migrate
php artisan atrium:bitrix:parity
```

La base de données Bitrix est uniquement lue. Les onglets, l'ordre des champs et les colonnes des listes ressortent tels que les rédacteurs les avaient réglés dans Bitrix. `atrium:bitrix:sync` rattrape les modifications faites sur l'ancien site tant qu'il est encore en ligne. Pas à pas : [packages/bitrix/README.md](../../packages/bitrix/README.md) (en anglais).

## Documentation

- [Installation : votre serveur, un hébergement avec ISPmanager ou cPanel, Docker](../installation.md)
- [Démo publique : Docker, une remise à zéro toutes les heures, ce qu’un visiteur peut faire, où l’héberger](../demo.md)
- [Configuration : e-mail et code de connexion, fichiers, jetons API, assistants IA via MCP](../configuration.md)
- [Aide à la rédaction par IA : fournisseurs gratuits et payants, descriptions, SEO, textes alt, traductions](../ai.md)
- [SEO : titres, hreflang, sitemaps, schema.org, redirections, le contrôle SEO](../seo.md)
- [Menus : en-tête et pied de page, arbre de liens, éléments qui déplient un module](../menus.md)
- [Formulaires : un constructeur en colonnes, envoi sans recharger la page, dans une fenêtre, votre propre balisage et fetch](../forms.md)
- [Recherche : la page de résultats, le champ de l'administration, ce qui est cherché et comment](../search.md)
- [Votre module : champs, colonnes, onglets, droits, langues, tests, paquet](../modules.md)
- [Champs personnalisés : ajouter des champs à tout module depuis l'administration, filtres et facettes sur 100 000 fiches](../custom-fields.md)
- [Import et export : CSV et Excel pour tout module, colonnes, une vérification avant l’écriture, la console et une planification](../import-export.md)
- [Cache : des pages rapides avec des dizaines de propriétés, Redis, cache de pages, en-têtes CDN](../cache.md)
- [Migrer depuis 1C-Bitrix, pas à pas](../bitrix.md)
- [Migrer depuis WordPress : connecter la base, migrer, contrôler, la carte des 301](../wordpress.md)
- [Sauvegardes : bases de données et fichiers vers plusieurs destinations, planification, contrôles, restauration](../backup.md)
- [Erreurs : erreurs PHP, de file d'attente et JavaScript dans la base du site, alertes, Sentry et vos propres pilotes](../errors.md)
- [Vitesse et disponibilité : temps du serveur, Web Vitals des visiteurs, contrôles de disponibilité avec alertes](../monitoring.md)
- [CDN : images, copies redimensionnées et WebP/AVIF, purge du cache, envois vers S3](../cdn.md)
- [Connexion à deux facteurs : applications d'authentification, clés d'accès, codes, journal des connexions](../two-factor.md)
- [Connexion sociale : fournisseurs, enregistrement de l'application, URL de redirection](../social-login.md)

## Paquets

| Paquet | Ce que c'est |
|---|---|
| [`alveby/atrium`](../../packages/admin) | Le panneau d'administration |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | Le thème public |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | Le migrateur 1C-Bitrix |
| [`alveby/atrium-ai`](../../packages/ai/README.md) | Aide à la rédaction par IA dans l'administration |
| [`alveby/atrium-custom-fields`](../../packages/custom-fields/README.md) | Champs ajoutés à tout module depuis l'administration, avec filtres et facettes |
| [`alveby/atrium-seo`](../../packages/seo/README.md) | Modèles de balises meta, hreflang, sitemaps, schema.org, redirections et un contrôle SEO |
| [`alveby/atrium-docs`](../../packages/docs/README.md) | Documentation à partir de Markdown, avec versions et recherche |
| [`alveby/atrium-backup`](../../packages/backup/README.md) | Sauvegardes des bases de données et des fichiers, restauration depuis l'administration |
| [`alveby/atrium-errors`](../../packages/errors/README.md) | Suivi des erreurs dans la base du site, alertes, Sentry |
| [`alveby/atrium-monitoring`](../../packages/monitoring/README.md) | Vitesse du serveur et des visiteurs, disponibilité et alertes, sans service externe |
| [`alveby/atrium-news`](../../packages/news/README.md) | Actualités |
| [`alveby/atrium-pages`](../../packages/pages/README.md) | Pages statiques, avec import des pages 1C-Bitrix |
| [`alveby/atrium-catalog`](../../packages/catalog/README.md) | Catalogue de produits avec sections imbriquées |
| [`alveby/atrium-brands`](../../packages/brands/README.md) | Marques avec logos |
| [`alveby/atrium-stores`](../../packages/stores/README.md) | Magasins et bureaux sur une carte |
| [`alveby/atrium-team`](../../packages/team/README.md) | Personnes, services et contacts |
| [`alveby/atrium-faq`](../../packages/faq/README.md) | Questions et réponses |
| [`alveby/atrium-forms`](../../packages/forms/README.md) | Générateur de formulaires et soumissions |
| [`alveby/atrium-reviews`](../../packages/reviews/README.md) | Avis et notes avec modération |
| [`alveby/atrium-2fa`](../../packages/two-factor/README.md) | Connexion à deux facteurs et sans mot de passe |
| [`alveby/atrium-social-login`](../../packages/social-login/README.md) | Connexion avec Google, Microsoft, Apple, GitHub, Telegram et d'autres |
| [`alveby/atrium-solutions`](../../packages/solutions/README.md) | Sites prêts à l'emploi installés en une commande |
| [`alveby/atrium-menus`](../../packages/menus/README.md) | Menus construits dans l'administration |
| [`alveby/atrium-search`](../../packages/search/README.md) | Recherche sur tout le site, dans chaque module |
| [`alveby/atrium-wordpress`](../../packages/wordpress/README.md) | L'importateur WordPress : articles, pages, types de contenu personnalisés, SEO et traductions, avec contrôle et carte des 301 |

Les paquets sont sur Packagist, mais tant que la 0.1 n'est pas étiquetée, seulement en version de développement : il faut donc la nommer :

```bash
composer require alveby/atrium:^0.2
php artisan atrium:install
```

## Prérequis

- PHP 8.3 ou plus récent
- Laravel 13
- SQLite, MySQL 8 ou PostgreSQL

## Langues

Le panneau d'administration parle anglais, russe, ukrainien, biélorusse, kazakh, ouzbek, allemand, français, espagnol, portugais (Brésil), italien, polonais, turc, chinois (simplifié) et japonais. L'anglais et le russe ont été écrits à la main ; les autres ont commencé par une traduction automatique, tout comme ce README. Les corrections de locuteurs natifs comptent parmi les meilleures premières contributions : voir [CONTRIBUTING.md](../../CONTRIBUTING.md) (en anglais).

## Comment tout a commencé

Atrium est né en décembre 2025 chez [AlveSoft](https://alve.by), comme notre propre panneau d'administration pour remplacer 1C-Bitrix sur le site d'un client, [arkaim.by](https://arkaim.by). Nous voulions une administration aussi familière pour les rédacteurs que Bitrix, mais construite sur du Laravel ordinaire, et un migrateur qui reprenne le contenu sans rien perdre. Depuis l'été 2026, le panneau fait tourner ce site en production : les rédacteurs y travaillent chaque jour, et le migrateur a transféré tout le catalogue, les pages et les formulaires.

En novembre 2026, nous avons décidé de l'offrir à tout le monde. Nous avons retiré tout ce qui était propre à ce site et publié le reste sous licence MIT, afin que chacun puisse l'utiliser, le modifier et bâtir ses sites dessus gratuitement.

## La suite

La [feuille de route](../../ROADMAP.md) (en anglais) : des modules qui s'installent en une commande, des sites prêts à l'emploi, des importateurs depuis WordPress et Drupal, une boutique en ligne, 1C et Bitrix24.

## Contribuer

[CONTRIBUTING.md](../../CONTRIBUTING.md) explique comment installer le projet, lancer les tests et traduire. Signalez les problèmes de sécurité comme décrit dans [SECURITY.md](../../SECURITY.md).

## Licence

MIT, voir [LICENSE](../../LICENSE).

Si Atrium vous a été utile, merci de garder le lien « Réalisé avec Atrium » dans le pied de page de votre site. C’est une demande, pas une condition de la licence : MIT exige seulement de conserver la mention de copyright dans les copies du code.
