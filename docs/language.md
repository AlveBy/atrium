# Formulas: Atrium's own language

[Русский](language.ru.md)

`alveby/atrium-language` is the first part of the [one language](extensions.md#level-2-one-language-formulas-rules-and-plugins) of Atrium: formulas written the way a spreadsheet writes them, a typed tree they are parsed into, a check before running and an interpreter with limits. It gives conditions ("show this block if …"), calculators and computed values to anything that calls it.

A formula reads only the fields it is given, calls only registered functions, is never turned into PHP and cannot keep a page busy without end.

Not here yet: the text syntax of plugins, the compilers to PHP and JavaScript, a formula editor in the admin and stored versions of formulas.

## Install

```bash
composer require alveby/atrium-language
```

It is a library, not a module: no tables, no menu, nothing to switch on.

## From PHP

```php
use Atrium\Language\Context;
use Atrium\Language\Formula;
use Atrium\Language\FormulaError;
use Atrium\Language\Schema;

// The fields a formula may name: a key, a type and any other names.
$schema = Schema::make()
    ->number('quantity', 'Quantity', 'Количество')
    ->number('price', 'Price', 'Цена')
    ->date('shipped', 'Shipping date', 'Дата отгрузки')
    ->records('lines', Schema::make()->number('amount', 'Amount', 'Сумма'), 'Lines', 'Строки');

// Their values, by key.
$context = Context::make($schema, [
    'quantity' => 3,
    'price' => '19.90',
    'shipped' => '2026-12-01',
    'lines' => [['amount' => 10], ['amount' => 15.5]],
]);

$formula = Formula::parse('Quantity * Price + SUM(Lines.Amount)');

$formula->check($context)->passes();        // true
$result = $formula->evaluate($context);

(string) $result->value;                    // '85.2'
array_keys($result->fields);                // ['quantity', 'price', 'lines']
```

| Call | What it gives |
|---|---|
| `Formula::parse($text)` | The formula, or a `FormulaError` with the line and column of what cannot be read. |
| `Formula::fromJson($json)`, `$formula->toJson()` | The formula as [stored JSON](#the-tree). |
| `Formula::fromNode($node)`, `$formula->root` | The formula built from nodes, and its tree. |
| `$formula->check($context)` | A `Check`: `passes()`, `problems` (all of them), `messages($locale)`, the result's `type`. |
| `$formula->evaluate($context)` | A `Result`: `value` and `fields`, the fields that were read with the values used. It checks first. |
| `$formula->explain($context)` | The same, with `trace`: how the value was counted. |

### The context

`Context::make($schema, $values)` takes the values as an array or an `ArrayAccess`, by field key; a missing one is empty. Each method returns a changed copy:

| Method | What it sets |
|---|---|
| `allow('deals.view', …)` | The permissions of the formula's author, which [functions](#adding-a-function) may ask for. |
| `withLimits(new Limits(…))` | Other [limits](#limits). |
| `withToday($date)` | The day `TODAY()` gives, instead of today in the application's time zone. |
| `withFunctions($functions)` | Another registry than the application's. |
| `withClock($nanoseconds)` | Another clock for the time limit, for tests. |

A value is converted to the type of its field when the formula first reads it:

| Field type | PHP values it takes | In a result |
|---|---|---|
| number | `int`, `float`, a text such as `-12.5`, `Brick\Math\BigDecimal` | `BigDecimal` |
| text | `string`, `Stringable` | `string` |
| boolean | `bool` | `bool` |
| date | `DateTimeInterface`, a text `2026-11-28` | `Atrium\Language\Values\Date` |
| records | any iterable of arrays or `ArrayAccess`, by the keys of the records' fields | a list; one record is a `Values\Record` |

An empty value is `null`. An empty text in a number, boolean or date field is empty too, as a form sends it.

**What a formula may see is decided by the schema.** Put in it only the fields that the formula's author may read: a formula cannot name any other.

### How it was counted

`explain()` keeps a `Step` for every part that was worked out: the node, its value and the steps it was made of. A branch of `IF` that was not taken, and the operands after the one that settled `AND` or `OR`, are not there.

```php
$step = Formula::parse('Quantity * Price')->explain($context)->trace;

(string) $step->value;               // '59.7'
(string) $step->steps[1]->value;     // '19.9'
$step->steps[1]->node->name;         // 'Price'
```

## Writing a formula

### Values

| Kind | How it is written |
|---|---|
| Number | `12`, `0.5`. The decimal sign is the point: the comma separates arguments. |
| Text | `"Done"`; a quote inside is doubled: `"the ""best"" one"`. |
| Date | `28.11.2026` or `#2026-11-28#`. |
| Yes or no | `TRUE`, `FALSE`, `ИСТИНА`, `ЛОЖЬ`, in any case. |

### Names of fields

Fields are named, not addressed. A name may be Russian or English, hold spaces and end in a number: `Price`, `Дата отгрузки`, `Stage 2`. Several spaces count as one, and the case does not matter.

Any other name goes in square brackets: `[Price (net)]`, `[Cost, $]`; a closing bracket inside is doubled: `[a]]b]` is `a]b`.

A field of related records comes after a point: `Lines.Amount` is the list of amounts of the lines, which a function such as `SUM` can total.

### Operators

From the loosest to the tightest:

| Operators | What they do |
|---|---|
| `OR` `ИЛИ` | Either is true. |
| `AND` `И` | Both are true. |
| `NOT` `НЕ` | The opposite. |
| `=` `<>` `<` `<=` `>` `>=` | Comparison. One at a time: write `AND(1 < x; x < 10)`, not `1 < x < 10`. |
| `&` | Joins texts, numbers, dates and yes-or-no into one text; a date reads as `2026-11-28`, yes-or-no as `TRUE` or `FALSE`. |
| `+` `-` | Sum and difference; a date plus or minus whole days; a date minus a date is the number of days. |
| `*` `/` | Product and quotient. |
| `^` | Power, with a whole exponent from -1000 to 1000. |
| `%` | A hundredth: `15%` is `0.15`. |
| `-` before a value | The negative. As in Excel, `-2^2` is `4`. |
| `.` | A field of a related record. |

The words `AND`, `OR`, `NOT`, `И`, `ИЛИ`, `НЕ` are operators only **in capitals**, so that names may hold the same words: `Terms and conditions` is one field. Written as functions, `AND(…)`, `or(…)`, `Не(…)`, they work in any case.

Brackets change the order. Arguments are separated by `;` or `,`.

### Conditions

`IF(condition; then; otherwise)`, or `ЕСЛИ`. Only the branch that is taken is worked out. Without the third argument the result is empty when the condition is false. Both branches must give the same type.

`AND` and `OR` stop at the operand that settles the answer.

## Values and empty values

- **Numbers are exact decimals**: `0.1 + 0.2` is `0.3`. They keep 20 places after the point, rounded half away from zero, and 40 digits before it.
- **Dates** are days of the calendar from year 1 to 9999, with no time.
- **Texts compare whatever their case**: `"Done" = "done"` is true.
- **Only values of one type are compared.** `<`, `>` and the like order numbers, texts and dates.

An empty value is not silently a zero:

| Where | What an empty value is |
|---|---|
| A text is expected, or `&` | An empty text. |
| A number, a date or yes-or-no is needed | An error that names the field. |
| `=` and `<>` | Equal only to another empty value or to `""`. |
| `SUM`, `AVERAGE`, `MIN`, `MAX`, `COUNT` | Skipped. |
| `ISBLANK`, `IFBLANK` | What they are for: `IFBLANK(Discount; 0)`. |

## Functions

Each function has an English and a Russian name, and either may be written in any case. `IF`, `AND`, `OR` and `NOT` belong to the language itself.

### Numbers

| Function | Russian name | Result |
|---|---|---|
| `SUM(values…)` | `СУММ` | The total of numbers and lists of numbers; 0 for none. |
| `AVERAGE(values…)` | `СРЗНАЧ` | The mean; an error when there is no value. |
| `MIN(values…)`, `MAX(values…)` | `МИН`, `МАКС` | The smallest and the largest; an error when there is no value. |
| `COUNT(values…)` | `СЧЁТ`, `СЧЕТ` | How many values are not empty, of any type or of related records. |
| `ROUND(number; places)` | `ОКРУГЛ` | Rounded half away from zero; places from -40 to 20. |
| `ROUNDUP(number; places)` | `ОКРУГЛВВЕРХ` | Rounded away from zero. |
| `ROUNDDOWN(number; places)` | `ОКРУГЛВНИЗ` | Rounded towards zero. |
| `INT(number)` | `ЦЕЛОЕ` | The whole number below. |
| `ABS(number)` | `ABS` | Without the sign. |
| `MOD(number; divisor)` | `ОСТАТ` | The remainder, with the sign of the divisor. |
| `SQRT(number)` | `КОРЕНЬ` | The square root. |

### Texts

| Function | Russian name | Result |
|---|---|---|
| `LEN(text)` | `ДЛСТР` | The number of characters. |
| `LEFT(text; [count])`, `RIGHT(text; [count])` | `ЛЕВСИМВ`, `ПРАВСИМВ` | The first or the last characters; one by default. |
| `MID(text; start; count)` | `ПСТР` | A part, counted from 1. |
| `UPPER(text)`, `LOWER(text)` | `ПРОПИСН`, `СТРОЧН` | In capitals or in small letters. |
| `TRIM(text)` | `СЖПРОБЕЛЫ` | Without spaces at the ends, and one between words. |
| `CONCAT(values…)` | `СЦЕПИТЬ`, `СЦЕП` | The values and lists of values joined. |
| `SUBSTITUTE(text; old; new)` | `ПОДСТАВИТЬ` | Every `old` replaced; the case matters. |
| `REPT(text; times)` | `ПОВТОР` | The text repeated. |
| `CONTAINS(text; part)` | `СОДЕРЖИТ` | Whether the part is in the text, whatever the case. |

The text looked for by `SUBSTITUTE` and `CONTAINS` may be up to 255 characters long.

### Empty values

| Function | Russian name | Result |
|---|---|---|
| `ISBLANK(value)` | `ЕПУСТО` | Whether the value is empty or an empty text. |
| `IFBLANK(value; instead)` | `ЕСЛИПУСТО` | The value, or `instead` when it is empty. |

### Dates

| Function | Russian name | Result |
|---|---|---|
| `TODAY()` | `СЕГОДНЯ` | Today. |
| `DATE(year; month; day)` | `ДАТА` | The day; month 13 is January of the next year, as in Excel. The year is the year as written. |
| `YEAR(date)`, `MONTH(date)`, `DAY(date)` | `ГОД`, `МЕСЯЦ`, `ДЕНЬ` | A part of the date. |
| `EDATE(date; months)` | `ДАТАМЕС` | The same day some months away, or the last day of a shorter month. |
| `EOMONTH(date; months)` | `КОНМЕСЯЦА` | The last day of the month some months away. |
| `DAYS(end; start)` | `ДНИ` | The days from `start` to `end`. |
| `WEEKDAY(date; [type])` | `ДЕНЬНЕД` | The day of the week: type 1 (default) counts from Sunday, type 2 from Monday. |

## Errors

Nothing like `#VALUE!`. A problem says what is wrong and where, in the 15 languages of the admin:

```php
try {
    Formula::parse('Quantity * Price')->evaluate(Context::make($schema, ['quantity' => 3]));
} catch (FormulaError $error) {
    $error->getMessage();               // Line 1, column 12: The field “Price” is empty, but the formula needs: number.
    $error->problem()->code;            // 'value.empty_field'
    $error->problem()->at->column;      // 12
    $error->problem()->message('ru');   // the same in Russian
}
```

`check()` finds everything that can be known before running, all at once (the first 100 problems): unknown fields and functions, types that do not fit, the number of arguments, permissions, the depth of nesting and lists more than 8 deep inside one another. What depends on the values (an empty field, a division by zero, a limit) stops the run. Every failure of a formula is a `FormulaError`.

## Limits

| `Limits` | Default | What it counts |
|---|---|---|
| `steps` | 200 000 | Parts of the formula worked out and items of lists passed over. |
| `milliseconds` | 1 000 | Time, looked at before every step. |
| `memory` | 2 097 152 | Bytes of the values made, added up over the run: 16 for each value, empty ones too, and the bytes of a text. A list put in many places — in each result of a loop, in an explanation — is counted in each. |
| `depth` | 64 | How deep the tree may be. |
| `recursion` | 8 | How many runs may be open inside each other. |

A run started from inside a function of another run spends the same budget under the outer limits, so a formula cannot get fresh limits by calling formulas.

Some bounds cannot be raised: a formula's text is at most 10 000 characters with brackets 64 deep; a tree has at most 5 000 nodes, 200 levels; a stored formula is at most 1 000 000 bytes; lists and records given as values go at most 64 deep into one another; and numbers are bounded as above.

PHP cannot be stopped in the middle of a function: the time and steps are looked at between the parts of a formula. A function that loops, or makes something big, asks for it itself (below).

## Adding a function

A function declares its names, what it takes, what it gives, the permission it needs and the PHP that does it. With Atrium installed, a module registers it as any other [extension](extensions.md):

```php
use Atrium\Admin\Facades\Hook;
use Atrium\Language\Functions\FormulaFunction;
use Atrium\Language\Functions\Functions;
use Atrium\Language\Functions\Invocation;
use Atrium\Language\Functions\Parameter;
use Atrium\Language\Types\Type;

Hook::register(Functions::REGISTRY, 'shop.vat', new FormulaFunction(
    names: ['VAT', 'НДС'],
    parameters: [Parameter::of(Type::number()), Parameter::of(Type::number())->optional()],
    returns: Type::number(),
    handler: fn (Invocation $call) => $call->argument(0)->multipliedBy($call->whole(1, 0, 100, 20))->withPointMovedLeft(2),
    permission: 'shop.view',
));
```

Without Atrium, add it to a registry: `Functions::standard()->add($function)`, and give that to `withFunctions()`.

- **Parameters.** `Parameter::of($type)`, then `optional()` (may be left out, as must be all after it), `variadic()` (the last one, any number) and `orEmpty()` (accepts an empty value; otherwise the run stops, naming what is empty).
- **Types.** `Type::number()`, `text()`, `boolean()`, `date()`, `listOf($type)`, `record($schema)`, `any()`, and `oneOf(…)` for a parameter that takes one of several.
- **The result's type** is a single type or `any`, not `oneOf`; it may be a closure of the arguments' types. The handler returns a plain PHP value of it, which is checked and counted against the memory limit.
- **The handler** gets one `Invocation`: `argument($i)`, `text($i)`, `whole($i, $min, $max, $default)`, `values()` (all arguments, lists opened, empty ones skipped), `today`, `fail($code, $params)`.
- **Limits.** Call `$call->step()` on every turn of your own loop and `$call->mustFit($bytes)` before making a big value.
- **Permissions.** With `permission` set, a formula whose context does not `allow()` it fails the check.
- **Names** are letters, digits and `_`. The functions of the language come first: a module cannot change what `SUM` does.

A function is PHP of a trusted package: what it reads and writes is its own responsibility.

## The tree

A formula is a tree of typed nodes, the intermediate representation that rules made of blocks and plugins written as text will also produce. It is versioned like a contract.

| Node | Class in `Atrium\Language\Ir` | JSON keys |
|---|---|---|
| A value | `Literal` | `type`: `empty`, `boolean`, `text`, `number` or `date`; `value` |
| A field | `Field` | `name` |
| The item of a loop | `Variable` | `name` |
| A field of a related record | `Member` | `of`, `name` |
| An operator | `Operation` | `operator`, `operands` |
| A call of a function | `Call` | `function`, `arguments` |
| A condition | `Condition` | `when`, `then`, `otherwise` (may be left out) |
| A loop over a list | `Each` | `source`, `variable`, `body`, `where` (may be left out) |

Operators: `add`, `subtract`, `multiply`, `divide`, `power`, `negate`, `percent`, `concat`, `equal`, `not_equal`, `less`, `less_or_equal`, `greater`, `greater_or_equal`, `and`, `or`, `not`. `negate`, `percent` and `not` take one operand; `add`, `multiply`, `concat`, `and` and `or` one or more; the others two.

Every node may carry `at`, the line and column it came from. `Quantity * 2`:

```json
{
    "version": 1,
    "root": {
        "node": "operation",
        "operator": "multiply",
        "operands": [
            {"node": "field", "name": "Quantity", "at": [1, 1]},
            {"node": "literal", "type": "number", "value": "2", "at": [1, 12]}
        ],
        "at": [1, 10]
    }
}
```

Numbers are texts in JSON, so that no precision is lost; dates are `YYYY-MM-DD`.

`Each` has no way to be written in a formula yet. It works as a node: `body` for every item of `source` for which `where` is true, with the item as a `Variable`, giving a list.

Stored JSON is not trusted. Before it is decoded, every key in it must be one of the format's, written plainly (without `\u` codes), and it may open at most 20 000 arrays and objects. Then it is read node by node, bounded in depth and count, and goes through the same check as text.

## What a formula cannot do

- Read anything but the fields of its schema.
- Call anything but a function of the registry that its context allows.
- Become PHP: nothing is `eval`ed, included or compiled, and names are never used as PHP names.
- Reach a file, the database, the network or PHP's globals.
- Run without end or grow a value without end: see [Limits](#limits).
