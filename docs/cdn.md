# CDN

[English](cdn.md) · [Русский](cdn.ru.md)

A CDN keeps copies of the site's pictures and files on servers near visitors. Atrium can put one in front of everything it links to: uploads, files moved from 1C-Bitrix, pictures inside texts, the theme's images and, if you want, the built scripts and styles. It is off until you set an address.

- [Turn it on](#turn-it-on)
- [Resized images and WebP/AVIF](#resized-images-and-webpavif)
- [New versions and deleted files](#new-versions-and-deleted-files)
- [Uploads to S3](#uploads-to-s3)
- [Scripts and styles](#scripts-and-styles)
- [Things to know](#things-to-know)

## Turn it on

Create a pull zone in your CDN (Cloudflare, BunnyCDN, CloudFront and the like) with the site as its origin, then:

```bash
ATRIUM_CDN_URL=https://cdn.example.com
php artisan config:cache
```

Every address Atrium builds for a path under `/storage/` or `/upload/` now points to the CDN: image fields and galleries, file links, the Open Graph picture, list thumbnails in the admin, and `src`/`href` inside texts written in the editor. The values in the database do not change, so turning the CDN off again is just removing the line. Other paths are set in `config/atrium.php` → `cdn.paths`.

With Cloudflare in front of the whole site (proxied DNS), set `ATRIUM_CDN_URL` to the site's own address, `https://example.com`: files stay where they are and get versioned addresses, and the resizing and purge below work on them.

## Resized images and WebP/AVIF

Without this, a list of cards loads every picture at its full size. With an image driver, the theme asks for each picture at the width it is shown and gives the browser a `srcset` to pick from; the CDN makes the copy and keeps it.

| `ATRIUM_CDN_IMAGES` | What it uses | Also set |
|---|---|---|
| `cloudflare` | [Image transformations](https://developers.cloudflare.com/images/transform-images/) on your zone: `/cdn-cgi/image/width=640,quality=82,format=auto/…` | Turn transformations on for the zone |
| `bunny` | [Bunny Optimizer](https://bunny.net/optimizer/): `?width=640&quality=82` | Turn Optimizer on for the pull zone; WebP/AVIF follow its settings |
| `imgproxy` | Your own [imgproxy](https://imgproxy.net/) server | `ATRIUM_CDN_IMAGES_URL`, and `ATRIUM_IMGPROXY_KEY`/`ATRIUM_IMGPROXY_SALT` if it checks signatures (it should) |
| `template` | Any other service, by a URL pattern | `ATRIUM_CDN_IMAGES_TEMPLATE`, with `{url}` `{path}` `{width}` `{format}` `{quality}` |

`ATRIUM_CDN_IMAGES_FORMAT` is `auto` (the CDN sends AVIF or WebP to browsers that take them), `webp` or `avif`; `ATRIUM_CDN_IMAGES_QUALITY` is 82. Widths are rounded up to 320, 640, 960, 1280 or 1920 pixels, so the CDN keeps a few copies of each picture, not one per layout (`cdn.images.widths`).

For example, a template for a service that takes `https://img.example.com/640x/storage/a.jpg`:

```bash
ATRIUM_CDN_IMAGES=template
ATRIUM_CDN_IMAGES_TEMPLATE=https://img.example.com/{width}x/{path}
```

Cloudflare and Bunny resize only pictures that pass through them, so other addresses (a bucket on another host) are left as they are. imgproxy and templates fetch any address.

In your own Blade views:

```blade
@php($src = \Atrium\Admin\Support\MediaUrl::to($record->preview_picture))
<img src="{{ \Atrium\Admin\Support\MediaUrl::to($record->preview_picture, 640) }}"
     srcset="{{ \Atrium\Admin\Support\Cdn::srcset($src) }}" sizes="(min-width: 64rem) 20rem, 100vw" alt="">
```

`MediaUrl::to($value, $width)` takes a stored value of any kind and gives the address through the CDN; `Cdn::srcset()` is null while no image driver is set, and Blade then leaves the attribute empty.

## New versions and deleted files

A CDN keeps a copy until it expires, so an image replaced under the same name would stay old for visitors. Atrium adds `?v=` with the file's modification time to every local file it links to (`ATRIUM_CDN_VERSION`, on by default): a changed file gets a new address and the CDN fetches it. Uploads in the admin get new names anyway.

A deleted file has no new address, so the CDN keeps serving it. With a purge driver, deleting a file in the admin's file manager removes it from the CDN's cache right after the page answers (`ATRIUM_CDN_PURGE_ON_DELETE`, on by default):

| `ATRIUM_CDN_PURGE` | `.env` |
|---|---|
| `cloudflare` | `CLOUDFLARE_ZONE_ID`, and `CLOUDFLARE_API_TOKEN`: a token with the "Zone → Cache Purge → Purge" permission for that zone only |
| `bunny` | `BUNNY_API_KEY` (Account settings → API), and `BUNNY_PULL_ZONE_ID` for purging everything |

Create the token in the CDN's dashboard yourself and keep it in `.env` only. By hand, for files or for everything:

```bash
php artisan atrium:cdn:purge /storage/uploads/2026/11/photo.jpg /upload/iblock/1/a.jpg
php artisan atrium:cdn:purge --all
```

A refused purge is written to the log; the file is gone from the site either way.

## Uploads to S3

Uploads can go to a bucket instead of the server: define an `s3` disk in `config/filesystems.php` (Amazon S3, Cloudflare R2, Backblaze B2, MinIO…), set `AWS_URL` to the CDN in front of the bucket, and point the admin at it:

```bash
composer require league/flysystem-aws-s3-v3
ATRIUM_UPLOADS_DISK=s3
AWS_BUCKET=site-media
AWS_URL=https://media.example.com
```

A file uploaded in the admin is stored as its full address on that host, `https://media.example.com/uploads/2026/11/…jpg`, so it works everywhere the value is shown. No WebP copy is made next to it (that one is the web server's job on a local disk): use an image driver with `format=auto` instead. Deleting it in the file manager deletes the object and purges that address.

Because the address is in the database, moving the bucket behind a different host later means replacing the old host in the stored values. Choose the CDN's host before the first upload.

## Scripts and styles

The admin's own bundle and your site's Vite build can come from the CDN too:

```bash
ATRIUM_CDN_ASSETS=true
```

Browsers load module scripts and fonts from another host only with CORS, so the CDN must answer with `Access-Control-Allow-Origin` for your site (in Cloudflare and Bunny: a response header rule). Built files have hashed names and never need a purge.

## Things to know

- Uploaded images get a WebP copy and the web server sends it to browsers that ask for WebP, with `Vary: Accept`. Cloudflare's free plans ignore `Vary` for images and may cache one format for everybody: either use `ATRIUM_CDN_IMAGES=cloudflare` with `format=auto`, or turn the WebP copies off (`ATRIUM_UPLOADS_WEBP=false`).
- In the admin, list thumbnails come through the CDN like the site's pictures; the previews in a record's form come from the site itself, so an editor sees what is on the server.
- Text written as plain text (not HTML) is left as it is: there are no `src` attributes in it.
