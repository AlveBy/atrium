# Плагины трекера

[English](tracker-plugins.md) · [Русский](tracker-plugins.ru.md)

Плагин — Composer-пакет, который добавляет что-то в [Трекер](tracker.ru.md), не меняя его: панель на карточке задачи, гаджет, отчёт, условие перехода, действие правила автоматизации. Он регистрируется в своём сервис-провайдере, а администратор может выключить его в настройках трекера.

**Что готово на сейчас.** Реестр, контракт и одиннадцать точек расширения есть и покрыты тестами. Точку *читает* тот этап [дорожной карты](tracker.ru.md#дорожная-карта), которому она принадлежит; каким должно быть описание, дописывается сюда, когда этап выходит. До тех пор плагин может регистрировать описания, и они ждут. Когда в платформе появятся точки расширения Atrium, плагины можно будет перенести на них: плагин говорит только с `Plugins`.

## Контракт

```php
use Atrium\Tracker\Plugins\Extensions;
use Atrium\Tracker\Plugins\TrackerPlugin;

interface TrackerPlugin
{
    public function key(): string;          // "git-links": строчные буквы, цифры, "-" и "_", до 40
    public function title(): string;        // название в настройках, на языке панели
    public function register(Extensions $extensions): void;
}
```

`register()` вызывается один раз, когда плагин передают реестру. Он добавляет своё через `$extensions->add($point, $key, $definition)`:

- `$point` — одна из констант `Atrium\Tracker\Plugins\Plugins` (ниже); неизвестная точка бросает `InvalidArgumentException`.
- `$key` уникален внутри точки; начинайте его с ключа плагина (`git-links.commits`). Занятый ключ бросает исключение, так что два плагина не затрут друг друга.
- `$definition` — то, что просит точка: массив, имя класса, замыкание.

## Точки расширения

| Константа | Что это |
|---|---|
| `FIELD_TYPES` | Вид своего поля: как показывается, хранится и проверяется |
| `TRANSITION_CONDITIONS` | Правило, которое переход проверяет, прежде чем состояться |
| `TRANSITION_VALIDATORS` | Проверка того, что человек ввёл на переходе |
| `TRANSITION_FUNCTIONS` | Работа, которая делается после перехода |
| `AUTOMATION_TRIGGERS` | Что запускает правило автоматизации |
| `AUTOMATION_ACTIONS` | Что делает правило автоматизации |
| `QUERY_FUNCTIONS` | Функция языка поиска |
| `TASK_PANELS` | Панель на карточке задачи |
| `GADGETS` | Гаджет для дашборда |
| `REPORTS` | Отчёт |
| `PROJECT_TABS` | Вкладка проекта |

Типы, статусы, приоритеты и решения — не точки: это записи, которые правит администратор (см. [Трекер](tracker.ru.md#типы-статусы-приоритеты-и-решения)), и плагин, которому нужен свой тип, статус или решение, добавляет строку, например из миграции.

## Каким бывает описание

Точки, которые что-то *делают* (`TRANSITION_CONDITIONS`, `TRANSITION_VALIDATORS`, `TRANSITION_FUNCTIONS`, `AUTOMATION_ACTIONS`), принимают имя класса в той форме, которую платформа готовит для действий автоматизации: `settings(): list<Field>` (те же классы `Atrium\Admin\Fields`, что и в форме, так что настройки шага рисуются и проверяются как любая форма) и `run(...)`, который делает работу с этими настройками. Перенос на реестр платформы тогда — перерегистрация, а не переписывание. Функции запросов (`QUERY_FUNCTIONS`) — класс с именем, аргументами и `evaluate()`. Точные сигнатуры фиксирует этап, который читает точку.

## Регистрация

В сервис-провайдере пакета:

```php
use Atrium\Tracker\Plugins\Plugins;

public function boot(): void
{
    $this->callAfterResolving(Plugins::class, function (Plugins $plugins): void {
        $plugins->register(new GitLinksPlugin);
    });
}
```

Ключ, зарегистрированный дважды, или ключ с заглавной буквой, точкой или пробелом сразу бросает исключение.

## Чтение

Часть трекера (или другой плагин) спрашивает у точки её описания:

```php
app(Plugins::class)->extensions(Plugins::TASK_PANELS);
// ['git-links.commits' => [...], …]  по ключу, в порядке добавления
```

Читаются только включённые плагины: `isEnabled($key)` ложно для выключенного администратором и для того, кого никто не регистрировал. `all()` перечисляет все зарегистрированные плагины, в том числе выключенные.

## Как выключить плагин

Как только зарегистрирован хотя бы один плагин, в настройках модуля (`/admin/modules/tracker/settings`) появляется поле **Отключённые плагины** со всеми. Отмеченное игнорируется: его описания `extensions()` не возвращает. Новый плагин включён, пока его не отметят. Выбор хранится в настройке `tracker.disabled_plugins` (список ключей).

## Пример

Плагин, который добавляет панель на карточку задачи и гаджет:

```php
namespace Acme\GitLinks;

use Atrium\Tracker\Plugins\Extensions;
use Atrium\Tracker\Plugins\Plugins;
use Atrium\Tracker\Plugins\TrackerPlugin;

final class GitLinksPlugin implements TrackerPlugin
{
    public function key(): string
    {
        return 'git-links';
    }

    public function title(): string
    {
        return __('git-links::plugin.title');
    }

    public function register(Extensions $extensions): void
    {
        $extensions->add(Plugins::TASK_PANELS, 'git-links.commits', [
            'component' => 'GitCommitsPanel',     // Vue-компонент пакета
            'label' => __('git-links::plugin.commits'),
        ]);

        $extensions->add(Plugins::GADGETS, 'git-links.recent', [
            'label' => __('git-links::plugin.recent'),
        ]);
    }
}
```

Проверяйте так же, как трекер: зарегистрируйте плагин в тесте, прочитайте точку, выключите через настройки и прочитайте снова (см. `PluginsTest` в пакете).
