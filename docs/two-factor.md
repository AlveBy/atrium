# Two-factor sign-in

[English](two-factor.md) · [Русский](two-factor.ru.md)

`alveby/atrium-2fa` adds a second sign-in step to the panel and to the personal account, plus ways to sign in without a password. Every guard (`admin`, `account`, or one of your own) is set up separately.

- [Install](#install)
- [What people see](#what-people-see)
- [Who must pass a second step](#who-must-pass-a-second-step)
- [Factors](#factors)
- [Signing in without a password](#signing-in-without-a-password)
- [Recovery codes and trusted devices](#recovery-codes-and-trusted-devices)
- [Limits and lockout](#limits-and-lockout)
- [The sign-in log and resets](#the-sign-in-log-and-resets)
- [SMS providers](#sms-providers)
- [Your own guard](#your-own-guard)
- [For other packages](#for-other-packages)
- [How secrets are kept](#how-secrets-are-kept)
- [Contrast](#contrast)

## Install

```bash
composer require alveby/atrium-2fa
php artisan atrium:module:install alveby/atrium-2fa
```

The command creates the tables and turns the module on. Until then the package does nothing: no routes, no buttons, and no second step at all. The e-mail code the panel used to have (`ATRIUM_2FA_ENABLED`) lives in this package now, so install it to keep that code. `atrium:module:remove alveby/atrium-2fa` turns it off again.

Defaults are in `config/atrium-2fa.php` (`php artisan vendor:publish --tag=atrium-2fa-config`). Most of them can be changed in the panel, in **Modules → Two-factor sign-in**, without a deployment. An empty field there keeps the config value.

## What people see

| Page | Panel | Account |
|---|---|---|
| Second step after the password | `/admin/auth/challenge` | `/account/auth/challenge` |
| Own factors, recovery codes, devices | `/admin/auth/security` | `/account/auth/security` |
| Sign in with a code by e-mail | `/admin/auth/code` | `/account/auth/code` |
| Sign in with a passkey | `/admin/auth/passkey` | `/account/auth/passkey` |
| Sign-in log | `/admin/auth/log` | — |
| People and resets | `/admin/auth/people` | — |

Account pages also exist for every content language (`/de/account/auth/security`). The panel menu gets a **Security** group: "My security" for everybody, plus the log and the people for whoever may set the module up (permission `two_factor.settings`).

The login screen shows only the ways that are switched on for the guard. With nothing extra switched on, it stays the plain e-mail and password form.

## Who must pass a second step

`guards.<guard>.required`, or "Second step for …" in the panel:

| Value | Meaning |
|---|---|
| `all` | Everybody. Whoever has no factor yet sets one up right after signing in, before they can open anything else. |
| `roles` | Superadmins and the roles in `required_roles` (panel only). Others may set up a factor themselves. |
| `optional` | Only whoever has set up a factor. |
| `null` | Admin only: keeps the old switch `ATRIUM_2FA_ENABLED`. `true` means `all`, `false` means `optional`, and also takes the e-mail factor off the panel's list (the switch used to turn that code off). |

`factors` lists the factors people of the guard may use. A factor taken off the list stops working at once, including for people who had it.

## Factors

**Authenticator app (TOTP, RFC 6238).** Any app that reads a QR code: Google Authenticator, Microsoft Authenticator, 1Password, Aegis, FreeOTP. The page shows the QR code and the key as text, for a phone without a camera. `totp.window` (default 1) accepts a code one 30-second step early or late. A code that was used once is refused the second time.

**Passkey (WebAuthn).** Touch ID, Windows Hello, a phone or a security key. The person names each passkey, so they can tell them apart later. `webauthn.rp_id` (`ATRIUM_WEBAUTHN_RP_ID`) defaults to the site's host. Set it to the parent domain to share passkeys between subdomains. Passkeys need HTTPS (or `localhost`).

**Code by e-mail.** Six digits to the account's address. This is the panel's old e-mail code, moved behind the same contract as the other factors.

**Code by SMS.** Six digits to a phone the person confirms first. Out of the box messages go to the log; see [SMS providers](#sms-providers).

A person may have several factors and choose one on the second-step page. They cannot remove their last factor while the second step is required for them.

## Signing in without a password

Both are switched on per guard (`magic_code`, `passwordless`). By default they are on for the account and off for the panel.

**Code by e-mail.** The person enters an address and gets six digits. The code lives `codes.ttl` minutes (10), works once, and is gone after `codes.max_attempts` wrong tries (5). Another code can be sent after `codes.resend_after` seconds (60). The answer is the same whether the address exists or not, and it takes the same time, so nobody can find out who has an account. The mail goes out after the response.

An e-mail code is one factor. If the person must pass a second step, the second step still follows, but the e-mail factor is not offered there again.

**Passkey alone.** A passkey is already two factors (the device and the fingerprint or PIN), so it signs a person in without a password and without a second step. The browser offers the passkeys it knows for the site.

## Recovery codes and trusted devices

When the first factor is set up, the person gets `recovery_codes` codes (10). They are shown once, on the security page; the person writes them down or keeps them in a password manager. Only their hashes are stored. Each code works once in place of a factor. "New codes" on the security page replaces all of them.

"Trust this device" on the second step skips the step on this browser for `trusted_days` days (30; 0 hides the box). The security page forgets all trusted devices with one button; an admin's reset forgets them too.

## Limits and lockout

`limits.per_minute` wrong answers a minute (5) for one person and address, then a wait. `limits.lockout_after` wrong answers in a row (10) lock the second step for `limits.lockout_minutes` (15). The lock and the end of it are in the log.

## The sign-in log and resets

The log keeps, for every guard: sign-ins (and by which way), second-step prompts, wrong answers, lockouts, codes sent, factors added and removed, recovery codes used and renewed, trusted devices, social accounts linked and unlinked (with `alveby/atrium-social-login`), and resets. It records the IP address and the browser, and can be filtered by guard, event and person.

**People** lists the people of a guard with their factors. **Reset** removes every factor, recovery code and trusted device of a person who lost their phone. The person signs in with the password again and, where it is required, sets up a factor anew. The reset is in the log with the name of the admin who did it.

## SMS providers

A driver is one class:

```php
use Atrium\TwoFactor\Contracts\SmsDriver;
use Illuminate\Support\Facades\Http;

final class MySmsDriver implements SmsDriver
{
    public function send(string $phone, string $message): void
    {
        Http::withToken(config('services.my_sms.token'))
            ->post('https://sms.example.test/send', ['to' => $phone, 'text' => $message])
            ->throw();
    }
}
```

```php
// config/atrium-2fa.php
'sms' => ['driver' => App\Sms\MySmsDriver::class],
```

Throw when the provider refuses: the person is told the message could not be sent, and the error goes to the log. Keep the token in `.env`, not in the code.

## Your own guard

Add a guard to `config/auth.php`, then to `guards` in `config/atrium-2fa.php`:

```php
'members' => [
    'enabled' => true,
    'prefix' => 'members/auth',
    'middleware' => ['web'],
    'localized' => false,
    'auth_middleware' => 'auth:members',
    'layout' => 'atrium-2fa::layouts.site',
    'login_route' => 'members.login',
    'active_column' => 'active',
    'required' => 'optional',
    'factors' => ['totp', 'passkey'],
    'passwordless' => true,
    'magic_code' => false,
    'trusted_days' => 0,
],
```

Your login controller hands the person to the second step instead of logging them in:

```php
return app(\Atrium\Admin\Auth\SignIn::class)->start($request, $user, 'members', 'password', $remember);
```

and asks for the extra buttons of the login screen:

```php
$methods = \Atrium\Admin\Facades\Hook::apply('atrium.auth.methods', [], 'members');
// [['key' => 'passkey', 'label' => '…', 'url' => '…', 'icon' => '<svg…>', 'kind' => 'link'], …]
```

## For other packages

| Hook | Kind | Arguments |
|---|---|---|
| `atrium.auth.methods` | filter | the methods, the guard. Add a button to the login screen. |
| `atrium.auth.signed_in` | action | the person, the guard, the way (`password`, `passkey`, `code`, `social:github`…). |
| `atrium.auth.event` | action | the guard, the person, the event, the way, details. Writes a line to the log. |
| `atrium.auth.other_ways_in` | filter | a count, the person, the guard. How many ways in the person has besides the one being removed. |

`alveby/atrium-social-login` uses all four: social buttons on the login screen, social sign-ins in the log, and it refuses to unlink a person's last way in.

## How secrets are kept

TOTP keys, phones and passkey data are encrypted with the application key (`APP_KEY`, AES-256 with a MAC) and sealed to the table and column they belong to. A value copied to another column is refused. Recovery codes and e-mail codes are stored as hashes only. Rotating `APP_KEY` means everybody sets up TOTP and SMS again (use `APP_PREVIOUS_KEYS` to avoid that).

## Contrast

Every colour of the pages comes from the panel's theme (`--atrium-*`), then from the site theme (`--bg`, `--text`…), then from a fallback. Nothing is told by colour alone: an error has a bar, a "!" mark and words; a success has a "✓"; the chosen factor is bold, has a thick border and `aria-current`; links are underlined.

WCAG 2.2 contrast ratios. AA asks for 4.5 for text and 3 for borders and focus rings.

| Theme | Text | Hints | Button text on button | Field border | Focus ring | Error text | Success mark |
|---|---|---|---|---|---|---|---|
| graphite | 17.7 | 7.6 | 5.2 | 3.7 | 5.2 | 5.7 | 5.0 |
| day | 17.9 | 7.6 | 6.3 | 3.2 | 6.3 | 5.7 | 5.0 |
| night | 14.0 | 7.2 | 9.3 | 3.2 | 8.1 | 7.5 | 9.0 |
| sand | 17.3 | 7.6 | 5.9 | 3.5 | 5.9 | 6.6 | 5.1 |
| contrast | 21.0 | 15.1 | 10.6 | 21.0 | 10.6 | 7.3 | 6.7 |
| site, fallback | 17.8 | 7.4 | 6.7 | 4.8 | 6.7 | 17.8 | 17.8 |

Measured against the card background. On the site without a theme, errors and successes use the text colour and rely on the bar and the mark. The QR code is always black on white (21.0), whatever the theme, so every phone reads it.

The social sign-in pages (`alveby/atrium-social-login`) use the same palette. Provider buttons carry the provider's name in words, never only a logo or a brand colour.
