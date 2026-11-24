# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [Español](README.es.md) · [Português (Brasil)](README.pt-BR.md) · [Italiano](README.it.md) · [Polski](README.pl.md) · [Türkçe](README.tr.md) · **简体中文** · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml) [![coverage 100%](https://img.shields.io/badge/coverage-100%25-brightgreen)](../testing.md)

_本页由英文机器翻译而来，欢迎指正，详见 [CONTRIBUTING.md](../../CONTRIBUTING.md)。_

一个管理面板即可管理任意数量的多语言站点，基于 Laravel 13 构建，并附带迁移工具，可将现有的 1C-Bitrix 站点整体迁移过来。

模块只需描述一次：一个模型，加上它的字段和列表列。Atrium 会为它生成带搜索和排序的列表、带标签页的表单、菜单项和 API，配合主题还能生成公开页面。

![演示商店的商品列表](../images/admin-list.webp)

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

| 记录表单 | 后台搜索 |
|---|---|
| ![记录表单](../images/admin-form.webp) | ![后台搜索](../images/admin-search.webp) |

| 菜单 | 仪表盘 |
|---|---|
| ![菜单](../images/admin-menus.webp) | ![仪表盘](../images/admin-dashboard.webp) |

| 网站，浅色主题 | 网站，深色主题 |
|---|---|
| ![网站，浅色主题](../images/site-light.webp) | ![网站，深色主题](../images/site-dark.webp) |

## 质量

- 每个 pull request 都会运行 6500 多个 PHPUnit 测试和 80 多个 Laravel Dusk 浏览器测试。
- 在 SQLite、MySQL 和 PostgreSQL 上，使用 PHP 8.3 和 8.4。
- 每个包的代码行覆盖率均为 100%，任何让某一行失去测试的改动都会被 CI 拒绝。
- 改动的代码必须达到 100% 的变异得分：Infection 会改动每一行变更，每一处改动都必须让某个测试失败。
- 这些检查没有任何例外：没有忽略注解，也没有被关闭的变异器。
- 每次 push 都会检查依赖中的已知漏洞，以及 git 历史中泄露的密钥。

[测试和检查如何运作](../testing.md).

## 试用

有 Docker 就够了，无需安装其他任何东西：

```bash
git clone -b stable https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

首次启动需要几分钟。然后打开 http://localhost:8000/admin，以 `admin@example.com` 和密码 `password` 登录（仅限本地试用）。站点位于 http://localhost:8000。

不使用 Docker 的话，需要 PHP 8.3 或更高版本以及 Composer。数据库默认使用 SQLite，管理面板也已预先构建，因此 MySQL 和 Node 都不需要：

```bash
git clone -b stable https://github.com/AlveBy/atrium.git
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
- [公开演示：Docker、每小时重置、访客能做什么、部署在哪里](../demo.md)
- [配置：邮件与登录验证码、文件、API 令牌、通过 MCP 接入的 AI 助手](../configuration.md)
- [AI 写作助手：免费与付费服务商、描述、SEO、alt 文本、翻译](../ai.md)
- [SEO：标题、hreflang、站点地图、schema.org、重定向、SEO 检查](../seo.md)
- [菜单：页眉与页脚、链接树、展开模块的菜单项](../menus.md)
- [表单：分栏构建器、无需刷新页面提交、在弹窗中打开、自定义标记与 fetch](../forms.md)
- [嵌入：文本中的表单或区块，以及模块如何注册自己的类型](../embeds.md)
- [区块：电话或横幅只写一次，在模板和文本中显示](../blocks.md)
- [搜索：结果页、后台搜索框、搜索内容与方式](../search.md)
- [自定义模块：字段、列、标签页、权限、语言、测试、打包](../modules.md)
- [扩展：平台设计 — 无代码构建器、公式、插件、外部应用、计划](../extensions.md)
- [自定义字段：在后台为任意模块添加字段，10 万条记录上的筛选与分面](../custom-fields.md)
- [导入与导出：任意模块的 CSV 和 Excel、列映射、写入前检查、命令行与定时任务](../import-export.md)
- [缓存：带几十个属性也很快的页面、Redis、页面缓存、CDN 头](../cache.md)
- [从 1C-Bitrix 迁移，分步说明](../bitrix.md)
- [从 WordPress 迁移：连接数据库、迁移、检查、301 对照表](../wordpress.md)
- [备份：数据库和文件同时存到多个位置、计划任务、健康检查、恢复](../backup.md)
- [错误：PHP、队列和 JavaScript 错误保存在站点数据库中，告警、Sentry 和自定义驱动](../errors.md)
- [速度与可用性：服务器耗时、访客的 Web Vitals、带告警的可用性检查](../monitoring.md)
- [集成：1C 与 Bitrix24 连接器的基础、带重试的队列、交换日志、发给管理员的告警](../integrations.md)
- [Bitrix24：表单提交变成线索或交易、网站上的聊天挂件、只需粘贴一个 Webhook](../bitrix24.md)
- [1C：通过标准 CommerceML 交换从 1C:Enterprise 导入目录，并有交换界面](../1c.md)
- [CDN：图片、缩放副本与 WebP/AVIF、清除缓存、上传到 S3](../cdn.md)
- [双重验证登录：验证器应用、通行密钥、验证码、登录日志](../two-factor.md)
- [社交登录：登录提供方、应用注册、重定向地址](../social-login.md)

## 软件包

| 包 | 说明 |
|---|---|
| [`alveby/atrium`](../../packages/admin) | 管理面板 |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | 公开主题 |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | 1C-Bitrix 迁移工具 |
| [`alveby/atrium-ai`](../../packages/ai/README.md) | 后台的 AI 写作助手 |
| [`alveby/atrium-custom-fields`](../../packages/custom-fields/README.md) | 在后台为任意模块添加的字段，支持筛选与分面 |
| [`alveby/atrium-seo`](../../packages/seo/README.md) | 元信息模板、hreflang、站点地图、schema.org、重定向和 SEO 检查 |
| [`alveby/atrium-docs`](../../packages/docs/README.md) | 由 Markdown 生成的文档，支持版本和搜索 |
| [`alveby/atrium-backup`](../../packages/backup/README.md) | 数据库和文件的备份，可在后台恢复 |
| [`alveby/atrium-errors`](../../packages/errors/README.md) | 在站点数据库中跟踪错误，告警、Sentry |
| [`alveby/atrium-monitoring`](../../packages/monitoring/README.md) | 服务器与访客的速度、可用性与告警，无需外部服务 |
| [`alveby/atrium-integrations`](../../packages/integrations/README.md) | 连接器的基础：带重试的队列、交换日志、失败告警 |
| [`alveby/atrium-bitrix24`](../../packages/bitrix24/README.md) | 表单提交以线索或交易形式进入 Bitrix24、聊天挂件 |
| [`alveby/atrium-1c`](../../packages/onec/README.md) | 通过 CommerceML 从 1C 导入商品、价格和库存 |
| [`alveby/atrium-news`](../../packages/news/README.md) | 新闻 |
| [`alveby/atrium-pages`](../../packages/pages/README.md) | 静态页面，支持导入 1C-Bitrix 页面 |
| [`alveby/atrium-catalog`](../../packages/catalog/README.md) | 带嵌套分类的商品目录 |
| [`alveby/atrium-brands`](../../packages/brands/README.md) | 带标志的品牌 |
| [`alveby/atrium-stores`](../../packages/stores/README.md) | 地图上的门店和办公室 |
| [`alveby/atrium-team`](../../packages/team/README.md) | 人员、部门和联系方式 |
| [`alveby/atrium-faq`](../../packages/faq/README.md) | 问答 |
| [`alveby/atrium-forms`](../../packages/forms/README.md) | 表单构建器和提交记录 |
| [`alveby/atrium-blocks`](../../packages/blocks/README.md) | 可复用区块：电话或横幅只写一次，随处显示 |
| [`alveby/atrium-reviews`](../../packages/reviews/README.md) | 带审核的评价和评分 |
| [`alveby/atrium-2fa`](../../packages/two-factor/README.md) | 双重验证和免密码登录 |
| [`alveby/atrium-social-login`](../../packages/social-login/README.md) | 使用 Google、Microsoft、Apple、GitHub、Telegram 等登录 |
| [`alveby/atrium-solutions`](../../packages/solutions/README.md) | 一条命令安装的现成网站 |
| [`alveby/atrium-menus`](../../packages/menus/README.md) | 在后台搭建的菜单 |
| [`alveby/atrium-search`](../../packages/search/README.md) | 覆盖所有模块的站内搜索 |
| [`alveby/atrium-wordpress`](../../packages/wordpress/README.md) | WordPress 导入器：文章、页面、自定义文章类型、SEO 和翻译，附带检查和 301 对照表 |

这些包已在 Packagist 上，但在 0.1 打标签之前只有开发版，所以需要写明版本：

```bash
composer require alveby/atrium:^0.2
php artisan atrium:install
```

## 系统要求

- PHP 8.3 或更高版本
- Laravel 13
- SQLite、MySQL 8 或 PostgreSQL

## 语言

管理面板支持英语、俄语、乌克兰语、白俄罗斯语、哈萨克语、乌兹别克语、德语、法语、西班牙语、葡萄牙语（巴西）、意大利语、波兰语、土耳其语、简体中文和日语。英语和俄语由人工编写；其余语言最初都是机器翻译，本 README 也是如此。母语者的修正是最好的首次贡献之一：参见 [CONTRIBUTING.md](../../CONTRIBUTING.md)（英文）。

## 缘起

Atrium 于 2025 年 12 月在 [AlveSoft](https://alve.by) 诞生，最初是我们自己的管理面板，用来替换一位客户的网站 [arkaim.by](https://arkaim.by) 上的 1C-Bitrix。我们想要一个编辑用起来和 Bitrix 一样顺手、但基于普通 Laravel 的管理面板，以及一个能把内容完整迁移过来的迁移工具。自 2026 年夏天起，这个面板已在该网站上正式运行：编辑每天都在里面维护内容，迁移工具也把整个目录、页面和表单都迁了过来。

2026 年 11 月，我们决定把它交给所有人。我们去掉了只属于那个网站的一切，把其余部分以 MIT 许可证发布，任何人都可以免费使用、修改，并在它之上搭建自己的网站。

## 下一步

[路线图](../../ROADMAP.md)（英文）：一条命令即可安装的模块、现成的站点、WordPress 和 Drupal 导入工具、网上商店、1C 和 Bitrix24。

## 参与贡献

[CONTRIBUTING.md](../../CONTRIBUTING.md) 介绍了如何搭建项目、运行测试以及参与翻译。安全问题请按照 [SECURITY.md](../../SECURITY.md) 中的说明报告。

## 许可证

MIT，参见 [LICENSE](../../LICENSE)。

如果 Atrium 对你有帮助，请保留网站页脚中的“使用 Atrium 构建”链接。这只是请求，并非许可证条件：MIT 许可证只要求在代码副本中保留版权声明。
