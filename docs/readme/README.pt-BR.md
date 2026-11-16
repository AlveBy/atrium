# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [Español](README.es.md) · **Português (Brasil)** · [Italiano](README.it.md) · [Polski](README.pl.md) · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml)

_Esta página foi traduzida automaticamente do inglês. Correções são bem-vindas: veja o [CONTRIBUTING.md](../../CONTRIBUTING.md) (em inglês)._

Um único painel de administração para quantos sites multilíngues você precisar, construído sobre o Laravel 13, com um migrador que traz um site existente do 1C-Bitrix.

Você descreve um módulo uma única vez, como um modelo com seus campos e as colunas da lista. O Atrium entrega a ele uma lista com busca e ordenação, um formulário com abas, um item no menu, uma API e, com o tema, páginas públicas.

![A lista de produtos da loja de demonstração](../images/admin-list.png)

> **Antes do primeiro lançamento.** O Atrium funciona e é coberto por testes, mas a versão 0.1 ainda não saiu e os pacotes não estão no Packagist. Experimente a partir deste repositório.

## O que ele faz

- **Vários sites.** Um só painel e um só banco de dados para muitos sites, cada um identificado pelo domínio ou por uma pasta nele (`example.com/site2/`) e com seus próprios idiomas.
- **Multilíngue.** O painel fala 15 idiomas, e cada administrador escolhe o seu. Os idiomas de conteúdo são adicionados no painel, qualquer coluna de um módulo pode ser traduzível, e cada idioma tem seus próprios endereços (`/de/…`) e `hreflang`.
- **20 tipos de campo.** Texto, números, datas, listas, vínculos com outros módulos, imagens, galerias, arquivos, um editor visual, mapas, valores repetíveis. Os formulários têm abas, como no 1C-Bitrix.
- **Papéis e permissões.** Quem vê cada módulo e o que pode fazer nele: ver, criar, editar, excluir.
- **SEO sem código.** Títulos e descrições para cada registro, endereços legíveis com redirecionamento a partir dos antigos, `sitemap.xml` e `robots.txt` para cada site. Google Tag, Yandex Metrica e códigos de verificação são definidos por site no painel.
- **API.** Tokens limitados aos módulos e ações escolhidos; `/api/v1` descreve a si mesma. Assistentes de IA (Claude, ChatGPT, Cursor) se conectam a `/mcp` com o mesmo token e exatamente as suas permissões.
- **Código de acesso por e-mail.** O login em duas etapas vem ativado por padrão.
- **Migrador do 1C-Bitrix.** Infoblocks, propriedades, seções, formulários, layouts de listas, SEO e a biblioteca de mídia viram modelos, migrações e dados do Laravel, e um relatório compara o site antigo com o novo.
- **Tema público.** Um site neutro que desenha cada módulo a partir do formulário dele no painel, nos modos claro e escuro, legível com qualquer tipo de visão de cores.
- **Testado.** PHPUnit com SQLite, MySQL e PostgreSQL, e testes de navegador com Laravel Dusk.

| O formulário de um registro | O site, claro e escuro |
|---|---|
| ![Formulário de produto com abas](../images/admin-form.png) | ![Página de produto](../images/site-light.png) ![Notícia em alemão, tema escuro](../images/site-dark.png) |

## Experimente

Com Docker, você não precisa de mais nada:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

A primeira inicialização leva alguns minutos. Depois abra http://localhost:8000/admin e entre como `admin@example.com` com a senha `password` (apenas para testes locais). O site fica em http://localhost:8000.

Sem Docker, você precisa do PHP 8.3 ou mais recente e do Composer. O banco de dados padrão é SQLite, e o painel já vem compilado, então nem MySQL nem Node são necessários:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
composer install
cp .env.example .env
php artisan key:generate
php artisan atrium:install --demo
php artisan serve
```

O instalador pede o nome, o e-mail e a senha do primeiro administrador. O código de acesso é enviado por e-mail; numa máquina local sem e-mail, ele fica no final de `storage/logs/laravel.log`, ou você pode desativá-lo com `ATRIUM_2FA_ENABLED=false` no `.env`.

`--demo` adiciona uma pequena loja: produtos com todos os tipos de campo, marcas, pontos de venda, notícias em três idiomas e um segundo site. Para começar do zero, apague `database/database.sqlite` e instale de novo.

## Seu primeiro módulo

```bash
php artisan atrium:make-module Article
php artisan migrate
```

Isso cria um modelo, uma migração e uma linha em `config/atrium.php`, e “Articles” aparece no menu. O modelo descreve seu formulário e sua lista:

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

Para que o módulo tenha páginas no site, adicione-o a `public_urls` em `config/atrium.php`:

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

## Migrando do 1C-Bitrix

```bash
composer require alveby/atrium-bitrix --dev
php artisan atrium:bitrix:migrate
php artisan atrium:bitrix:parity
```

O banco de dados do Bitrix é apenas lido. As abas, a ordem dos campos e as colunas das listas ficam do jeito que os editores as tinham no Bitrix. `atrium:bitrix:sync` traz as edições feitas no site antigo enquanto ele ainda está no ar. Passo a passo: [packages/bitrix/README.md](../../packages/bitrix/README.md) (em inglês).

## Documentação

- [Instalação: servidor próprio, hospedagem com ISPmanager ou cPanel, Docker](../installation.md)
- [Configuração: e-mail e código de acesso, arquivos, tokens de API, assistentes de IA via MCP](../configuration.md)
- [Ajuda de redação com IA: provedores gratuitos e pagos, descrições, SEO, textos alt, traduções](../ai.md)
- [Seu próprio módulo: campos, colunas, abas, permissões, idiomas, testes, pacote](../modules.md)
- [Migrar do 1C-Bitrix, passo a passo](../bitrix.md)

## Pacotes

| Pacote | O que é |
|---|---|
| [`alveby/atrium`](../../packages/admin) | O painel de administração |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | O tema público |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | O migrador do 1C-Bitrix |
| [`alveby/atrium-ai`](../../packages/ai/README.md) | Ajuda de redação com IA no painel |
| [`alveby/atrium-docs`](../../packages/docs/README.md) | Documentação a partir de Markdown, com versões e busca |

Quando a 0.1 estiver no Packagist, uma aplicação Laravel 13 existente poderá receber o Atrium com:

```bash
composer require alveby/atrium
php artisan atrium:install
```

## Requisitos

- PHP 8.3 ou mais recente
- Laravel 13
- SQLite, MySQL 8 ou PostgreSQL

## Idiomas

O painel de administração fala inglês, russo, ucraniano, bielorrusso, cazaque, uzbeque, alemão, francês, espanhol, português (Brasil), italiano, polonês, turco, chinês (simplificado) e japonês. O inglês e o russo foram escritos à mão; os demais começaram como tradução automática, assim como este README. Correções de falantes nativos estão entre as melhores primeiras contribuições: veja o [CONTRIBUTING.md](../../CONTRIBUTING.md) (em inglês).

## Próximos passos

O [roadmap](../../ROADMAP.md) (em inglês): módulos que se instalam com um único comando, sites prontos, importadores do WordPress e do Drupal, uma loja virtual, 1C e Bitrix24.

## Como contribuir

O [CONTRIBUTING.md](../../CONTRIBUTING.md) explica como preparar o projeto, rodar os testes e traduzir. Relate problemas de segurança como descrito no [SECURITY.md](../../SECURITY.md).

## Licença

MIT, veja [LICENSE](../../LICENSE).

Se o Atrium ajudou você, por favor mantenha o link “Feito com Atrium” no rodapé do seu site. É um pedido, não uma condição da licença: a MIT exige apenas que o aviso de copyright permaneça nas cópias do código.
