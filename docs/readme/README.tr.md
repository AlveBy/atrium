# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [Español](README.es.md) · [Português (Brasil)](README.pt-BR.md) · [Italiano](README.it.md) · [Polski](README.pl.md) · **Türkçe** · [简体中文](README.zh-CN.md) · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml)

_Bu sayfa İngilizceden makine çevirisiyle hazırlandı. Düzeltmelere açığız, bkz. [CONTRIBUTING.md](../../CONTRIBUTING.md)._

Laravel 13 üzerine kurulu, istediğiniz sayıda çok dilli site için tek bir yönetim paneli ve mevcut bir 1C-Bitrix sitesini taşıyan bir geçiş aracı.

Bir modülü yalnızca bir kez, alanları ve liste sütunlarıyla birlikte bir model olarak tanımlarsınız. Atrium ona arama ve sıralama içeren bir liste, sekmeli bir form, bir menü öğesi, bir API ve temayla birlikte herkese açık sayfalar sağlar.

![Demo mağazanın ürün listesi](../images/admin-list.png)

> **İlk sürümden önce.** Atrium çalışıyor ve testlerle kapsanıyor, ancak 0.1 sürümü henüz çıkmadı ve paketler Packagist'te yok. Bu depodan deneyebilirsiniz.

## Neler yapar

- **Çoklu site.** Birçok site için tek panel ve tek veritabanı; her site alan adına veya onun üzerindeki bir klasöre (`example.com/site2/`) göre seçilir ve kendi dillerine sahiptir.
- **Çok dilli.** Panel 15 dil konuşur ve her yönetici kendi dilini seçer. İçerik dilleri panelden eklenir, bir modülün herhangi bir sütunu çevrilebilir yapılabilir ve her dil kendi adreslerini (`/de/…`) ve `hreflang` etiketlerini alır.
- **20 alan türü.** Metin, sayılar, tarihler, listeler, diğer modüllere bağlantılar, görseller, galeriler, dosyalar, görsel editör, haritalar, tekrarlanabilir değerler. Formlarda, 1C-Bitrix'te olduğu gibi sekmeler bulunur.
- **Roller ve izinler.** Kimin hangi modülü göreceği ve orada ne yapabileceği: görüntüleme, oluşturma, düzenleme, silme.
- **Kod yazmadan SEO.** Her kayıt için başlık ve açıklama, eski adreslerden yönlendirmeli okunabilir adresler, her site için `sitemap.xml` ve `robots.txt`. Google Tag, Yandex Metrica ve doğrulama kodları panelde her site için ayrı ayarlanır.
- **API.** Seçilen modüller ve işlemlerle sınırlı tokenlar; `/api/v1` kendini kendisi tanımlar. Yapay zekâ asistanları (Claude, ChatGPT, Cursor) aynı token ile ve tam olarak onun yetkileriyle `/mcp` adresine bağlanır.
- **E-postayla giriş kodu.** İki adımlı giriş varsayılan olarak açıktır.
- **1C-Bitrix geçiş aracı.** Bilgi blokları, özellikler, bölümler, formlar, liste düzenleri, SEO ve medya kütüphanesi Laravel modellerine, migration'lara ve verilere dönüşür; bir rapor da eski siteyi yenisiyle karşılaştırır.
- **Herkese açık tema.** Her modülü paneldeki formuna göre çizen sade bir site; açık ve koyu modu vardır ve her türlü renk görüşüyle okunabilir.
- **Test edilmiş.** SQLite, MySQL ve PostgreSQL üzerinde PHPUnit, Laravel Dusk ile tarayıcı testleri.

| Bir kaydın formu | Site, açık ve koyu |
|---|---|
| ![Sekmeli bir ürün formu](../images/admin-form.png) | ![Bir ürün sayfası](../images/site-light.png) ![Almanca bir haber, koyu tema](../images/site-dark.png) |

## Deneyin

Docker varsa başka hiçbir şeye gerek yok:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

İlk başlatma birkaç dakika sürer. Ardından http://localhost:8000/admin adresini açın ve `password` parolasıyla `admin@example.com` olarak giriş yapın (yalnızca yerel denemeler için). Site http://localhost:8000 adresindedir.

Docker olmadan PHP 8.3 veya daha yenisi ve Composer gerekir. Veritabanı varsayılan olarak SQLite'tır ve panel önceden derlenmiş olarak gelir; bu yüzden ne MySQL ne de Node gerekir:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
composer install
cp .env.example .env
php artisan key:generate
php artisan atrium:install --demo
php artisan serve
```

Kurulum aracı ilk yöneticinin adını, e-posta adresini ve parolasını sorar. Giriş kodu e-postayla gönderilir; e-posta ayarlanmamış yerel bir makinede kod `storage/logs/laravel.log` dosyasının sonundadır. Dilerseniz `.env` dosyasında `ATRIUM_2FA_ENABLED=false` ile kapatabilirsiniz.

`--demo` küçük bir online mağaza ekler: her alan türünü kullanan ürünler, markalar, mağazalar, üç dilde haberler ve ikinci bir site. Baştan başlamak için `database/database.sqlite` dosyasını silin ve yeniden kurun.

## İlk modülünüz

```bash
php artisan atrium:make-module Article
php artisan migrate
```

Bu komut bir model, bir migration ve `config/atrium.php` içinde bir satır oluşturur; menüde de “Articles” belirir. Model kendi formunu ve listesini tanımlar:

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

Modülün sitede sayfaları olması için onu `config/atrium.php` içindeki `public_urls` bölümüne ekleyin:

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

## 1C-Bitrix'ten geçiş

```bash
composer require alveby/atrium-bitrix --dev
php artisan atrium:bitrix:migrate
php artisan atrium:bitrix:parity
```

Bitrix veritabanından yalnızca okuma yapılır. Sekmeler, alan sırası ve liste sütunları, editörlerin Bitrix'te ayarladığı şekliyle aktarılır. `atrium:bitrix:sync`, eski site hâlâ yayındayken orada yapılan düzenlemeleri sonradan aktarır. Adım adım anlatım: [packages/bitrix/README.md](../../packages/bitrix/README.md) (İngilizce).

## Belgeler

- [Kurulum: kendi sunucunuz, ISPmanager veya cPanel barındırma, Docker](../installation.md)
- [Yapılandırma: e-posta ve giriş kodu, dosyalar, API tokenları, MCP ile yapay zekâ asistanları](../configuration.md)
- [Yapay zekâ yazım yardımı: ücretsiz ve ücretli sağlayıcılar, açıklamalar, SEO, alt metinleri, çeviriler](../ai.md)
- [Kendi modülünüz: alanlar, sütunlar, sekmeler, yetkiler, diller, testler, paket](../modules.md)
- [Özel alanlar: yönetim panelinden her modüle alan ekleme, 100 000 kayıtta filtreler ve faset sayıları](../custom-fields.md)
- [Önbellek: onlarca özellikli hızlı sayfalar, Redis, sayfa önbelleği, CDN başlıkları](../cache.md)
- [1C-Bitrix’ten adım adım taşınma](../bitrix.md)
- [Yedekler: veritabanları ve dosyalar birden çok yere, zamanlama, sağlık kontrolleri, geri yükleme](../backup.md)
- [Hatalar: PHP, kuyruk ve JavaScript hataları sitenin veritabanında, uyarılar, Sentry ve kendi sürücüleriniz](../errors.md)
- [CDN: görseller, küçültülmüş kopyalar ve WebP/AVIF, önbellek temizleme, S3'e yükleme](../cdn.md)

## Paketler

| Paket | Nedir |
|---|---|
| [`alveby/atrium`](../../packages/admin) | Yönetim paneli |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | Herkese açık tema |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | 1C-Bitrix geçiş aracı |
| [`alveby/atrium-ai`](../../packages/ai/README.md) | Yönetim panelinde yapay zekâ yazım yardımı |
| [`alveby/atrium-custom-fields`](../../packages/custom-fields/README.md) | Yönetim panelinden her modüle eklenen alanlar, filtreler ve faset sayılarıyla |
| [`alveby/atrium-docs`](../../packages/docs/README.md) | Markdown’dan belgeler, sürümler ve arama ile |
| [`alveby/atrium-backup`](../../packages/backup/README.md) | Veritabanı ve dosya yedekleri, yönetim panelinden geri yükleme |
| [`alveby/atrium-errors`](../../packages/errors/README.md) | Sitenin veritabanında hata takibi, uyarılar, Sentry |

0.1 Packagist'te yayımlandığında, mevcut bir Laravel 13 uygulamasına Atrium şu şekilde eklenecek:

```bash
composer require alveby/atrium
php artisan atrium:install
```

## Gereksinimler

- PHP 8.3 veya daha yenisi
- Laravel 13
- SQLite, MySQL 8 veya PostgreSQL

## Diller

Yönetim paneli İngilizce, Rusça, Ukraynaca, Belarusça, Kazakça, Özbekçe, Almanca, Fransızca, İspanyolca, Portekizce (Brezilya), İtalyanca, Lehçe, Türkçe, Çince (Basitleştirilmiş) ve Japonca konuşur. İngilizce ve Rusça elle yazıldı; diğerleri, bu README gibi, makine çevirisiyle başladı. Anadili bu diller olanlardan gelen düzeltmeler, projeye yapılabilecek en iyi ilk katkılardan biridir: bkz. [CONTRIBUTING.md](../../CONTRIBUTING.md) (İngilizce).

## Nasıl başladı

Atrium, Aralık 2025'te [AlveSoft](https://alve.by) bünyesinde, bir müşterinin sitesinde ([arkaim.by](https://arkaim.by)) 1C-Bitrix'in yerini alacak kendi yönetim panelimiz olarak doğdu. Editörlerin Bitrix kadar aşina olacağı, ama sade Laravel üzerine kurulu bir panel ve içeriği kayıpsız taşıyacak bir geçiş aracı istiyorduk. 2026 yazından beri panel o siteyi canlıda çalıştırıyor: editörler her gün onunla içerik giriyor, geçiş aracı ise tüm kataloğu, sayfaları ve formları taşıdı.

Kasım 2026'da onu herkese açmaya karar verdik. O tek siteye ait ne varsa çıkardık, geri kalanını MIT lisansıyla yayımladık; böylece herkes onu ücretsiz kullanabilir, değiştirebilir ve kendi sitelerini onun üzerine kurabilir.

## Sırada ne var

[Yol haritası](../../ROADMAP.md) (İngilizce): tek komutla kurulan modüller, hazır siteler, WordPress ve Drupal'dan içe aktarıcılar, bir online mağaza, 1C ve Bitrix24.

## Katkıda bulunma

[CONTRIBUTING.md](../../CONTRIBUTING.md) projenin nasıl kurulacağını, testlerin nasıl çalıştırılacağını ve nasıl çeviri yapılacağını anlatır. Güvenlik sorunlarını [SECURITY.md](../../SECURITY.md) dosyasında anlatıldığı şekilde bildirin.

## Lisans

MIT, bkz. [LICENSE](../../LICENSE).

Atrium işinize yaradıysa lütfen sitenizin alt bilgisindeki “Atrium ile yapıldı” bağlantısını koruyun. Bu bir rica, lisans koşulu değil: MIT yalnızca kodun kopyalarında telif hakkı bildiriminin korunmasını şart koşar.
