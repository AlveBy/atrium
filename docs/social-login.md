# Social sign-in

[English](social-login.md) · [Русский](social-login.ru.md)

`alveby/atrium-social-login` lets people sign in to the panel and the personal account with Google, Microsoft, Microsoft Entra ID, Yandex ID, Apple, GitHub, GitLab, Facebook, VK ID, Mail.ru, Telegram, LinkedIn or any OpenID Connect server (Keycloak, Authentik, Zitadel, Okta, Auth0…). It is built on [Laravel Socialite](https://laravel.com/docs/socialite) and [SocialiteProviders](https://socialiteproviders.com).

- [Install](#install)
- [Switching a provider on](#switching-a-provider-on)
- [Redirect URLs](#redirect-urls)
- [Who may sign in](#who-may-sign-in)
- [Linking in the profile](#linking-in-the-profile)
- [Registering an app at each provider](#registering-an-app-at-each-provider)
- [Another OpenID Connect server](#another-openid-connect-server)
- [Your own guard](#your-own-guard)
- [Security notes](#security-notes)

## Install

```bash
composer require alveby/atrium-social-login
php artisan atrium:module:install alveby/atrium-social-login
```

With `alveby/atrium-2fa` installed as well, the second step applies after a social sign-in exactly as after a password, and every social sign-in, link and unlink is in the sign-in log.

## Switching a provider on

**Social sign-in → Providers** in the panel (`/admin/auth/social/providers`, for whoever may set the module up, permission `social_login.settings`). For each provider:

1. Copy the redirect URLs shown on the page into the app you register at the provider (see below).
2. Paste the client ID and the client secret the provider gave you. The secret is stored encrypted and never shown again; leave the field empty to keep the saved one.
3. Tick where the button appears: the panel, the personal account, or both.

The login screens show a button only for providers that are switched on for that guard and have a client ID. Each button has the provider's name in words.

## Redirect URLs

| Guard | Redirect (callback) URL |
|---|---|
| Panel | `https://your-site.example/admin/auth/social/callback/{provider}` |
| Personal account | `https://your-site.example/account/auth/social/callback/{provider}` |

`{provider}` is the key: `google`, `microsoft`, `entra`, `yandex`, `apple`, `github`, `gitlab`, `facebook`, `vkid`, `mailru`, `telegram`, `linkedin`, `oidc`. The provider page in the panel shows the exact URLs for your `APP_URL`. Most providers want the URL letter for letter, including `https` and without a trailing slash.

## Who may sign in

A social account finds a person in one of two ways:

- It was **linked** to the person before (in the profile, or by an earlier sign-in).
- Its e-mail address matches the person's, and the provider **vouches for the address**. Google, Apple, LinkedIn and OpenID Connect say so in `email_verified`; GitHub and Facebook give only confirmed addresses; GitLab needs a confirmed account; Yandex and Mail.ru count only for their own mailboxes. Microsoft, Entra ID, VK ID and Telegram are never trusted by address: such an account has to be linked from the profile first.

**Panel.** Nobody registers through a social account. It signs in only an existing, active administrator. With allowed domains set (**Modules → Social sign-in**, one per line, or `allowed_domains` in the config), the address must also be in one of them, and this is checked before anything else, so the answer does not tell whether an account exists.

**Personal account.** An unknown verified address creates an account when `register` is `true` for the guard (the default) and the account package can register people. Otherwise the person is told there is no account.

Deactivated people cannot sign in, whichever way they try.

## Linking in the profile

**Linked accounts** (`/admin/auth/social/accounts`, `/account/auth/social/accounts`) lists the person's social accounts and the providers they can still link. Linking runs the same provider round trip and must come back with the same person. An account that is already linked to someone else is refused.

Unlinking is refused when it would leave the person no way in: no password, no other social account, and none of the passwordless ways of `alveby/atrium-2fa`.

## Registering an app at each provider

Use the redirect URL of every guard where the button should appear. Never put the secrets in the repository; they are entered in the panel only.

**Google.** [Google Cloud console](https://console.cloud.google.com/apis/credentials) → Credentials → Create credentials → OAuth client ID → Web application. Add the redirect URLs under "Authorized redirect URIs". Fill in the OAuth consent screen (app name, support e-mail, the `openid`, `email` and `profile` scopes) and publish it, or only test users can sign in.

**Microsoft (personal, work and school).** [Microsoft Entra admin center](https://entra.microsoft.com) → App registrations → New registration. Supported account types: "Accounts in any organizational directory and personal Microsoft accounts". Platform "Web", redirect URLs as above. Client ID is the "Application (client) ID"; the secret is made under Certificates & secrets → New client secret (copy the **Value**, not the ID; it expires, so note the date).

**Microsoft Entra ID (one organisation).** The same registration with "Accounts in this organizational directory only". On the provider page, enter the tenant: the "Directory (tenant) ID" or a domain such as `contoso.onmicrosoft.com`. Left empty, any work or school account can sign in.

**Yandex ID.** [oauth.yandex.ru](https://oauth.yandex.ru) → Create app → Web services. Add the redirect URLs, and the permissions "Access to e-mail address" and "Access to user name, avatar…". Client ID and secret are on the app page.

**Apple.** Needs a paid Apple Developer account and HTTPS on a real domain (Apple does not accept `localhost`). In [Certificates, Identifiers & Profiles](https://developer.apple.com/account/resources/identifiers/list): create an App ID with "Sign in with Apple", then a Services ID; configure it with your domain and the redirect URLs as "Return URLs". Create a key with "Sign in with Apple" and download the `.p8` file. The client ID is the Services ID. The client secret is a JWT signed with that key (team ID, key ID, Services ID); Apple accepts one for six months at most, so make a new one before it expires. Apple shows the e-mail and name only on the first sign-in.

**GitHub.** [Settings → Developer settings → OAuth Apps](https://github.com/settings/developers) → New OAuth App. GitHub takes one callback URL per app: register one app per guard, or use the panel URL only. The person's primary verified address is used.

**GitLab.** User settings → Applications (or Admin Area → Applications for the whole instance) → Add new application. Redirect URLs one per line, scope `read_user`, "Confidential" on. For a self-managed GitLab, enter its address (`https://gitlab.example.test`) on the provider page.

**Facebook.** [Meta for Developers](https://developers.facebook.com/apps) → Create app → "Authenticate and request data from users with Facebook Login". Under Facebook Login → Settings → "Valid OAuth Redirect URIs". App ID and App Secret are under App settings → Basic. Switch the app to Live.

**VK ID.** [id.vk.com/about/business](https://id.vk.com/about/business/go) → create an app of the "Web" type. Add your domain and the redirect URLs as trusted. Client ID is the app ID, the secret is the "Protected key". VK ID gives no verified address, so people link it from the profile first.

**Mail.ru.** [o2.mail.ru/app](https://o2.mail.ru/app/) → create an app, add the redirect URLs. Client ID and secret are on the app page.

**Telegram.** Message [@BotFather](https://t.me/BotFather): `/newbot` creates a bot and gives its token; `/setdomain` binds the bot to your site's domain (without `https://` and the path). On the provider page, the client ID is the bot's username, the client secret is the token. Telegram gives no e-mail: people link it from the profile first. The signed answer is checked with the token and is accepted for ten minutes.

**LinkedIn.** [LinkedIn Developers](https://www.linkedin.com/developers/apps) → Create app (it needs a LinkedIn company page). Products → add "Sign In with LinkedIn using OpenID Connect". Auth → "Authorized redirect URLs for your app". Client ID and Primary Client Secret are on the same tab.

## Another OpenID Connect server

The `oidc` provider talks to any server with discovery (`/.well-known/openid-configuration`). On its page enter the **issuer** (for Keycloak `https://sso.example.test/realms/main`) and the **button label** people see ("Company sign-in"). The issuer must use HTTPS; plain HTTP is allowed only for `localhost`. Register a confidential client with the redirect URLs and the scopes `openid email profile`.

More OpenID Connect servers at once are added in `config/atrium-social.php`:

```php
'providers' => [
    // …
    'keycloak' => ['driver' => 'oidc', 'label' => 'Keycloak', 'settings' => ['issuer', 'label']],
],
```

## Your own guard

```php
// config/atrium-social.php
'guards' => [
    'members' => [
        'enabled' => true,
        'prefix' => 'members/auth/social',
        'middleware' => ['web'],
        'localized' => false,
        'auth_middleware' => 'auth:members',
        'layout' => 'atrium-social::layouts.site',
        'login_route' => 'members.login',
        'active_column' => 'active',
        'allowed_domains' => [],
        'register' => false,
    ],
],
```

The buttons come from the `atrium.auth.methods` hook, as described in [Two-factor sign-in](two-factor.md#your-own-guard). A person who registers through a social account is created by a registrar of the guard; without one, `register` acts as `false`:

```php
use Atrium\SocialLogin\SocialLogin;
use Laravel\Socialite\Contracts\User as SocialiteUser;

app(SocialLogin::class)->registerUsing('members', function (SocialiteUser $at, string $provider) {
    return Member::create(['name' => $at->getName() ?: $at->getEmail(), 'email' => $at->getEmail(), 'active' => true]);
});
```

Return `null` to refuse; the person is told there is no account.

## Security notes

- The round trip carries its own one-time state, tied to the guard and the provider and valid for `state_ttl` minutes (10), plus PKCE for VK ID and OpenID Connect.
- Apple and Telegram answer from their own sites; a small page re-sends that answer from your site, so no session cookie has to cross sites and CSRF protection stays on everywhere else.
- Every refusal (wrong state, cancelled, unverified address, wrong domain, no account, deactivated, already linked) is in the sign-in log with the reason.
- Client secrets are encrypted with `APP_KEY`.
