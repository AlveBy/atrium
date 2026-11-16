# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [Español](README.es.md) · [Português (Brasil)](README.pt-BR.md) · [Italiano](README.it.md) · [Polski](README.pl.md) · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · **日本語**

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml)

_このページは英語から機械翻訳されました。修正を歓迎します。詳しくは [CONTRIBUTING.md](../../CONTRIBUTING.md) をご覧ください。_

いくつもの多言語サイトを 1 つの管理パネルで扱える、Laravel 13 ベースのツールです。既存の 1C-Bitrix サイトを移行するマイグレーターも付属しています。

モジュールは、フィールドと一覧の列を持つモデルとして一度記述するだけです。Atrium はそこから、検索と並べ替えができる一覧、タブ付きのフォーム、メニュー項目、API を用意し、テーマを入れれば公開ページも表示します。

![デモショップの商品一覧](../images/admin-list.png)

> **最初のリリース前です。** Atrium は動作し、テストも揃っていますが、バージョン 0.1 はまだリリースされておらず、パッケージも Packagist にはありません。このリポジトリから試してください。

## できること

- **マルチサイト**：1 つの管理パネルと 1 つのデータベースで多数のサイトを運用できます。サイトはドメイン、またはドメイン上のフォルダー（`example.com/site2/`）で判別され、それぞれ独自の言語を持ちます。
- **多言語**：管理パネルは 15 の言語に対応し、管理者ごとに言語を選べます。コンテンツの言語は管理パネルで追加でき、モジュールのどの列も翻訳可能にできます。言語ごとに専用のアドレス（`/de/…`）と `hreflang` が付きます。
- **20 種類のフィールド**：テキスト、数値、日付、リスト、他のモジュールへのリンク、画像、ギャラリー、ファイル、ビジュアルエディター、地図、繰り返し値。フォームには 1C-Bitrix と同じようにタブがあります。
- **ロールと権限**：誰がどのモジュールを見られるか、そこで何ができるか（閲覧、作成、編集、削除）を設定できます。
- **コード不要の SEO**：すべてのレコードにタイトルと説明、旧アドレスからリダイレクトされる読みやすいアドレス、サイトごとの `sitemap.xml` と `robots.txt`。Google Tag、Yandex Metrica、サイト確認コードは管理パネルでサイトごとに設定します。
- **API**：選んだモジュールと操作だけに限定されたトークン。`/api/v1` は自身の内容を説明します。AI アシスタント（Claude、ChatGPT、Cursor）は同じトークンで `/mcp` に接続し、そのトークンの権限だけを持ちます。
- **メールによるログインコード**：2 段階ログインがデフォルトで有効です。
- **1C-Bitrix マイグレーター**：情報ブロック、プロパティ、セクション、フォーム、一覧のレイアウト、SEO、メディアライブラリが Laravel のモデル、マイグレーション、データに変換され、旧サイトと新サイトを比較するレポートも出力されます。
- **公開テーマ**：各モジュールを管理パネルのフォームに基づいて表示する、ニュートラルなサイト。ライトとダークに対応し、どのような色覚でも読みやすくなっています。
- **テスト済み**：SQLite、MySQL、PostgreSQL 上の PHPUnit と、Laravel Dusk によるブラウザテスト。

| レコードのフォーム | サイト（ライトとダーク） |
|---|---|
| ![タブ付きの商品フォーム](../images/admin-form.png) | ![商品ページ](../images/site-light.png) ![ドイツ語のニュース、ダークテーマ](../images/site-dark.png) |

## 試してみる

Docker があれば、ほかには何も要りません。

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

初回の起動には数分かかります。その後 http://localhost:8000/admin を開き、`admin@example.com`、パスワード `password` でログインしてください（ローカルでの試用専用）。サイトは http://localhost:8000 で開けます。

Docker を使わない場合は、PHP 8.3 以降と Composer が必要です。データベースはデフォルトで SQLite、管理パネルはビルド済みなので、MySQL も Node も要りません。

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
composer install
cp .env.example .env
php artisan key:generate
php artisan atrium:install --demo
php artisan serve
```

インストーラーが最初の管理者の名前、メールアドレス、パスワードを尋ねます。ログインコードはメールで届きます。メールを設定していないローカル環境では `storage/logs/laravel.log` の末尾に出力されます。`.env` に `ATRIUM_2FA_ENABLED=false` を書けば無効にもできます。

`--demo` は小さなショップを追加します。すべてのフィールドタイプを使った商品、ブランド、店舗、3 言語のニュース、そして 2 つ目のサイトです。最初からやり直すには、`database/database.sqlite` を削除して再インストールしてください。

## 最初のモジュール

```bash
php artisan atrium:make-module Article
php artisan migrate
```

これでモデル、マイグレーション、`config/atrium.php` の 1 行が作成され、メニューに「Articles」が表示されます。モデルは自身のフォームと一覧を記述します。

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

モジュールにサイト上のページを持たせるには、`config/atrium.php` の `public_urls` に追加します。

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

## 1C-Bitrix からの移行

```bash
composer require alveby/atrium-bitrix --dev
php artisan atrium:bitrix:migrate
php artisan atrium:bitrix:parity
```

Bitrix のデータベースは読み取るだけです。タブ、フィールドの順序、一覧の列は、編集者が Bitrix で設定していたとおりに再現されます。`atrium:bitrix:sync` は、旧サイトが稼働している間にそこで行われた編集を後から取り込みます。手順は [packages/bitrix/README.md](../../packages/bitrix/README.md)（英語）をご覧ください。

## ドキュメント

- [インストール：自前サーバー、ISPmanager や cPanel のホスティング、Docker](../installation.md)
- [設定：メールとサインインコード、ファイル、API トークン、MCP 経由の AI アシスタント](../configuration.md)
- [独自モジュール：フィールド、列、タブ、権限、言語、テスト、パッケージ化](../modules.md)
- [1C-Bitrix からの移行（手順）](../bitrix.md)

## パッケージ

| パッケージ | 内容 |
|---|---|
| [`alveby/atrium`](../../packages/admin) | 管理パネル |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | 公開テーマ |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | 1C-Bitrix マイグレーター |

0.1 が Packagist に公開されたら、既存の Laravel 13 アプリには次のコマンドで Atrium を導入できます。

```bash
composer require alveby/atrium
php artisan atrium:install
```

## 動作要件

- PHP 8.3 以降
- Laravel 13
- SQLite、MySQL 8、または PostgreSQL

## 言語

管理パネルは英語、ロシア語、ウクライナ語、ベラルーシ語、カザフ語、ウズベク語、ドイツ語、フランス語、スペイン語、ポルトガル語（ブラジル）、イタリア語、ポーランド語、トルコ語、中国語（簡体字）、日本語に対応しています。英語とロシア語は人の手で書かれ、それ以外はこの README と同じく機械翻訳から始まりました。ネイティブスピーカーによる修正は、最初の貢献としてとても歓迎されます。詳しくは [CONTRIBUTING.md](../../CONTRIBUTING.md)（英語）をご覧ください。

## 今後の予定

[ロードマップ](../../ROADMAP.md)（英語）：1 コマンドでインストールできるモジュール、すぐに使えるサイト、WordPress と Drupal からのインポーター、オンラインショップ、1C と Bitrix24。

## コントリビュート

[CONTRIBUTING.md](../../CONTRIBUTING.md) では、プロジェクトのセットアップ、テストの実行、翻訳の方法を説明しています。セキュリティ上の問題は [SECURITY.md](../../SECURITY.md) に記載の方法で報告してください。

## ライセンス

MIT。詳しくは [LICENSE](../../LICENSE) をご覧ください。
