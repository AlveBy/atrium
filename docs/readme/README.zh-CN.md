# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [Español](README.es.md) · [Português (Brasil)](README.pt-BR.md) · [Italiano](README.it.md) · [Polski](README.pl.md) · [Türkçe](README.tr.md) · **简体中文** · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml)

_本页由英文机器翻译而来，欢迎指正，详见 [CONTRIBUTING.md](../../CONTRIBUTING.md)。_

一个管理面板即可管理任意数量的多语言站点，基于 Laravel 13 构建，并附带迁移工具，可将现有的 1C-Bitrix 站点整体迁移过来。

模块只需描述一次：一个模型，加上它的字段和列表列。Atrium 会为它生成带搜索和排序的列表、带标签页的表单、菜单项和 API，配合主题还能生成公开页面。

![演示商店的商品列表](../images/admin-list.png)

> **尚未发布首个版本。** Atrium 已经可以使用，并有测试覆盖，但 0.1 版本还没有发布，各个包也尚未上架 Packagist。请从本仓库试用。

## 功能

- **多站点**：一个管理面板、一个数据库即可服务多个站点，每个站点按域名或域名下的文件夹（`example.com/site2/`）识别，并拥有各自的语言。
- **多语言**：管理面板支持 15 种语言，每位管理员可自选界面语言。内容语言在管理面板中添加，模块的任意列都可以设为可翻译，每种语言都有自己的地址（`/de/…`）和 `hreflang`。
- **20 种字段类型**：文本、数字、日期、列表、指向其他模块的关联、图片、图库、文件、可视化编辑器、地图、可重复值。表单分为多个标签页，与 1C-Bitrix 一样。
- **角色与权限**：谁能看到哪个模块，以及能在其中做什么：查看、创建、编辑、删除。
- **无需写代码的 SEO**：每条记录都有标题和描述，地址清晰易读并自动从旧地址重定向，每个站点都有自己的 `sitemap.xml` 和 `robots.txt`。Google Tag、Yandex Metrica 和验证代码在管理面板中按站点设置。
- **API**：令牌只能访问选定的模块和操作；`/api/v1` 会自我描述。AI 助手（Claude、ChatGPT、Cursor）用同一个令牌连接 `/mcp`，权限与该令牌完全一致。
- **邮件登录验证码**：两步登录默认开启。
- **1C-Bitrix 迁移工具**：信息块、属性、分类、表单、列表布局、SEO 和媒体库会转换为 Laravel 模型、迁移和数据，并生成一份报告对比新旧站点。
- **公开主题**：一个风格中性的站点，按照管理面板中的表单渲染每个模块，提供浅色和深色两种模式，任何色觉类型都能清晰阅读。
- **经过测试**：PHPUnit 覆盖 SQLite、MySQL 和 PostgreSQL，浏览器测试使用 Laravel Dusk。

| 记录的表单 | 站点：浅色与深色 |
|---|---|
| ![带标签页的商品表单](../images/admin-form.png) | ![商品页面](../images/site-light.png) ![德语新闻，深色主题](../images/site-dark.png) |

## 试用

有 Docker 就够了，无需安装其他任何东西：

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

首次启动需要几分钟。然后打开 http://localhost:8000/admin，以 `admin@example.com` 和密码 `password` 登录（仅限本地试用）。站点位于 http://localhost:8000。

不使用 Docker 的话，需要 PHP 8.3 或更高版本以及 Composer。数据库默认使用 SQLite，管理面板也已预先构建，因此 MySQL 和 Node 都不需要：

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
composer install
cp .env.example .env
php artisan key:generate
php artisan atrium:install --demo
php artisan serve
```

安装程序会询问第一位管理员的姓名、电子邮件和密码。登录验证码通过电子邮件发送；在没有配置邮件的本地机器上，验证码位于 `storage/logs/laravel.log` 的末尾，也可以在 `.env` 中设置 `ATRIUM_2FA_ENABLED=false` 将其关闭。

`--demo` 会添加一个小型商店：包含所有字段类型的商品、品牌、门店、三种语言的新闻，以及第二个站点。如需从头开始，删除 `database/database.sqlite` 后重新安装即可。

## 你的第一个模块

```bash
php artisan atrium:make-module Article
php artisan migrate
```

该命令会生成一个模型、一个迁移，并在 `config/atrium.php` 中添加一行，菜单中随即出现“Articles”。模型描述自己的表单和列表：

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

要让模块在站点上拥有页面，请将它添加到 `config/atrium.php` 的 `public_urls` 中：

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

## 从 1C-Bitrix 迁移

```bash
composer require alveby/atrium-bitrix --dev
php artisan atrium:bitrix:migrate
php artisan atrium:bitrix:parity
```

Bitrix 数据库只读不写。标签页、字段顺序和列表列会保持编辑们在 Bitrix 中的设置。在旧站点仍在运行期间，`atrium:bitrix:sync` 可以补上在旧站点上做出的修改。分步说明：[packages/bitrix/README.md](../../packages/bitrix/README.md)（英文）。

## 文档

- [安装：自有服务器、ISPmanager 或 cPanel 虚拟主机、Docker](../installation.md)
- [配置：邮件与登录验证码、文件、API 令牌、通过 MCP 接入的 AI 助手](../configuration.md)
- [AI 写作助手：免费与付费服务商、描述、SEO、alt 文本、翻译](../ai.md)
- [自定义模块：字段、列、标签页、权限、语言、测试、打包](../modules.md)
- [从 1C-Bitrix 迁移，分步说明](../bitrix.md)

## 软件包

| 包 | 说明 |
|---|---|
| [`alveby/atrium`](../../packages/admin) | 管理面板 |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | 公开主题 |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | 1C-Bitrix 迁移工具 |
| [`alveby/atrium-ai`](../../packages/ai/README.md) | 后台的 AI 写作助手 |
| [`alveby/atrium-docs`](../../packages/docs/README.md) | 由 Markdown 生成的文档，支持版本和搜索 |

0.1 上架 Packagist 后，现有的 Laravel 13 应用可以这样安装 Atrium：

```bash
composer require alveby/atrium
php artisan atrium:install
```

## 系统要求

- PHP 8.3 或更高版本
- Laravel 13
- SQLite、MySQL 8 或 PostgreSQL

## 语言

管理面板支持英语、俄语、乌克兰语、白俄罗斯语、哈萨克语、乌兹别克语、德语、法语、西班牙语、葡萄牙语（巴西）、意大利语、波兰语、土耳其语、简体中文和日语。英语和俄语由人工编写；其余语言最初都是机器翻译，本 README 也是如此。母语者的修正是最好的首次贡献之一：参见 [CONTRIBUTING.md](../../CONTRIBUTING.md)（英文）。

## 缘起

Atrium 于 2025 年 12 月在 [AlveSoft](https://alve.by) 诞生，最初是我们自己的管理面板，用来替换某位客户网站上的 1C-Bitrix。我们想要一个编辑用起来和 Bitrix 一样顺手、但基于普通 Laravel 的管理面板，以及一个能把内容完整迁移过来的迁移工具。自 2026 年夏天起，这个面板已在该网站上正式运行：编辑每天都在里面维护内容，迁移工具也把整个目录、页面和表单都迁了过来。

2026 年 11 月，我们决定把它交给所有人。我们去掉了只属于那个网站的一切，把其余部分以 MIT 许可证发布，任何人都可以免费使用、修改，并在它之上搭建自己的网站。

## 下一步

[路线图](../../ROADMAP.md)（英文）：一条命令即可安装的模块、现成的站点、WordPress 和 Drupal 导入工具、网上商店、1C 和 Bitrix24。

## 参与贡献

[CONTRIBUTING.md](../../CONTRIBUTING.md) 介绍了如何搭建项目、运行测试以及参与翻译。安全问题请按照 [SECURITY.md](../../SECURITY.md) 中的说明报告。

## 许可证

MIT，参见 [LICENSE](../../LICENSE)。

如果 Atrium 对你有帮助，请保留网站页脚中的“使用 Atrium 构建”链接。这只是请求，并非许可证条件：MIT 许可证只要求在代码副本中保留版权声明。
