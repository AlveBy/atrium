<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Feature;

use Atrium\Admin\Hooks\Hooks;
use Atrium\Language\Formula;
use Atrium\Language\Functions\FormulaFunction;
use Atrium\Language\Functions\Functions;
use Atrium\Language\Functions\Invocation;
use Atrium\Language\Functions\Parameter;
use Atrium\Language\Schema;
use Atrium\Language\Tests\TestCase;
use Atrium\Language\Types\Type;
use Brick\Math\BigDecimal;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/** The functions plugins add, and how they are called. */
final class RegistryTest extends TestCase
{
    /** @param  list<Parameter>  $parameters */
    private static function made(array $names, array $parameters = [], mixed $handler = null, Type|\Closure|null $returns = null): FormulaFunction
    {
        return new FormulaFunction($names, $parameters, $returns ?? Type::number(), $handler ?? static fn (): int => 1);
    }

    public function test_a_function_tells_how_many_arguments_it_takes(): void
    {
        $number = Parameter::of(Type::number());
        $text = Parameter::of(Type::text());

        $none = self::made(['A']);
        $this->assertSame([0, 0], [$none->minimum(), $none->maximum()]);

        $two = self::made(['B', 'Б'], [$number, $text->optional()]);
        $this->assertSame([1, 2], [$two->minimum(), $two->maximum()]);
        $this->assertSame('B', $two->name());
        $this->assertNull($two->permission);
        $this->assertSame($number, $two->parameter(0));
        $this->assertEquals($text->optional(), $two->parameter(1));

        $many = self::made(['C'], [$number, $text->variadic()]);
        $this->assertSame([2, null], [$many->minimum(), $many->maximum()]);
        $this->assertSame($number, $many->parameter(0));
        $this->assertEquals($text->variadic(), $many->parameter(1));
        $this->assertEquals($text->variadic(), $many->parameter(7));
    }

    public function test_each_change_of_a_parameter_keeps_the_others(): void
    {
        $plain = Parameter::of(Type::number());
        $flags = static fn (Parameter $parameter): array => [$parameter->optional, $parameter->variadic, $parameter->orEmpty];

        $this->assertSame([false, false, false], $flags($plain));
        $this->assertSame([true, false, false], $flags($plain->optional()));
        $this->assertSame([false, true, false], $flags($plain->variadic()));
        $this->assertSame([false, false, true], $flags($plain->orEmpty()));
        $this->assertSame([true, true, true], $flags($plain->orEmpty()->variadic()->optional()));
        $this->assertSame([true, true, true], $flags($plain->optional()->variadic()->orEmpty()));
        $this->assertSame([true, true, true], $flags($plain->variadic()->orEmpty()->optional()));
        $this->assertSame([false, false, false], $flags($plain));
    }

    public function test_a_required_parameter_cannot_follow_an_optional_one(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A required parameter of [A] follows an optional one.');

        $number = Parameter::of(Type::number());
        self::made(['A'], [$number->optional(), $number]);
    }

    public function test_only_the_last_parameter_may_take_any_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only the last parameter of [A] may be variadic.');

        $number = Parameter::of(Type::number());
        self::made(['A'], [$number->variadic(), $number]);
    }

    /** @return iterable<string,array{string}> */
    public static function unfitNames(): iterable
    {
        foreach (['', 'two words', '1A', 'a-b', 'a.b', "a\n", ' a', 'A(', 'if', 'ЕСЛИ', 'And', 'и', 'OR', 'Или', 'not', 'не', 'True', 'Истина', 'FALSE', 'ложь'] as $name) {
            yield json_encode($name, JSON_UNESCAPED_UNICODE) => [$name];
        }
    }

    #[DataProvider('unfitNames')]
    public function test_a_name_a_formula_cannot_call_is_refused(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A function cannot be called [{$name}].");

        (new Functions)->add(self::made(['FINE', $name]));
    }

    /** @return iterable<string,array{string}> */
    public static function fineNames(): iterable
    {
        foreach (['_x', 'Налог', 'a1_2', "E\u{301}tat", '税', 'Ifs', 'ANDY'] as $name) {
            yield $name => [$name];
        }
    }

    #[DataProvider('fineNames')]
    public function test_a_name_may_be_a_word_of_any_language(string $name): void
    {
        $function = self::made([$name]);

        $this->assertSame($function, (new Functions)->add($function)->find(mb_strtoupper($name)));
    }

    /** @return iterable<string,array{list<FormulaFunction>,string}> */
    public static function doubles(): iterable
    {
        yield 'two functions' => [[self::made(['VAT']), self::made(['vat'])], 'vat'];
        yield 'one function twice' => [[self::made(['НДС', 'ндс'])], 'ндс'];
        yield 'an alias' => [[self::made(['A', 'B']), self::made(['C', 'b'])], 'b'];
    }

    /** @param  list<FormulaFunction>  $functions */
    #[DataProvider('doubles')]
    public function test_two_functions_cannot_have_one_name(array $functions, string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Two functions are called [{$name}].");

        (new Functions)->add(...$functions);
    }

    public function test_the_functions_that_come_with_the_language(): void
    {
        $functions = Functions::standard();
        $names = [
            'SUM СУММ', 'AVERAGE СРЗНАЧ', 'MIN МИН', 'MAX МАКС', 'COUNT СЧЁТ СЧЕТ', 'ROUND ОКРУГЛ', 'ROUNDUP ОКРУГЛВВЕРХ', 'ROUNDDOWN ОКРУГЛВНИЗ', 'INT ЦЕЛОЕ', 'ABS', 'MOD ОСТАТ', 'SQRT КОРЕНЬ',
            'LEN ДЛСТР', 'LEFT ЛЕВСИМВ', 'RIGHT ПРАВСИМВ', 'MID ПСТР', 'UPPER ПРОПИСН', 'LOWER СТРОЧН', 'TRIM СЖПРОБЕЛЫ', 'CONCAT СЦЕПИТЬ СЦЕП', 'SUBSTITUTE ПОДСТАВИТЬ', 'REPT ПОВТОР', 'CONTAINS СОДЕРЖИТ',
            'ISBLANK ЕПУСТО', 'IFBLANK ЕСЛИПУСТО',
            'TODAY СЕГОДНЯ', 'DATE ДАТА', 'YEAR ГОД', 'MONTH МЕСЯЦ', 'DAY ДЕНЬ', 'EDATE ДАТАМЕС', 'EOMONTH КОНМЕСЯЦА', 'DAYS ДНИ', 'WEEKDAY ДЕНЬНЕД',
        ];

        foreach ($names as $line) {
            $all = explode(' ', $line);
            $this->assertSame($all, $functions->find($all[0])?->names, $line);

            foreach ($all as $name) {
                $this->assertSame($functions->find($all[0]), $functions->find(mb_strtolower($name)), $name);
            }
        }

        $this->assertNull($functions->find('VLOOKUP'));
        $this->assertNull((new Functions)->find('SUM'));
    }

    public function test_added_functions_come_from_outside_at_every_lookup(): void
    {
        $items = [];
        $functions = Functions::standard(static function () use (&$items): array {
            return $items;
        });

        $this->assertNull($functions->find('VAT'));

        $vat = self::made(['VAT', 'НДС']);
        $sum = self::made(['SUM']);
        $items = ['x' => 'not a function', 'y' => $sum, 'z' => $vat];

        $this->assertSame($vat, $functions->find('vat'));
        $this->assertSame($vat, $functions->find('ндс'));
        $this->assertNull($functions->find('GST'));
        // What the language brings cannot be replaced.
        $this->assertNotSame($sum, $functions->find('SUM'));
    }

    public function test_the_application_has_one_registry(): void
    {
        $functions = $this->app->make(Functions::class);

        $this->assertSame($functions, $this->app->make(Functions::class));
        $this->assertSame($functions, $this->context()->functions());
        $this->assertNotNull($functions->find('SUM'));
        // Without Atrium's core there are no hooks to ask.
        $this->assertFalse($this->app->bound(Hooks::class));
        $this->assertNull($functions->find('VAT'));

        $own = new Functions;
        $this->assertSame($own, $this->context()->withFunctions($own)->functions());
    }

    private function vat(): FormulaFunction
    {
        return new FormulaFunction(
            ['VAT', 'НДС'],
            [Parameter::of(Type::number())],
            Type::number(),
            static fn (Invocation $call): BigDecimal => $call->argument(0)->multipliedBy('0.2'),
            'shop.prices',
        );
    }

    public function test_a_module_adds_a_function_with_a_hook(): void
    {
        $functions = $this->app->make(Functions::class);
        $this->app->singleton(Hooks::class);

        $this->assertNull($functions->find('VAT'));

        $this->app->make(Hooks::class)->register(Functions::REGISTRY, 'vat', $this->vat());

        $this->assertSame('atrium.language.functions', Functions::REGISTRY);
        $this->assertSame('20', (string) Formula::parse('ндс(100)')->evaluate($this->context()->allow('shop.prices'))->value);
        $this->assertSame('check.permission', Formula::parse('VAT(100)')->check($this->context())->problems[0]->code);
    }

    private function calling(FormulaFunction $function, string $source, array $values = []): mixed
    {
        $context = $this->context($values, $this->schema()->field('x', Type::any()))
            ->withFunctions(Functions::standard()->add($function));

        return self::plain(Formula::parse($source)->evaluate($context)->value);
    }

    public function test_a_handler_sees_its_arguments(): void
    {
        $seen = [];
        $probe = self::made(
            ['PROBE'],
            [Parameter::of(Type::number()), Parameter::of(Type::text())->orEmpty()->optional(), Parameter::of(Type::number())->optional()],
            static function (Invocation $call) use (&$seen): int {
                $seen[] = [
                    self::plain($call->arguments),
                    self::plain($call->argument(0)),
                    $call->argument(1),
                    $call->text(1),
                    self::plain($call->argument(2)),
                    $call->whole(2, 1, 9, 7),
                    $call->whole(2, 1, 9),
                    (string) $call->today,
                ];

                return 1;
            },
        );

        $this->calling($probe, 'PROBE(2)');
        $this->calling($probe, 'PROBE(2; Status; 3)');

        $this->assertSame([
            [[self::num(2)], self::num(2), null, '', null, 7, 0, '2026-11-28'],
            [[self::num(2), null, self::num(3)], self::num(2), null, '', self::num(3), 3, 3, '2026-11-28'],
        ], $seen);
    }

    public function test_the_values_of_every_argument_without_the_empty_ones(): void
    {
        $values = self::made(
            ['VALUES'],
            [Parameter::of(Type::any())->orEmpty()->variadic()->optional()],
            static fn (Invocation $call): array => $call->values(),
            Type::listOf(Type::any()),
        );

        $this->assertSame(
            [self::num(1), self::num(2), [self::num(3)], 'a'],
            $this->calling($values, 'VALUES(1; Amount; X; "a")', ['x' => [2, null, [3]]]),
        );
        $this->assertSame([], $this->calling($values, 'VALUES()'));
    }

    public function test_a_handler_stops_a_run_in_the_words_of_the_language(): void
    {
        $failing = self::made(
            ['BOOM'],
            [],
            static fn (Invocation $call): never => $call->fail('value.search_too_long', ['limit' => 3]),
        );

        $this->assertStops(
            'value.search_too_long',
            'Line 1, column 1: The function BOOM can search for at most 3 characters.',
            fn () => $this->calling($failing, 'BOOM()'),
        );
    }

    public function test_a_handler_that_loops_is_stopped_by_the_steps_it_takes(): void
    {
        $forever = self::made(['FOREVER'], [], static function (Invocation $call): int {
            // Far past the limit, yet it ends even when nothing stops it.
            for ($turn = 0; $turn < 1000000; $turn++) {
                $call->step();
            }

            return 1;
        });

        $this->assertStops(
            'limit.steps',
            'Line 1, column 1: The formula was stopped: it took more than 200000 steps.',
            fn () => $this->calling($forever, 'FOREVER()'),
        );
    }

    public function test_a_handler_asks_before_it_makes_something_big(): void
    {
        $asking = self::made(['BIG'], [Parameter::of(Type::number())], static function (Invocation $call): int {
            $call->mustFit($call->whole(0, 0, 100000000));

            return 1;
        });

        $this->assertSame(self::num(1), $this->calling($asking, 'BIG(2097152)'));
        $this->assertStops(
            'limit.memory',
            'Line 1, column 1: The formula was stopped: its values took more than 2097152 bytes.',
            fn () => $this->calling($asking, 'BIG(2097153)'),
        );
    }

    public function test_what_a_handler_returns_is_taken_as_the_type_it_declares(): void
    {
        $date = self::made(['WHEN'], [], static fn (): string => '2026-01-02', Type::date());
        $price = self::made(['PRICE'], [], static fn (): float => 1.5);
        $lines = self::made(
            ['MINE'],
            [],
            static fn (): array => [['price' => 2]],
            Type::listOf(Type::record(Schema::make()->number('price'))),
        );
        $none = self::made(['NONE'], [], static fn (): null => null);

        $this->assertSame(self::day('2026-01-02'), $this->calling($date, 'WHEN()'));
        $this->assertSame(self::num('1.5'), $this->calling($price, 'PRICE()'));
        $this->assertSame([self::num(2)], $this->calling($lines, 'MINE().price'));
        $this->assertNull($this->calling($none, 'NONE()'));
    }

    public function test_the_result_type_may_follow_the_arguments(): void
    {
        $same = self::made(
            ['SAME'],
            [Parameter::of(Type::any())],
            static fn (Invocation $call): mixed => $call->argument(0),
            static fn (array $types): Type => $types[0],
        );
        $context = $this->context()->withFunctions(Functions::standard()->add($same));

        $this->assertSame('date', Formula::parse('SAME(Due)')->check($context)->type->describe('en'));
        $this->assertSame(self::day('2026-01-02'), $this->calling($same, 'SAME(Due)', ['due' => '2026-01-02']));
        $this->assertSame(self::num(3), $this->calling($same, 'SAME(X) + 1', ['x' => 2]));
    }

    public function test_a_wrong_return_names_the_function(): void
    {
        $wrong = self::made(['WRONG'], [], static fn (): string => 'many');

        $this->assertStops(
            'value.bad_return',
            'Line 1, column 1: The function WRONG returned a value that does not fit its type: number.',
            fn () => $this->calling($wrong, 'WRONG()'),
        );
    }
}
