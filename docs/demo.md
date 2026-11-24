# A public demo

A demo of Atrium that anybody may sign in to, change and break, and that puts itself back as it was every hour. It is one container: the demo shop and the example sites, one administrator, and a scheduler that resets everything.

## Try it

You need Docker. From a checkout of this repository:

```bash
docker compose -f docker/demo/compose.yaml up --build
```

Open <http://localhost:8000/admin>. The sign-in page of a demo says how to get in: a note above the form shows the e-mail and the password (`demo@atrium.test` and `demo`, or what `ATRIUM_DEMO_EMAIL` and `ATRIUM_DEMO_PASSWORD` say), and both fields are already filled in, so one click on **Sign in** is enough. The note is in the language of the page and readable in all five themes (text 7.2:1 to 14.7:1 on its background, the frame at least 4.2:1 against the card). On any other site the page is as it was. The site itself is at <http://localhost:8000>.

Without Compose:

```bash
docker build -f docker/demo/Dockerfile -t atrium-demo .
docker run --rm -p 8000:8000 atrium-demo
```

## What the visitor can and cannot do

The `demo` administrator is not a superadmin. Its role, "Demo", is made by the reset:

- **Edits** the content of every module: products, news, pages, menus, FAQ, forms, team, SEO and the rest, with uploads (2 MB at most, five files in one request).
- **Imports and exports** the same sections from a CSV or Excel file. A row whose image is an address (`https://…`) is not imported, and the check before the import already says so: the demo never makes the server download a file from an address someone typed in.
- **Looks at** administrators, sites, languages, uptime checks and incidents, and changes none of them. These are the sections in `atrium.demo.read_only`.
- **Has no** roles, API tokens, cache screen, module settings, Modules screen or backups.
- **Cannot change** their own name, e-mail or password. The panel answers 403, and so does the users list for the demo account itself (a second line of defence: the role has no right to edit administrators anyway).
- **Cannot set up a second step of sign-in.** With `alveby/atrium-2fa` installed, the security page shows "Not available in the demo" instead of its forms, and the server answers 403 to every request that would add, confirm or remove an authenticator app, a passkey, a phone or the e-mail code, make new recovery codes, forget trusted devices, reset a person's factors or change the module's settings. Nothing is required of the demo account either, whatever the module's settings say. A visitor can therefore never lock the next one out. The log and the list of people stay readable.

The sign-in code is off (`ATRIUM_2FA_ENABLED=false`), mail goes to the log, and nobody knows the password of the account the installer creates before the reset replaces it. Signing in with a social account can be linked and unlinked in the profile of the demo, which cannot lock anybody out: the password is always there.

## Languages

The demo has three content languages, English, German and Russian, and two sites (the shop in English, the outlet in German). The news, pages, FAQ, team, menus and custom fields come in all three; the panel's own words and the demo's sample **Articles** model are in all 15 languages of the panel. The catalog (sections and products), the brands and the stores keep one plain text per record, as Bitrix does, so their names and descriptions stay in English until those modules get a column per language.

## The reset

```bash
php artisan atrium:demo:reset
```

It deletes everything in the database and refills it with the demo content, removes the files visitors uploaded to the `public` disk, and makes the `demo` administrator and its role. Visitors are signed out, because the sessions are in the database.

It refuses to run unless `ATRIUM_DEMO=true`, so on a real site it does nothing. The scheduler runs it every hour; `ATRIUM_DEMO_RESET` is a cron expression (`*/30 * * * *` for every half hour). The container starts `schedule:work` itself; on a server of your own add the usual `* * * * * php artisan schedule:run` cron line.

| Variable | Default | |
|---|---|---|
| `ATRIUM_DEMO` | `false` | Demo mode: the sign-in page shows the details, the reset works, the profile, module settings and the second step of sign-in are locked |
| `ATRIUM_DEMO_EMAIL` | `demo@atrium.test` | The visitor's sign-in |
| `ATRIUM_DEMO_PASSWORD` | `demo` | Their password |
| `ATRIUM_DEMO_RESET` | `0 * * * *` | When the scheduler resets the demo |
| `ATRIUM_AI_DEMO_DAILY_LIMIT` | `40` | AI calls a day for the whole demo (`0` = no cap), see below |
| `ATRIUM_AI_DEMO_STORE` | `file` | The cache store that keeps that count |

### AI in the demo

If the demo has the AI module (`alveby/atrium-ai`) and a provider key, mind the key's free quota: all visitors are one administrator, so nothing but a daily cap stops one of them from using it all. While `ATRIUM_DEMO=true` the module counts the calls of the whole site (the Generate dialog, the helper, the API and MCP) and, at `ATRIUM_AI_DEMO_DAILY_LIMIT`, says in the dialog that the day's limit is used up and answers the API with 429, without asking the provider. The count lives in the `file` cache store because the hourly reset empties the database (and a `database` cache with it). The dialog and the helper also tell visitors that the demo is public and personal data must not be typed in. See [ai.md](ai.md).

The demo's image sets `ATRIUM_THEME_SKIN=atrium`, the [Atrium skin](skins.md#the-atrium-skin): the public pages of the demo shop are drawn by it, in light and dark and in every language of the site. Remove the line to see the neutral theme.

## Putting it on the internet

The compose file listens on `127.0.0.1:8000` only. Put a reverse proxy with HTTPS in front, for example Caddy:

```
demo.example.com {
    reverse_proxy 127.0.0.1:8000
}
```

and set `APP_URL=https://demo.example.com` (in the environment of the compose file). Then:

- The container drops all capabilities, cannot gain new ones, and has 512 MB of memory, one CPU and 128 processes. The database is a SQLite file inside it, so there is nothing to back up and nothing to lose.
- Use a server of its own, or a throwaway VPS: a demo is a place where strangers may write files and run the application's code paths.
- The container can reach the internet. If you do not want that, give it an internal network and let only the proxy through.
- Rate-limit sign-ins and uploads in the proxy.

## Where to host it

| | Cost | Reset | Safety |
|---|---|---|---|
| A small VPS of your own (Hetzner, DigitalOcean, any) with Docker and Caddy | a few euros a month | the scheduler in the container | the server holds nothing else; the best option for a long-living demo |
| A server you already have | free | the same | the demo shares the machine with your sites: use the compose limits and a separate user |
| GitHub Codespaces (a "try it" button) | free allowance per account, the visitor pays with their own | every Codespace is its own copy and goes away | nothing of yours is exposed; needs a GitHub account and is not a public link |
| `docker run` on the visitor's machine | free | each run is new | nothing is exposed; needs Docker |
| A free container host (Render, Fly.io and the like) | free tiers change and sleep when idle | the scheduler does not run while it sleeps; the container starts fresh instead | fine, but the first visit is slow |

For a link anybody can open, a VPS with the compose file above is the simplest thing that works.
