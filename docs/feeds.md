# Feeds

[Русский](feeds.ru.md)

Feeds are addresses that other programs read: a news reader, a search engine's webmaster tool, a marketplace. A module that has one adds it by itself, for every site and every language.

- [RSS of the news](#rss-of-the-news)

## RSS of the news

The news module (`alveby/atrium-news`) has an RSS 2.0 feed with the latest news of the site.

| Address | What it is |
|---|---|
| `https://example.com/news.xml` | the feed in the site's default language |
| `https://example.com/de/news.xml` | the same in German |
| `https://example.com/site2/news.xml` | a site that lives in a folder of the domain |

The address follows the module's pages: it is the prefix under which the news are shown (`/news`) and `.xml`. Move the pages with `public_urls` in `config/atrium.php` and the feed moves with them (`/press.xml`). A module without an address of its own gets no feed.

What is in it:

- the news of this site only, active and already published (`active_from` is in the past or empty), newest first;
- for a language, only the news written in it: a name and a code in that language, as on the site;
- for each item the title, the absolute link (also its `guid`), the publication date (`active_from`, or the day the news was created when there is none), the preview text as plain text (the start of the full text when there is no preview) and the preview picture as an `enclosure` with its type and size;
- text is escaped, and characters XML forbids are dropped, so a title with `&`, `<` or a stray control character does not break the feed.

How many items: **Modules → News → Settings → News in the RSS feed**, from 1 to 100, 20 by default, for each site on its own. Read it with `Setting::get('news.rss_items')`.

The public theme (`alveby/atrium-theme`) adds `<link rel="alternate" type="application/rss+xml">` to the head of the news list and of every news page, in the language of the page, so readers and browsers find the feed by themselves. A theme of your own gets the same line through the `atrium.head` filter, which the core's `<x-atrium::head />` prints; without it, put the link in the template.

Check the feed with any validator, for example [validator.w3.org/feed](https://validator.w3.org/feed/).

### Submitting it

Readers need no submission: they take the address. Yandex reads RSS 2.0 for its "Fresh and relevant" section (up to 10 MB, over HTTP or HTTPS): add `https://example.com/news.xml` in [Yandex Webmaster](https://webmaster.yandex.com) under **Search appearance → Fresh and relevant**. The `sitemap.xml` section does not take RSS, so do not put the feed there. Google needs nothing: it finds the feed by the `<link rel="alternate">` in the head, and Search Console → **Sitemaps** accepts RSS too.
