# AI writing help

[English](ai.md) · [Русский](ai.ru.md)

The `alveby/atrium-ai` module puts a **Generate** link under the text fields of the admin. It writes descriptions, previews, SEO tags and picture alt texts, **improves** a text the editor has already written (spelling, punctuation, clarity) and translates a field into the site's other languages. The editor reads the text in a dialog and decides whether to use it. Nothing is saved until **Save**.

It runs on the official [Laravel AI SDK](https://laravel.com/docs/13.x/ai-sdk), so any provider the SDK knows will do. This guide sets it up with a free one. The full reference is in [packages/ai/README.md](../packages/ai/README.md).

- [1. Choose a provider](#1-choose-a-provider)
- [2. Install the module and add a key](#2-install-the-module-and-add-a-key)
- [3. A queue, if the site is busy](#3-a-queue-if-the-site-is-busy)
- [4. Choose the fields](#4-choose-the-fields)
- [5. Ask the helper](#5-ask-the-helper)
- [When it does not answer](#when-it-does-not-answer)

## 1. Choose a provider

| Provider | Price | Key | Your texts |
|---|---|---|---|
| Google Gemini | Free tier with daily limits | [aistudio.google.com/apikey](https://aistudio.google.com/apikey) | **On the free tier Google may use prompts and answers to improve its products, and people may read them** |
| Groq | Free tier with limits per minute and day | [console.groq.com/keys](https://console.groq.com/keys) | See Groq's terms |
| OpenRouter | Models ending in `:free` cost nothing, with limits | [openrouter.ai/keys](https://openrouter.ai/keys) | Depends on the model's host |
| Ollama | Free, on your own server | none | Never leave your server |
| Ollama Cloud | Free tier with limits | [ollama.com/settings/keys](https://ollama.com/settings/keys) | See Ollama's terms |
| YandexGPT, GigaChat, DeepSeek | Paid or with a trial; they work from Russia and Belarus | [see below](#from-russia-and-belarus) | Go to Yandex, Sber or DeepSeek |
| OpenAI, Anthropic, Mistral and others | Paid | their consoles | Paid API terms do not use them for training by default |

Gemini is the default because it is free, fast, understands pictures (for alt texts) and writes well in many languages. **If the records hold anything private** (prices not yet public, people's names, internal notes), use Ollama or a paid key instead.

You can list several. When the first one answers "too many requests" or does not answer in time, the next one is asked.

## 2. Install the module and add a key

```bash
composer require alveby/atrium-ai
php artisan atrium:module:install alveby/atrium-ai
```

Add the key to `.env` on the server. Atrium never shows it and never stores it anywhere else:

```dotenv
GEMINI_API_KEY=...
```

Two free providers are better than one: the second answers when the first is over its limit.

```dotenv
GEMINI_API_KEY=...
GROQ_API_KEY=...
```

Check:

```bash
php artisan atrium:ai:status
```

The table shows each kind of work (text, pictures, embeddings), its providers in order and whether each has a key. Open a form in the admin: text fields now have **Generate** under them.

### Ollama instead

Install [Ollama](https://ollama.com) on the server or next to it and pull a model, for example `ollama pull llama3.2` for text and `ollama pull gemma3:4b` for pictures. Then:

```dotenv
ATRIUM_AI_TEXT=ollama:llama3.2
ATRIUM_AI_VISION=ollama:gemma3:4b
OLLAMA_URL=http://127.0.0.1:11434
```

A small model on a server without a graphics card answers in tens of seconds. That is fine: the form does not wait for it.

### Ollama Cloud

The same models without a server of your own: a key from [ollama.com/settings/keys](https://ollama.com/settings/keys) and the address of the cloud.

```dotenv
OLLAMA_URL=https://ollama.com
OLLAMA_API_KEY=...
ATRIUM_AI_TEXT=ollama:gpt-oss:120b
```

The models to name are the ones [ollama.com](https://ollama.com/search?c=cloud) marks as cloud.

### A paid provider

Its key and its name in the chain:

```dotenv
OPENAI_API_KEY=...
ATRIUM_AI_TEXT=openai,gemini
ATRIUM_AI_VISION=openai
```

`provider:model` picks a model, otherwise the SDK's default for that provider is used. Every provider and the name of its key is in the SDK's `config/ai.php`.

### From Russia and Belarus

Gemini and Groq refuse these countries. Atrium notices (a 403, or "User location is not supported"), asks the next provider and does not ask that one again for an hour, but with only Gemini and Groq there is nobody to ask. Use one of these:

```dotenv
# YandexGPT: an API key of a service account with the role ai.languageModels.user
YANDEX_API_KEY=...
YANDEX_FOLDER_ID=b1g...

# GigaChat: the authorization key of a project in the GigaChat API
GIGACHAT_AUTH_KEY=...
GIGACHAT_CA_BUNDLE=/etc/ssl/russian-ca.pem

# DeepSeek
DEEPSEEK_API_KEY=...

ATRIUM_AI_TEXT=yandex,gigachat,deepseek
```

- **YandexGPT** takes the model `gpt://<folder_id>/yandexgpt/latest` from `YANDEX_FOLDER_ID`. For another model, write it in the chain: `yandex:gpt://b1g.../yandexgpt-lite/latest`.
- **GigaChat** works with `GIGACHAT_SCOPE=GIGACHAT_API_PERS` (the default; `GIGACHAT_API_B2B` and `GIGACHAT_API_CORP` for companies). Atrium trades the key for an access token and keeps it for its 30 minutes. GigaChat's servers use the certificates of the Russian Ministry of Digital Development, which the server does not know: download them from [gosuslugi.ru/crt](https://www.gosuslugi.ru/crt), put the root and the intermediate one after another into one PEM file and give its path in `GIGACHAT_CA_BUNDLE`. The check of the certificate is never turned off.
- **DeepSeek** needs only its key. Its servers are in China.

These three write texts. The `alt` button needs a model that sees pictures: Ollama (`ATRIUM_AI_VISION=ollama:gemma3:4b`) or an OpenRouter free model. Your texts go to the provider you chose; for private data use Ollama on your own server.

## 3. A queue, if the site is busy

Nothing to do for most sites. **Generate** answers at once, and the provider is called in the same PHP process right after that, while the dialog waits for the result. No queue worker is needed, as for the rest of Atrium.

When many editors generate at the same time, or a slow local model keeps PHP processes busy, move the work to a queue with a worker:

```dotenv
ATRIUM_AI_QUEUE_CONNECTION=database
ATRIUM_AI_QUEUE=ai
```

```bash
php artisan queue:work --queue=ai,default
```

Keep the worker running with Supervisor or systemd.

## 4. Choose the fields

The texts a site's editors write have the buttons already: the description of a product, a section, a brand, a news item, an article, a store, a team member (the preview is written from the full text) and a page, the answer to a FAQ question (from the question), and the SEO fields (title, description, keywords, picture alt). Visitors' texts (reviews, form submissions) and technical fields (head code, `robots.txt`, recipients, options) have none. In your own modules a field opts in with `->ai()`:

```php
ContentField::make('detail_text')->label('Description')->ai(),
ContentField::make('preview_text')->label('Preview')->ai('preview', from: 'detail_text'),
TextField::make('picture_alt')->label('Picture alt')->ai('alt', from: 'picture'),
```

Without arguments it writes the field from everything else in the form. `preview`, `seo_title`, `seo_description`, `seo_keywords` and `alt` are the other tasks (`improve` needs no setting: any field with `->ai()` has it); `from` names the fields to work from.

Every such field also gets **Improve** when it is not empty. It sends the text that is in the field now, as it is in the form, and gets it back corrected: spelling, punctuation and clarity, in the same language, with the same HTML (tags and links stay). It never adds facts and does not translate.

- **Text and Textarea fields, and a content field in Text or HTML mode:** the links are under the field: **Generate · Improve · Translate**.
- **A content field in the visual editor:** the same actions are in a menu behind the sparkle button at the right end of the editor's toolbar: **Generate**, **Improve** and **Translate**. Select a piece of the text first and the item reads **Improve the selection**: only that piece is sent, and only it is replaced when you accept. With nothing selected the whole text is improved. Pieces are replaced in place, so formatting around them stays.
- A very long text is not cut: the dialog says it is too long to improve in one go, and you improve it in parts, a selection at a time.

As with everything else, the result is only offered in the dialog (**Use this text** / edit / **Cancel**), and nothing is saved until **Save**.

A translatable field also gets **Translate** when the site has more than one content language: it translates the language being edited into the others. Every Atrium language works, and the model is told the script where it matters (Uzbek in Latin, Kazakh and Belarusian in Cyrillic).

The prompts are Blade views. To change the tone or add rules ("always mention free delivery"):

```bash
php artisan vendor:publish --tag=atrium-ai-prompts
```

and edit the files in `resources/views/vendor/atrium-ai/prompts`.

## 5. Ask the helper

Once a text provider has a key, every admin page has the Atrium arch in the bottom right corner. Click it and ask in your language:

- "How do I add a menu item?" It looks the answer up in these guides.
- "How many products do we have?", "Find the news about the sale." It reads the records and links to them.

It answers in the language you asked in, whatever the language of the panel. It reads only the modules you may open, and it changes nothing. With the monitoring module installed it can also tell how fast the site is. The conversation stays while the tab is open; **New conversation** starts over.

The arch shows what it is doing. It raises its brows while it thinks, smiles when the answer comes and looks sorry when it cannot answer. It holds a magnifier while it looks through the records and puts on glasses for the guides. When a field is being written it picks up a pencil, and around the New Year it wears a hat. Now and then it yawns, looks around or hops. It never opens or speaks on its own. With reduced motion switched on in the system it stays still, and only its face changes. A screen reader hears what it is doing. **Modules → AI → Playful helper** turns all of this off and leaves the calm arch.

The question and what the helper reads (guide sections, records) go to the provider. On a site with private data use Ollama or a paid key, or turn it off:

```dotenv
ATRIUM_AI_HELP=false
```

## On a public demo

With `ATRIUM_DEMO=true` ([demo.md](demo.md)) all visitors are one administrator. The module then gives the whole site `ATRIUM_AI_DEMO_DAILY_LIMIT` calls a day (40 by default, `0` = no cap), so that one visitor cannot use up the provider's free quota; when it is gone the dialog says so and the API answers 429. The dialog and the helper also say that the demo is public and personal data must not be typed in. Details: [packages/ai/README.md](../packages/ai/README.md#public-demo).

## When it does not answer

| The dialog says | Why | What to do |
|---|---|---|
| All AI providers are busy or over their free limit | Every provider in the chain answered with a limit or did not answer in time | Wait a minute, or add a second provider |
| Fill in the name or the text first | The form has nothing to work from | Fill in the name or the text it should use |
| Upload the image first | The alt text needs a picture saved on this site | Upload it; pictures on other sites are not fetched |
| The demo's daily AI limit is used up | A public demo allows a fixed number of calls a day for all visitors | Try again tomorrow, or raise `ATRIUM_AI_DEMO_DAILY_LIMIT` |
| The AI provider did not answer | Any other error: a wrong key, a model that does not exist | `storage/logs/laravel.log` has the details |

No buttons at all: `php artisan atrium:ai:status` shows whether a provider has a key, and `ATRIUM_AI_ENABLED` must not be `false`.

Every call is logged in the `ai_usage` table without the texts: task, provider, model, tokens, time. `php artisan atrium:ai:status --days=7` adds them up, which helps to see when a free limit is close.
