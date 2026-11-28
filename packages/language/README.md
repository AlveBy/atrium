# Atrium Language

The first part of [Atrium](https://github.com/AlveBy/atrium)'s own language: formulas written the way a spreadsheet writes them, in English or Russian, parsed into a typed tree and run by an interpreter with limits. A formula reads only the fields it is given, calls only registered functions, never becomes PHP and cannot hang a page. Errors carry a line and a column and are written for people, in the 15 admin languages.

## Install

```bash
composer require alveby/atrium-language
```

It is a library, not a module: no tables, no menu, nothing to switch on.

## Use

```php
use Atrium\Language\Context;
use Atrium\Language\Formula;
use Atrium\Language\Schema;

$schema = Schema::make()
    ->number('amount', 'Amount', 'Сумма')
    ->text('status', 'Status', 'Статус');

$formula = Formula::parse('IF(Amount > 1000; "big"; "small")');
$context = Context::make($schema, ['amount' => 1500, 'status' => 'new']);

$check = $formula->check($context);      // $check->passes(), $check->messages('en'), $check->type
$result = $formula->evaluate($context);  // (string) $result->value === 'big'
```

Syntax, types, functions, limits and how a plugin adds a function: [docs/language.md](../../docs/language.md).

## Tests

From the repository root:

```bash
vendor/bin/phpunit packages/language/tests
```

## Contributing

This package lives in `packages/language` of [AlveBy/atrium](https://github.com/AlveBy/atrium). Issues and pull requests go there.

## License

MIT
