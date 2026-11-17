# Вход через соцсети

[English](social-login.md) · [Русский](social-login.ru.md)

`alveby/atrium-social-login` позволяет входить в панель и в личный кабинет через Google, Microsoft, Microsoft Entra ID, Яндекс ID, Apple, GitHub, GitLab, Facebook, VK ID, Mail.ru, Telegram, LinkedIn или любой сервер OpenID Connect (Keycloak, Authentik, Zitadel, Okta, Auth0…). Построен на [Laravel Socialite](https://laravel.com/docs/socialite) и [SocialiteProviders](https://socialiteproviders.com).

- [Установка](#установка)
- [Как включить провайдера](#как-включить-провайдера)
- [Адреса возврата](#адреса-возврата)
- [Кого впускает](#кого-впускает)
- [Привязка в профиле](#привязка-в-профиле)
- [Регистрация приложения у каждого провайдера](#регистрация-приложения-у-каждого-провайдера)
- [Другой сервер OpenID Connect](#другой-сервер-openid-connect)
- [Свой guard](#свой-guard)
- [О безопасности](#о-безопасности)

## Установка

```bash
composer require alveby/atrium-social-login
php artisan atrium:module:install alveby/atrium-social-login
```

Если установлен и `alveby/atrium-2fa`, второй шаг после входа через соцсеть работает так же, как после пароля, а каждый вход, привязка и отвязка попадают в журнал входов.

## Как включить провайдера

**Вход через соцсети → Провайдеры** в панели (`/admin/auth/social/providers`, для тех, кому можно настраивать модуль, право `social_login.settings`). Для каждого провайдера:

1. Скопируйте адреса возврата со страницы в приложение, которое регистрируете у провайдера (см. ниже).
2. Вставьте client ID и client secret, которые выдал провайдер. Секрет хранится зашифрованным и больше не показывается; пустое поле оставляет сохранённый.
3. Отметьте, где показывать кнопку: в панели, в личном кабинете или там и там.

Экраны входа показывают кнопку только тех провайдеров, что включены для этого guard и у которых есть client ID. На каждой кнопке название провайдера написано словами.

## Адреса возврата

| Guard | Адрес возврата (callback) |
|---|---|
| Панель | `https://your-site.example/admin/auth/social/callback/{provider}` |
| Личный кабинет | `https://your-site.example/account/auth/social/callback/{provider}` |

`{provider}` — ключ: `google`, `microsoft`, `entra`, `yandex`, `apple`, `github`, `gitlab`, `facebook`, `vkid`, `mailru`, `telegram`, `linkedin`, `oidc`. Страница провайдера в панели показывает точные адреса для вашего `APP_URL`. Большинство провайдеров сверяют адрес буква в букву, включая `https` и без косой черты в конце.

## Кого впускает

Аккаунт соцсети находит человека одним из двух способов:

- Он уже **привязан** к человеку (в профиле или прошлым входом).
- Его почта совпадает с почтой человека, и провайдер **ручается за адрес**. Google, Apple, LinkedIn и OpenID Connect сообщают это в `email_verified`; GitHub и Facebook отдают только подтверждённые адреса; GitLab требует подтверждённый аккаунт; Яндекс и Mail.ru считаются только для своих ящиков. Microsoft, Entra ID, VK ID и Telegram по адресу не доверяются никогда: такой аккаунт сначала привязывают в профиле.

**Панель.** Через соцсеть никто не регистрируется. Она впускает только существующего активного администратора. Если заданы разрешённые домены (**Модули → Вход через соцсети**, по одному в строке, или `allowed_domains` в конфиге), адрес должен быть ещё и из них, и это проверяется первым — по ответу нельзя понять, есть ли такая учётная запись.

**Личный кабинет.** Неизвестный подтверждённый адрес создаёт учётную запись, если у guard `register` равно `true` (по умолчанию) и пакет кабинета умеет регистрировать. Иначе человеку говорят, что учётной записи нет.

Отключённые люди не войдут никаким способом.

## Привязка в профиле

**Привязанные аккаунты** (`/admin/auth/social/accounts`, `/account/auth/social/accounts`) показывают аккаунты соцсетей человека и провайдеров, которых ещё можно привязать. Привязка проходит тот же круг через провайдера и должна вернуться с тем же человеком. Аккаунт, уже привязанный к кому-то другому, не привяжется.

Отвязать нельзя, если у человека не останется способа войти: ни пароля, ни другой соцсети, ни входов без пароля из `alveby/atrium-2fa`.

## Регистрация приложения у каждого провайдера

Указывайте адрес возврата каждого guard, где должна быть кнопка. Секреты никогда не кладите в репозиторий — их вводят только в панели.

**Google.** [Google Cloud console](https://console.cloud.google.com/apis/credentials) → Credentials → Create credentials → OAuth client ID → Web application. Адреса возврата — в «Authorized redirect URIs». Заполните OAuth consent screen (название, почта поддержки, области `openid`, `email`, `profile`) и опубликуйте его, иначе войдут только тестовые пользователи.

**Microsoft (личные, рабочие и учебные).** [Microsoft Entra admin center](https://entra.microsoft.com) → App registrations → New registration. Supported account types: «Accounts in any organizational directory and personal Microsoft accounts». Платформа «Web», адреса возврата как выше. Client ID — это «Application (client) ID»; секрет создаётся в Certificates & secrets → New client secret (копируйте **Value**, а не ID; у секрета есть срок, запишите дату).

**Microsoft Entra ID (одна организация).** Та же регистрация с «Accounts in this organizational directory only». На странице провайдера укажите tenant: «Directory (tenant) ID» или домен вида `contoso.onmicrosoft.com`. Если поле пустое, войдёт любой рабочий или учебный аккаунт.

**Яндекс ID.** [oauth.yandex.ru](https://oauth.yandex.ru) → Создать приложение → Веб-сервисы. Добавьте адреса возврата и доступы «Доступ к адресу электронной почты» и «Доступ к логину, имени и фамилии…». Client ID и секрет — на странице приложения.

**Apple.** Нужен платный аккаунт Apple Developer и HTTPS на настоящем домене (`localhost` Apple не принимает). В [Certificates, Identifiers & Profiles](https://developer.apple.com/account/resources/identifiers/list): создайте App ID с «Sign in with Apple», затем Services ID; в его настройках укажите домен и адреса возврата как «Return URLs». Создайте ключ с «Sign in with Apple» и скачайте файл `.p8`. Client ID — это Services ID. Client secret — JWT, подписанный этим ключом (team ID, key ID, Services ID); Apple принимает его не дольше шести месяцев, так что заранее делайте новый. Почту и имя Apple отдаёт только при первом входе.

**GitHub.** [Settings → Developer settings → OAuth Apps](https://github.com/settings/developers) → New OAuth App. GitHub принимает один адрес возврата на приложение: регистрируйте по приложению на guard или только для панели. Берётся основной подтверждённый адрес человека.

**GitLab.** User settings → Applications (или Admin Area → Applications для всего сервера) → Add new application. Адреса возврата — по одному в строке, область `read_user`, «Confidential» включено. Для своего GitLab укажите его адрес (`https://gitlab.example.test`) на странице провайдера.

**Facebook.** [Meta for Developers](https://developers.facebook.com/apps) → Create app → «Authenticate and request data from users with Facebook Login». В Facebook Login → Settings → «Valid OAuth Redirect URIs». App ID и App Secret — в App settings → Basic. Переведите приложение в режим Live.

**VK ID.** [id.vk.com/about/business](https://id.vk.com/about/business/go) → создайте приложение типа «Веб». Добавьте домен и адреса возврата в доверенные. Client ID — ID приложения, секрет — «Защищённый ключ». VK ID не даёт подтверждённой почты, поэтому его сначала привязывают в профиле.

**Mail.ru.** [o2.mail.ru/app](https://o2.mail.ru/app/) → создайте приложение, добавьте адреса возврата. Client ID и секрет — на странице приложения.

**Telegram.** Напишите [@BotFather](https://t.me/BotFather): `/newbot` создаёт бота и выдаёт токен; `/setdomain` привязывает бота к домену сайта (без `https://` и пути). На странице провайдера client ID — имя бота (username), client secret — токен. Telegram не даёт почты: его сначала привязывают в профиле. Подписанный ответ проверяется токеном и принимается в течение десяти минут.

**LinkedIn.** [LinkedIn Developers](https://www.linkedin.com/developers/apps) → Create app (нужна страница компании в LinkedIn). Products → добавьте «Sign In with LinkedIn using OpenID Connect». Auth → «Authorized redirect URLs for your app». Client ID и Primary Client Secret — на той же вкладке.

## Другой сервер OpenID Connect

Провайдер `oidc` работает с любым сервером, у которого есть discovery (`/.well-known/openid-configuration`). На его странице укажите **issuer** (для Keycloak `https://sso.example.test/realms/main`) и **подпись кнопки** («Корпоративный вход»). Issuer должен быть на HTTPS; HTTP допустим только для `localhost`. Зарегистрируйте конфиденциального клиента с адресами возврата и областями `openid email profile`.

Несколько серверов OpenID Connect сразу добавляются в `config/atrium-social.php`:

```php
'providers' => [
    // …
    'keycloak' => ['driver' => 'oidc', 'label' => 'Keycloak', 'settings' => ['issuer', 'label']],
],
```

## Свой guard

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

Кнопки приходят через хук `atrium.auth.methods`, как описано в [Двухфакторном входе](two-factor.ru.md#свой-guard). Человека, который регистрируется через соцсеть, создаёт регистратор guard; без него `register` работает как `false`:

```php
use Atrium\SocialLogin\SocialLogin;
use Laravel\Socialite\Contracts\User as SocialiteUser;

app(SocialLogin::class)->registerUsing('members', function (SocialiteUser $at, string $provider) {
    return Member::create(['name' => $at->getName() ?: $at->getEmail(), 'email' => $at->getEmail(), 'active' => true]);
});
```

Верните `null`, чтобы отказать: человеку скажут, что учётной записи нет.

## О безопасности

- У каждого круга через провайдера свой одноразовый state, привязанный к guard и провайдеру и живущий `state_ttl` минут (10), плюс PKCE для VK ID и OpenID Connect.
- Apple и Telegram отвечают со своих сайтов; маленькая страница пересылает этот ответ уже с вашего сайта, поэтому сессионной куке не нужно ходить между сайтами, а защита от CSRF остаётся включённой везде.
- Каждый отказ (неверный state, отмена, неподтверждённый адрес, чужой домен, нет учётной записи, отключён, уже привязан) попадает в журнал входов с причиной.
- Client secret шифруются ключом `APP_KEY`.
