# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [Español](README.es.md) · **Português (Brasil)** · [Italiano](README.it.md) · [Polski](README.pl.md) · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml) [![coverage 100%](https://img.shields.io/badge/coverage-100%25-brightgreen)](../testing.md)

_Esta página foi traduzida automaticamente do inglês. Correções são bem-vindas: veja o [CONTRIBUTING.md](../../CONTRIBUTING.md) (em inglês)._

Um único painel de administração para quantos sites multilíngues você precisar, construído sobre o Laravel 13, com um migrador que traz um site existente do 1C-Bitrix.

Você descreve um módulo uma única vez, como um modelo com seus campos e as colunas da lista. O Atrium entrega a ele uma lista com busca e ordenação, um formulário com abas, um item no menu, uma API e, com o tema, páginas públicas.

![A lista de produtos da loja de demonstração](../images/admin-list.webp)

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

| O formulário de um registro | Busca no painel |
|---|---|
| ![O formulário de um registro](../images/admin-form.webp) | ![Busca no painel](../images/admin-search.webp) |

| Menus | Painel |
|---|---|
| ![Menus](../images/admin-menus.webp) | ![Painel](../images/admin-dashboard.webp) |

| O site, tema claro | O site, tema escuro |
|---|---|
| ![O site, tema claro](../images/site-light.webp) | ![O site, tema escuro](../images/site-dark.webp) |

## Qualidade

- Mais de 6.500 testes PHPUnit e mais de 80 testes de navegador com Laravel Dusk rodam em cada pull request.
- Em SQLite, MySQL e PostgreSQL, com PHP 8.3 e 8.4.
- Cada pacote tem 100% das linhas cobertas, e a CI recusa uma mudança que deixe uma linha sem teste.
- Código alterado precisa de pontuação de mutação de 100%: o Infection altera cada linha mudada, e um teste precisa falhar para cada alteração.
- Nada fica fora dessas verificações: nenhuma anotação de ignorar, nenhum mutador desligado.
- Cada push é verificado quanto a dependências com vulnerabilidades conhecidas e a segredos no histórico do git.

[Como os testes e as verificações funcionam](../testing.md).

## Experimente

Com Docker, você não precisa de mais nada:

```bash
git clone -b stable https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

A primeira inicialização leva alguns minutos. Depois abra http://localhost:8000/admin e entre como `admin@example.com` com a senha `password` (apenas para testes locais). O site fica em http://localhost:8000.

Sem Docker, você precisa do PHP 8.3 ou mais recente e do Composer. O banco de dados padrão é SQLite, e o painel já vem compilado, então nem MySQL nem Node são necessários:

```bash
git clone -b stable https://github.com/AlveBy/atrium.git
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
- [Demo pública: Docker, uma reinicialização a cada hora, o que um visitante pode fazer, onde hospedar](../demo.md)
- [Configuração: e-mail e código de acesso, arquivos, tokens de API, assistentes de IA via MCP](../configuration.md)
- [Ajuda de redação com IA: provedores gratuitos e pagos, descrições, SEO, textos alt, traduções](../ai.md)
- [SEO: títulos, hreflang, sitemaps, schema.org, redirecionamentos, a verificação de SEO](../seo.md)
- [Menus: cabeçalho e rodapé, uma árvore de links, itens que expandem um módulo](../menus.md)
- [Formulários: um construtor em colunas, envio sem recarregar a página, em uma janela, sua própria marcação e fetch](../forms.md)
- [Incorporações: um formulário ou bloco dentro de um texto e como um módulo registra o seu próprio tipo](../embeds.md)
- [Blocos: um telefone ou banner escrito uma vez e exibido em modelos e textos](../blocks.md)
- [Busca: a página de resultados, a caixa do painel, o que é buscado e como](../search.md)
- [Seu próprio módulo: campos, colunas, abas, permissões, idiomas, testes, pacote](../modules.md)
- [Extensões: o projeto da plataforma — construtor sem código, fórmulas, plugins, apps externos, o plano](../extensions.md)
- [Campos personalizados: adicionar campos a qualquer módulo pelo painel, filtros e facetas com 100 000 registros](../custom-fields.md)
- [Importar e exportar: CSV e Excel para qualquer módulo, colunas, uma verificação antes de gravar, o console e um agendamento](../import-export.md)
- [Histórico de registros: o registro de alterações de todos os módulos, quem alterou o quê e quando, sem senhas](../history.md)
- [Cache: páginas rápidas com dezenas de propriedades, Redis, cache de páginas, cabeçalhos para CDN](../cache.md)
- [Migrar do 1C-Bitrix, passo a passo](../bitrix.md)
- [Migrar do WordPress: conectar o banco, migrar, verificar, o mapa de 301](../wordpress.md)
- [Backups: bancos de dados e arquivos em vários destinos, agendamento, verificações, restauração](../backup.md)
- [Erros: erros de PHP, de filas e de JavaScript no banco do site, alertas, Sentry e drivers próprios](../errors.md)
- [Velocidade e disponibilidade: tempos do servidor, Web Vitals dos visitantes, verificações de disponibilidade com alertas](../monitoring.md)
- [Integrações: a base dos conectores de 1C e Bitrix24, fila com novas tentativas, registro de trocas, alertas para os administradores](../integrations.md)
- [Bitrix24: os envios de formulários viram leads ou negócios, o widget de chat no site, um único webhook colado](../bitrix24.md)
- [1C: o catálogo do 1C:Enterprise pela troca padrão CommerceML, uma tela de troca](../1c.md)
- [CDN: imagens, cópias redimensionadas e WebP/AVIF, limpeza de cache, uploads para S3](../cdn.md)
- [Acesso em duas etapas: apps autenticadores, passkeys, códigos, o registro de acessos](../two-factor.md)
- [Acesso social: provedores, registro do aplicativo, URLs de redirecionamento](../social-login.md)

## Pacotes

| Pacote | O que é |
|---|---|
| [`alveby/atrium`](../../packages/admin) | O painel de administração |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | O tema público |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | O migrador do 1C-Bitrix |
| [`alveby/atrium-ai`](../../packages/ai/README.md) | Ajuda de redação com IA no painel |
| [`alveby/atrium-custom-fields`](../../packages/custom-fields/README.md) | Campos adicionados a qualquer módulo pelo painel, com filtros e facetas |
| [`alveby/atrium-seo`](../../packages/seo/README.md) | Modelos de meta tags, hreflang, sitemaps, schema.org, redirecionamentos e uma verificação de SEO |
| [`alveby/atrium-docs`](../../packages/docs/README.md) | Documentação a partir de Markdown, com versões e busca |
| [`alveby/atrium-backup`](../../packages/backup/README.md) | Backups dos bancos de dados e arquivos, restauração pelo painel |
| [`alveby/atrium-errors`](../../packages/errors/README.md) | Registro de erros no banco do site, alertas, Sentry |
| [`alveby/atrium-monitoring`](../../packages/monitoring/README.md) | Velocidade do servidor e dos visitantes, disponibilidade e alertas, sem serviços externos |
| [`alveby/atrium-integrations`](../../packages/integrations/README.md) | A base dos conectores: fila com novas tentativas, registro de trocas, alerta em caso de falha |
| [`alveby/atrium-bitrix24`](../../packages/bitrix24/README.md) | Envios de formulários para o Bitrix24 como leads ou negócios, o widget de chat |
| [`alveby/atrium-1c`](../../packages/onec/README.md) | Produtos, preços e estoque do 1C via CommerceML |
| [`alveby/atrium-news`](../../packages/news/README.md) | Notícias |
| [`alveby/atrium-pages`](../../packages/pages/README.md) | Páginas estáticas, com importação de páginas do 1C-Bitrix |
| [`alveby/atrium-catalog`](../../packages/catalog/README.md) | Catálogo de produtos com seções aninhadas |
| [`alveby/atrium-brands`](../../packages/brands/README.md) | Marcas com logotipos |
| [`alveby/atrium-stores`](../../packages/stores/README.md) | Lojas e escritórios em um mapa |
| [`alveby/atrium-team`](../../packages/team/README.md) | Pessoas, departamentos e contatos |
| [`alveby/atrium-faq`](../../packages/faq/README.md) | Perguntas e respostas |
| [`alveby/atrium-tracker`](../../packages/tracker/README.md) | Tarefas e projetos para quem trabalha no painel |
| [`alveby/atrium-forms`](../../packages/forms/README.md) | Construtor de formulários e envios |
| [`alveby/atrium-blocks`](../../packages/blocks/README.md) | Blocos reutilizáveis: um telefone ou banner escrito uma vez e exibido em qualquer lugar |
| [`alveby/atrium-reviews`](../../packages/reviews/README.md) | Avaliações e notas com moderação |
| [`alveby/atrium-2fa`](../../packages/two-factor/README.md) | Acesso em duas etapas e sem senha |
| [`alveby/atrium-social-login`](../../packages/social-login/README.md) | Acesso com Google, Microsoft, Apple, GitHub, Telegram e outros |
| [`alveby/atrium-solutions`](../../packages/solutions/README.md) | Sites prontos instalados com um comando |
| [`alveby/atrium-menus`](../../packages/menus/README.md) | Menus montados no painel |
| [`alveby/atrium-search`](../../packages/search/README.md) | Busca no site inteiro, em cada módulo |
| [`alveby/atrium-wordpress`](../../packages/wordpress/README.md) | O importador do WordPress: posts, páginas, tipos de conteúdo próprios, SEO e traduções, com verificação e mapa de 301 |

Os pacotes já estão no Packagist, mas enquanto a 0.1 não tiver tag, só como versão de desenvolvimento; por isso ela precisa ser indicada:

```bash
composer require alveby/atrium:^0.2
php artisan atrium:install
```

## Requisitos

- PHP 8.3 ou mais recente
- Laravel 13
- SQLite, MySQL 8 ou PostgreSQL

## Idiomas

O painel de administração fala inglês, russo, ucraniano, bielorrusso, cazaque, uzbeque, alemão, francês, espanhol, português (Brasil), italiano, polonês, turco, chinês (simplificado) e japonês. O inglês e o russo foram escritos à mão; os demais começaram como tradução automática, assim como este README. Correções de falantes nativos estão entre as melhores primeiras contribuições: veja o [CONTRIBUTING.md](../../CONTRIBUTING.md) (em inglês).

## Como tudo começou

O Atrium surgiu em dezembro de 2025 na [AlveSoft](https://alve.by) como nosso próprio painel de administração, para substituir o 1C-Bitrix no site de um cliente, [arkaim.by](https://arkaim.by). Queríamos um painel tão familiar para os editores quanto o Bitrix, mas feito em Laravel puro, e um migrador que levasse o conteúdo sem perder nada. Desde o verão de 2026 o painel mantém esse site em produção: os editores trabalham nele todos os dias, e o migrador transferiu todo o catálogo, as páginas e os formulários.

Em novembro de 2026 decidimos abrir o projeto para todos. Removemos tudo o que pertencia àquele único site e publicamos o resto sob a licença MIT, para que qualquer pessoa possa usá-lo, modificá-lo e construir seus sites nele gratuitamente.

## Próximos passos

O [roadmap](../../ROADMAP.md) (em inglês): módulos que se instalam com um único comando, sites prontos, importadores do WordPress e do Drupal, uma loja virtual, 1C e Bitrix24.

## Como contribuir

O [CONTRIBUTING.md](../../CONTRIBUTING.md) explica como preparar o projeto, rodar os testes e traduzir. Relate problemas de segurança como descrito no [SECURITY.md](../../SECURITY.md).

## Licença

MIT, veja [LICENSE](../../LICENSE).

Se o Atrium ajudou você, por favor mantenha o link “Feito com Atrium” no rodapé do seu site. É um pedido, não uma condição da licença: a MIT exige apenas que o aviso de copyright permaneça nas cópias do código.
