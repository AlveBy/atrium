# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [Español](README.es.md) · [Português (Brasil)](README.pt-BR.md) · [Italiano](README.it.md) · [Polski](README.pl.md) · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · **日本語**

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml) [![coverage 100%](https://img.shields.io/badge/coverage-100%25-brightgreen)](../testing.md)

_このページは英語から機械翻訳されました。修正を歓迎します。詳しくは [CONTRIBUTING.md](../../CONTRIBUTING.md) をご覧ください。_

いくつもの多言語サイトを 1 つの管理パネルで扱える、Laravel 13 ベースのツールです。既存の 1C-Bitrix サイトを移行するマイグレーターも付属しています。

モジュールは、フィールドと一覧の列を持つモデルとして一度記述するだけです。Atrium はそこから、検索と並べ替えができる一覧、タブ付きのフォーム、メニュー項目、API を用意し、テーマを入れれば公開ページも表示します。

![デモショップの商品一覧](../images/admin-list.webp)

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

| レコードのフォーム | 管理画面の検索 |
|---|---|
| ![レコードのフォーム](../images/admin-form.webp) | ![管理画面の検索](../images/admin-search.webp) |

| メニュー | ダッシュボード |
|---|---|
| ![メニュー](../images/admin-menus.webp) | ![ダッシュボード](../images/admin-dashboard.webp) |

| サイト（ライトテーマ） | サイト（ダークテーマ） |
|---|---|
| ![サイト（ライトテーマ）](../images/site-light.webp) | ![サイト（ダークテーマ）](../images/site-dark.webp) |

## 品質

- プルリクエストごとに 6,500 件以上の PHPUnit テストと 80 件以上の Laravel Dusk ブラウザテストが実行されます。
- SQLite、MySQL、PostgreSQL 上で、PHP 8.3 と 8.4 を使って。
- すべてのパッケージで行カバレッジは 100% で、テストのない行を残す変更は CI が受け付けません。
- 変更したコードにはミューテーションスコア 100% が必要です。Infection が変更行をひとつずつ書き換え、そのたびにどれかのテストが失敗しなければなりません。
- これらのチェックに例外はありません。無視用のアノテーションも、無効化したミューテーターもありません。
- push のたびに、既知の脆弱性を持つ依存関係と、git 履歴に残った秘密情報を検査します。

[テストとチェックの仕組み](../testing.md).

## 試してみる

Docker があれば、ほかには何も要りません。

```bash
git clone -b stable https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

初回の起動には数分かかります。その後 http://localhost:8000/admin を開き、`admin@example.com`、パスワード `password` でログインしてください（ローカルでの試用専用）。サイトは http://localhost:8000 で開けます。

Docker を使わない場合は、PHP 8.3 以降と Composer が必要です。データベースはデフォルトで SQLite、管理パネルはビルド済みなので、MySQL も Node も要りません。

```bash
git clone -b stable https://github.com/AlveBy/atrium.git
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
- [公開デモ：Docker、1時間ごとのリセット、訪問者にできること、置き場所](../demo.md)
- [設定：メールとサインインコード、ファイル、API トークン、MCP 経由の AI アシスタント](../configuration.md)
- [AI 文章アシスタント：無料・有料のプロバイダー、説明文、SEO、alt テキスト、翻訳](../ai.md)
- [SEO：タイトル、hreflang、サイトマップ、schema.org、リダイレクト、SEOチェック](../seo.md)
- [メニュー：ヘッダーとフッター、リンクのツリー、モジュールを展開する項目](../menus.md)
- [フォーム：列で組み立てるビルダー、ページを再読み込みせずに送信、ウィンドウで表示、独自のマークアップと fetch](../forms.md)
- [埋め込み：テキスト内のフォームやブロック、モジュールが独自の種類を登録する方法](../embeds.md)
- [ブロック：電話番号やバナーを一度書けば、テンプレートとテキストに表示](../blocks.md)
- [検索：結果ページ、管理画面の検索ボックス、検索対象と仕組み](../search.md)
- [独自モジュール：フィールド、列、タブ、権限、言語、テスト、パッケージ化](../modules.md)
- [拡張：プラットフォーム設計 — ノーコードビルダー、数式、プラグイン、外部アプリ、計画](../extensions.md)
- [カスタムフィールド：管理画面から任意のモジュールにフィールドを追加、10 万件でのフィルターとファセット](../custom-fields.md)
- [インポートとエクスポート: どのモジュールでも CSV と Excel、列の対応、書き込み前のチェック、コンソールとスケジュール](../import-export.md)
- [キャッシュ：数十のプロパティでも速いページ、Redis、ページキャッシュ、CDN ヘッダー](../cache.md)
- [1C-Bitrix からの移行（手順）](../bitrix.md)
- [WordPress からの移行：データベース接続、移行、チェック、301 マップ](../wordpress.md)
- [バックアップ：データベースとファイルを複数の保存先へ、スケジュール、ヘルスチェック、復元](../backup.md)
- [エラー：PHP・キュー・JavaScript のエラーをサイトのデータベースに保存、通知、Sentry、独自ドライバー](../errors.md)
- [速度と稼働状況：サーバーの計測、訪問者の Web Vitals、通知付きの死活監視](../monitoring.md)
- [連携：1C と Bitrix24 コネクターの基盤、再試行付きキュー、交換ログ、管理者への通知](../integrations.md)
- [Bitrix24：フォームの送信がリードまたは取引になる、サイト上のチャットウィジェット、貼り付けるだけの Webhook 1 つ](../bitrix24.md)
- [1C：標準のCommerceML交換で1C:Enterpriseからカタログを取り込み、交換画面で状況を確認](../1c.md)
- [CDN：画像、リサイズしたコピーと WebP/AVIF、キャッシュの削除、S3 へのアップロード](../cdn.md)
- [二要素サインイン：認証アプリ、パスキー、コード、サインイン履歴](../two-factor.md)
- [ソーシャルログイン：プロバイダー、アプリの登録、リダイレクトURL](../social-login.md)

## パッケージ

| パッケージ | 内容 |
|---|---|
| [`alveby/atrium`](../../packages/admin) | 管理パネル |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | 公開テーマ |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | 1C-Bitrix マイグレーター |
| [`alveby/atrium-ai`](../../packages/ai/README.md) | 管理画面の AI 文章アシスタント |
| [`alveby/atrium-custom-fields`](../../packages/custom-fields/README.md) | 管理画面から任意のモジュールに追加するフィールド（フィルターとファセット付き） |
| [`alveby/atrium-seo`](../../packages/seo/README.md) | メタテンプレート、hreflang、サイトマップ、schema.org、リダイレクト、SEOチェック |
| [`alveby/atrium-docs`](../../packages/docs/README.md) | Markdownから作るドキュメント（バージョンと検索つき） |
| [`alveby/atrium-backup`](../../packages/backup/README.md) | データベースとファイルのバックアップ、管理画面からの復元 |
| [`alveby/atrium-errors`](../../packages/errors/README.md) | サイトのデータベースでのエラー追跡、通知、Sentry |
| [`alveby/atrium-monitoring`](../../packages/monitoring/README.md) | サーバーと訪問者の速度、稼働状況と通知、外部サービスなし |
| [`alveby/atrium-integrations`](../../packages/integrations/README.md) | コネクターの基盤：再試行付きキュー、交換ログ、失敗時の通知 |
| [`alveby/atrium-bitrix24`](../../packages/bitrix24/README.md) | フォーム送信を Bitrix24 にリードまたは取引として登録、チャットウィジェット |
| [`alveby/atrium-1c`](../../packages/onec/README.md) | CommerceMLで1Cから商品・価格・在庫を取り込み |
| [`alveby/atrium-news`](../../packages/news/README.md) | ニュース |
| [`alveby/atrium-pages`](../../packages/pages/README.md) | 静的ページ（1C-Bitrix のページのインポートつき） |
| [`alveby/atrium-catalog`](../../packages/catalog/README.md) | 入れ子のセクションを持つ商品カタログ |
| [`alveby/atrium-brands`](../../packages/brands/README.md) | ロゴつきのブランド |
| [`alveby/atrium-stores`](../../packages/stores/README.md) | 地図上の店舗とオフィス |
| [`alveby/atrium-team`](../../packages/team/README.md) | 人、部署、連絡先 |
| [`alveby/atrium-faq`](../../packages/faq/README.md) | 質問と回答 |
| [`alveby/atrium-forms`](../../packages/forms/README.md) | フォームビルダーと送信データ |
| [`alveby/atrium-blocks`](../../packages/blocks/README.md) | 再利用ブロック：電話番号やバナーを一度書けば、どこにでも表示 |
| [`alveby/atrium-reviews`](../../packages/reviews/README.md) | 承認制のレビューと評価 |
| [`alveby/atrium-2fa`](../../packages/two-factor/README.md) | 二要素認証とパスワードレスのサインイン |
| [`alveby/atrium-social-login`](../../packages/social-login/README.md) | Google、Microsoft、Apple、GitHub、Telegram などでのサインイン |
| [`alveby/atrium-solutions`](../../packages/solutions/README.md) | コマンド1つで入れられる完成済みサイト |
| [`alveby/atrium-menus`](../../packages/menus/README.md) | 管理画面で作るメニュー |
| [`alveby/atrium-search`](../../packages/search/README.md) | 全モジュール対象のサイト内検索 |
| [`alveby/atrium-wordpress`](../../packages/wordpress/README.md) | WordPress インポーター（投稿、ページ、カスタム投稿タイプ、SEO、翻訳、チェックと 301 マップつき） |

パッケージは Packagist に公開されていますが、0.1 にタグが付くまでは開発版のみです。そのため次のように指定します。

```bash
composer require alveby/atrium:^0.2
php artisan atrium:install
```

## 動作要件

- PHP 8.3 以降
- Laravel 13
- SQLite、MySQL 8、または PostgreSQL

## 言語

管理パネルは英語、ロシア語、ウクライナ語、ベラルーシ語、カザフ語、ウズベク語、ドイツ語、フランス語、スペイン語、ポルトガル語（ブラジル）、イタリア語、ポーランド語、トルコ語、中国語（簡体字）、日本語に対応しています。英語とロシア語は人の手で書かれ、それ以外はこの README と同じく機械翻訳から始まりました。ネイティブスピーカーによる修正は、最初の貢献としてとても歓迎されます。詳しくは [CONTRIBUTING.md](../../CONTRIBUTING.md)（英語）をご覧ください。

## はじまり

Atrium は 2025 年 12 月に [AlveSoft](https://alve.by) で、お客様のサイト [arkaim.by](https://arkaim.by) の 1C-Bitrix を置き換えるための、自社の管理パネルとして生まれました。Bitrix と同じように編集者が使い慣れていて、しかも素の Laravel で動く管理パネルと、内容を欠けなく移せるマイグレーターが欲しかったのです。2026 年の夏からは、このパネルがそのサイトを本番で動かしています。編集者は毎日ここで内容を更新し、マイグレーターはカタログ全体とページ、フォームを移しました。

2026 年 11 月、これをみんなに公開することにしました。そのサイトだけに関わるものをすべて取り除き、残りを MIT ライセンスで公開しています。誰でも無料で使い、変更し、その上に自分のサイトを作れます。

## 今後の予定

[ロードマップ](../../ROADMAP.md)（英語）：1 コマンドでインストールできるモジュール、すぐに使えるサイト、WordPress と Drupal からのインポーター、オンラインショップ、1C と Bitrix24。

## コントリビュート

[CONTRIBUTING.md](../../CONTRIBUTING.md) では、プロジェクトのセットアップ、テストの実行、翻訳の方法を説明しています。セキュリティ上の問題は [SECURITY.md](../../SECURITY.md) に記載の方法で報告してください。

## ライセンス

MIT。詳しくは [LICENSE](../../LICENSE) をご覧ください。

Atrium がお役に立ったなら、サイトのフッターにある「Atrium で作成」のリンクを残していただけると嬉しいです。これはお願いであってライセンスの条件ではありません。MIT が求めるのは、コードの複製に著作権表示を残すことだけです。
