# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · **Español** · [Português (Brasil)](README.pt-BR.md) · [Italiano](README.it.md) · [Polski](README.pl.md) · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml)

_Esta página es una traducción automática del inglés. Las correcciones son bienvenidas: consulta [CONTRIBUTING.md](../../CONTRIBUTING.md) (en inglés)._

Un único panel de administración para tantos sitios multilingües como necesites, construido sobre Laravel 13, con un migrador que trae un sitio existente de 1C-Bitrix.

Describes un módulo una sola vez, como un modelo con sus campos y las columnas de su lista. Atrium le da una lista con búsqueda y ordenación, un formulario con pestañas, una entrada en el menú, una API y, con el tema, páginas públicas.

![La lista de productos de la tienda de demostración](../images/admin-list.png)

> **Antes de la primera versión.** Atrium funciona y está cubierto por tests, pero la versión 0.1 aún no ha salido y los paquetes no están en Packagist. Pruébalo desde este repositorio.

## Qué hace

- **Multisitio.** Un solo panel y una sola base de datos para muchos sitios, cada uno identificado por su dominio y con sus propios idiomas.
- **Multilingüe.** El panel habla 15 idiomas y cada administrador elige el suyo. Los idiomas de contenido se añaden desde el panel, cualquier columna de un módulo puede ser traducible y cada idioma tiene sus propias direcciones (`/de/…`) y `hreflang`.
- **20 tipos de campo.** Texto, números, fechas, listas, enlaces a otros módulos, imágenes, galerías, archivos, un editor visual, mapas, valores repetibles. Los formularios tienen pestañas, como en 1C-Bitrix.
- **Roles y permisos.** Quién ve cada módulo y qué puede hacer en él: ver, crear, editar, eliminar.
- **SEO sin código.** Títulos y descripciones para cada registro, direcciones legibles con redirecciones desde las antiguas, `sitemap.xml` y `robots.txt` para cada sitio. Google Tag, Yandex Metrica y los códigos de verificación se configuran por sitio en el panel.
- **API.** Tokens limitados a los módulos y acciones que elijas; `/api/v1` se describe a sí misma. Los asistentes de IA (Claude, ChatGPT, Cursor) se conectan a `/mcp` con el mismo token y exactamente sus permisos.
- **Código de acceso por correo.** El inicio de sesión en dos pasos está activado por defecto.
- **Migrador de 1C-Bitrix.** Los infoblocks, propiedades, secciones, formularios, la disposición de las listas, el SEO y la biblioteca multimedia se convierten en modelos, migraciones y datos de Laravel, y un informe compara el sitio antiguo con el nuevo.
- **Tema público.** Un sitio neutro que dibuja cada módulo a partir de su formulario en el panel, en claro y oscuro, legible con cualquier tipo de visión del color.
- **Con tests.** PHPUnit sobre SQLite, MySQL y PostgreSQL, y tests de navegador con Laravel Dusk.

| El formulario de un registro | El sitio, en claro y oscuro |
|---|---|
| ![Formulario de producto con pestañas](../images/admin-form.png) | ![Página de producto](../images/site-light.png) ![Noticia en alemán, tema oscuro](../images/site-dark.png) |

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

## Paquetes

| Paquete | Qué es |
|---|---|
| [`alveby/atrium`](../../packages/admin) | El panel de administración |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | El tema público |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | El migrador de 1C-Bitrix |

Cuando la 0.1 esté en Packagist, una aplicación Laravel 13 existente podrá añadir Atrium con:

```bash
composer require alveby/atrium
php artisan atrium:install
```

## Requisitos

- PHP 8.3 o superior
- Laravel 13
- SQLite, MySQL 8 o PostgreSQL

## Idiomas

El panel de administración habla inglés, ruso, ucraniano, bielorruso, kazajo, uzbeko, alemán, francés, español, portugués (Brasil), italiano, polaco, turco, chino (simplificado) y japonés. El inglés y el ruso se escribieron a mano; los demás empezaron como traducción automática, igual que este README. Las correcciones de hablantes nativos son una de las mejores primeras contribuciones: consulta [CONTRIBUTING.md](../../CONTRIBUTING.md) (en inglés).

## Próximos pasos

La [hoja de ruta](../../ROADMAP.md) (en inglés): módulos que se instalan con un solo comando, sitios listos para usar, importadores desde WordPress y Drupal, una tienda online, 1C y Bitrix24.

## Contribuir

[CONTRIBUTING.md](../../CONTRIBUTING.md) explica cómo preparar el proyecto, ejecutar los tests y traducir. Informa de los problemas de seguridad como se describe en [SECURITY.md](../../SECURITY.md).

## Licencia

MIT, consulta [LICENSE](../../LICENSE).
