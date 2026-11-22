# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · **Español** · [Português (Brasil)](README.pt-BR.md) · [Italiano](README.it.md) · [Polski](README.pl.md) · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml)

_Esta página es una traducción automática del inglés. Las correcciones son bienvenidas: consulta [CONTRIBUTING.md](../../CONTRIBUTING.md) (en inglés)._

Un único panel de administración para tantos sitios multilingües como necesites, construido sobre Laravel 13, con un migrador que trae un sitio existente de 1C-Bitrix.

Describes un módulo una sola vez, como un modelo con sus campos y las columnas de su lista. Atrium le da una lista con búsqueda y ordenación, un formulario con pestañas, una entrada en el menú, una API y, con el tema, páginas públicas.

![La lista de productos de la tienda de demostración](../images/admin-list.webp)

> **Antes de la primera versión.** Atrium funciona y está cubierto por tests, pero la versión 0.1 aún no ha salido y los paquetes no están en Packagist. Pruébalo desde este repositorio.

## Qué hace

- **Multisitio.** Un solo panel y una sola base de datos para muchos sitios, cada uno identificado por su dominio o por una carpeta en él (`example.com/site2/`) y con sus propios idiomas.
- **Multilingüe.** El panel habla 15 idiomas y cada administrador elige el suyo. Los idiomas de contenido se añaden desde el panel, cualquier columna de un módulo puede ser traducible y cada idioma tiene sus propias direcciones (`/de/…`) y `hreflang`.
- **20 tipos de campo.** Texto, números, fechas, listas, enlaces a otros módulos, imágenes, galerías, archivos, un editor visual, mapas, valores repetibles. Los formularios tienen pestañas, como en 1C-Bitrix.
- **Roles y permisos.** Quién ve cada módulo y qué puede hacer en él: ver, crear, editar, eliminar.
- **SEO sin código.** Títulos y descripciones para cada registro, direcciones legibles con redirecciones desde las antiguas, `sitemap.xml` y `robots.txt` para cada sitio. Google Tag, Yandex Metrica y los códigos de verificación se configuran por sitio en el panel.
- **API.** Tokens limitados a los módulos y acciones que elijas; `/api/v1` se describe a sí misma. Los asistentes de IA (Claude, ChatGPT, Cursor) se conectan a `/mcp` con el mismo token y exactamente sus permisos.
- **Código de acceso por correo.** El inicio de sesión en dos pasos está activado por defecto.
- **Migrador de 1C-Bitrix.** Los infoblocks, propiedades, secciones, formularios, la disposición de las listas, el SEO y la biblioteca multimedia se convierten en modelos, migraciones y datos de Laravel, y un informe compara el sitio antiguo con el nuevo.
- **Tema público.** Un sitio neutro que dibuja cada módulo a partir de su formulario en el panel, en claro y oscuro, legible con cualquier tipo de visión del color.
- **Con tests.** PHPUnit sobre SQLite, MySQL y PostgreSQL, y tests de navegador con Laravel Dusk.

| El formulario de un registro | Búsqueda en el panel |
|---|---|
| ![El formulario de un registro](../images/admin-form.webp) | ![Búsqueda en el panel](../images/admin-search.webp) |

| Menús | Panel de control |
|---|---|
| ![Menús](../images/admin-menus.webp) | ![Panel de control](../images/admin-dashboard.webp) |

| El sitio, tema claro | El sitio, tema oscuro |
|---|---|
| ![El sitio, tema claro](../images/site-light.webp) | ![El sitio, tema oscuro](../images/site-dark.webp) |

## Pruébalo

Con Docker no necesitas nada más:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

El primer arranque tarda unos minutos. Después abre http://localhost:8000/admin e inicia sesión como `admin@example.com` con la contraseña `password` (solo para pruebas locales). El sitio está en http://localhost:8000.

Sin Docker necesitas PHP 8.3 o superior y Composer. La base de datos es SQLite por defecto y el panel viene ya compilado, así que no hacen falta ni MySQL ni Node:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
composer install
cp .env.example .env
php artisan key:generate
php artisan atrium:install --demo
php artisan serve
```

El instalador pide el nombre, el correo y la contraseña del primer administrador. El código de acceso se envía por correo; en una máquina local sin correo aparece al final de `storage/logs/laravel.log`, o puedes desactivarlo con `ATRIUM_2FA_ENABLED=false` en `.env`.

`--demo` añade una pequeña tienda: productos con todos los tipos de campo, marcas, puntos de venta, noticias en tres idiomas y un segundo sitio. Para empezar de cero, borra `database/database.sqlite` y vuelve a instalar.

## Tu primer módulo

```bash
php artisan atrium:make-module Article
php artisan migrate
```

Esto crea un modelo, una migración y una línea en `config/atrium.php`, y «Articles» aparece en el menú. El modelo describe su formulario y su lista:

```php
class Article extends Model implements Administrable
{
    use InteractsWithAdmin, Translatable;

    protected array $translatable = ['name', 'code', 'preview_text', 'detail_text'];

    public static function getAdministrationFields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            SlugField::make('code')->label('Code')->from('name')->unique('articles', 'code'),
            CheckboxField::make('active')->label('Active')->default(true),
            ContentField::make('detail_text')->label('Detail text'),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('name')->label('Name')->sortable()->searchable(),
            BooleanColumn::make('active')->label('Active'),
        ];
    }
}
```

Para que el módulo tenga páginas en el sitio, añádelo a `public_urls` en `config/atrium.php`:

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

## Migrar desde 1C-Bitrix

```bash
composer require alveby/atrium-bitrix --dev
php artisan atrium:bitrix:migrate
php artisan atrium:bitrix:parity
```

La base de datos de Bitrix solo se lee. Las pestañas, el orden de los campos y las columnas de las listas quedan tal como los tenían los editores en Bitrix. `atrium:bitrix:sync` incorpora los cambios que se hagan en el sitio antiguo mientras siga en funcionamiento. Paso a paso: [packages/bitrix/README.md](../../packages/bitrix/README.md) (en inglés).

## Documentación

- [Instalación: tu servidor, hosting con ISPmanager o cPanel, Docker](../installation.md)
- [Demo pública: Docker, un reinicio cada hora, qué puede hacer un visitante, dónde alojarla](../demo.md)
- [Configuración: correo y código de acceso, archivos, tokens de API, asistentes de IA por MCP](../configuration.md)
- [Ayuda de redacción con IA: proveedores gratuitos y de pago, descripciones, SEO, textos alt, traducciones](../ai.md)
- [SEO: títulos, hreflang, sitemaps, schema.org, redirecciones, la revisión SEO](../seo.md)
- [Menús: cabecera y pie, un árbol de enlaces, elementos que despliegan un módulo](../menus.md)
- [Búsqueda: la página de resultados, el buscador del panel, qué se busca y cómo](../search.md)
- [Tu propio módulo: campos, columnas, pestañas, permisos, idiomas, pruebas, paquete](../modules.md)
- [Campos propios: añadir campos a cualquier módulo desde el panel, filtros y facetas con 100 000 registros](../custom-fields.md)
- [Importar productos: CSV y Excel, columnas, una comprobación antes de escribir, la consola y una programación](../catalog-import.md)
- [Caché: páginas rápidas con decenas de propiedades, Redis, caché de páginas, cabeceras para CDN](../cache.md)
- [Migrar desde 1C-Bitrix, paso a paso](../bitrix.md)
- [Migrar desde WordPress: conectar la base de datos, migrar, comprobar, el mapa de 301](../wordpress.md)
- [Copias de seguridad: bases de datos y archivos en varios destinos, horarios, comprobaciones, restauración](../backup.md)
- [Errores: errores de PHP, de colas y de JavaScript en la base de datos del sitio, alertas, Sentry y controladores propios](../errors.md)
- [Velocidad y disponibilidad: tiempos del servidor, Web Vitals de los visitantes, comprobaciones de disponibilidad con avisos](../monitoring.md)
- [CDN: imágenes, copias redimensionadas y WebP/AVIF, purga de caché, subidas a S3](../cdn.md)
- [Acceso en dos pasos: apps de autenticación, passkeys, códigos, el registro de accesos](../two-factor.md)
- [Acceso social: proveedores, registro de la aplicación, URL de redirección](../social-login.md)

## Paquetes

| Paquete | Qué es |
|---|---|
| [`alveby/atrium`](../../packages/admin) | El panel de administración |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | El tema público |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | El migrador de 1C-Bitrix |
| [`alveby/atrium-ai`](../../packages/ai/README.md) | Ayuda de redacción con IA en el panel |
| [`alveby/atrium-custom-fields`](../../packages/custom-fields/README.md) | Campos que se añaden a cualquier módulo desde el panel, con filtros y facetas |
| [`alveby/atrium-seo`](../../packages/seo/README.md) | Plantillas meta, hreflang, sitemaps, schema.org, redirecciones y una revisión SEO |
| [`alveby/atrium-docs`](../../packages/docs/README.md) | Documentación desde Markdown, con versiones y búsqueda |
| [`alveby/atrium-backup`](../../packages/backup/README.md) | Copias de seguridad de bases de datos y archivos, restauración desde el panel |
| [`alveby/atrium-errors`](../../packages/errors/README.md) | Registro de errores en la base de datos del sitio, alertas, Sentry |
| [`alveby/atrium-monitoring`](../../packages/monitoring/README.md) | Velocidad del servidor y de los visitantes, disponibilidad y avisos, sin servicios externos |
| [`alveby/atrium-news`](../../packages/news/README.md) | Noticias |
| [`alveby/atrium-pages`](../../packages/pages/README.md) | Páginas estáticas, con importación de páginas de 1C-Bitrix |
| [`alveby/atrium-catalog`](../../packages/catalog/README.md) | Catálogo de productos con secciones anidadas |
| [`alveby/atrium-brands`](../../packages/brands/README.md) | Marcas con logotipos |
| [`alveby/atrium-stores`](../../packages/stores/README.md) | Tiendas y oficinas en un mapa |
| [`alveby/atrium-team`](../../packages/team/README.md) | Personas, departamentos y contactos |
| [`alveby/atrium-faq`](../../packages/faq/README.md) | Preguntas y respuestas |
| [`alveby/atrium-forms`](../../packages/forms/README.md) | Constructor de formularios y envíos |
| [`alveby/atrium-reviews`](../../packages/reviews/README.md) | Reseñas y valoraciones con moderación |
| [`alveby/atrium-2fa`](../../packages/two-factor/README.md) | Acceso en dos pasos y sin contraseña |
| [`alveby/atrium-social-login`](../../packages/social-login/README.md) | Acceso con Google, Microsoft, Apple, GitHub, Telegram y otros |
| [`alveby/atrium-solutions`](../../packages/solutions/README.md) | Sitios listos que se instalan con un comando |
| [`alveby/atrium-menus`](../../packages/menus/README.md) | Menús creados en el panel |
| [`alveby/atrium-search`](../../packages/search/README.md) | Búsqueda en todo el sitio, en cada módulo |
| [`alveby/atrium-wordpress`](../../packages/wordpress/README.md) | El importador de WordPress: entradas, páginas, tipos de contenido propios, SEO y traducciones, con comprobación y mapa de 301 |

Los paquetes ya están en Packagist, pero mientras la 0.1 no tenga etiqueta solo como versión de desarrollo, así que hay que indicarla:

```bash
composer require alveby/atrium:^0.1
php artisan atrium:install
```

## Requisitos

- PHP 8.3 o superior
- Laravel 13
- SQLite, MySQL 8 o PostgreSQL

## Idiomas

El panel de administración habla inglés, ruso, ucraniano, bielorruso, kazajo, uzbeko, alemán, francés, español, portugués (Brasil), italiano, polaco, turco, chino (simplificado) y japonés. El inglés y el ruso se escribieron a mano; los demás empezaron como traducción automática, igual que este README. Las correcciones de hablantes nativos son una de las mejores primeras contribuciones: consulta [CONTRIBUTING.md](../../CONTRIBUTING.md) (en inglés).

## Cómo empezó todo

Atrium nació en diciembre de 2025 en [AlveSoft](https://alve.by) como nuestro propio panel de administración para sustituir 1C-Bitrix en el sitio de un cliente, [arkaim.by](https://arkaim.by). Queríamos un panel que a los editores les resultara tan familiar como Bitrix, pero construido sobre Laravel a secas, y un migrador que traspasara el contenido sin perder nada. Desde el verano de 2026 el panel mantiene ese sitio en producción: los editores trabajan en él todos los días, y el migrador trasladó todo el catálogo, las páginas y los formularios.

En noviembre de 2026 decidimos ofrecerlo a todo el mundo. Quitamos todo lo que pertenecía a ese único sitio y publicamos el resto bajo la licencia MIT, para que cualquiera pueda usarlo, modificarlo y construir sus sitios sobre él de forma gratuita.

## Próximos pasos

La [hoja de ruta](../../ROADMAP.md) (en inglés): módulos que se instalan con un solo comando, sitios listos para usar, importadores desde WordPress y Drupal, una tienda online, 1C y Bitrix24.

## Contribuir

[CONTRIBUTING.md](../../CONTRIBUTING.md) explica cómo preparar el proyecto, ejecutar los tests y traducir. Informa de los problemas de seguridad como se describe en [SECURITY.md](../../SECURITY.md).

## Licencia

MIT, consulta [LICENSE](../../LICENSE).

Si Atrium te ha ayudado, por favor deja el enlace «Hecho con Atrium» en el pie de tu sitio. Es una petición, no una condición de la licencia: MIT solo exige conservar el aviso de copyright en las copias del código.
