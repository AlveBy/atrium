<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC\Tests;

use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Atrium\Integrations\Models\ExchangeLog;
use Atrium\OneC\Models\Link;
use Atrium\OneC\Models\Run;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

final class ImportTest extends TestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->token = $this->signIn();
    }

    private function importXml(string $file = 'import.xml'): string
    {
        return (string) $this->exchange(['type' => 'catalog', 'mode' => 'import', 'filename' => $file], $this->token)->getContent();
    }

    private function sendCatalogue(): void
    {
        $this->upload($this->token, 'import.xml', $this->fixture('import.xml'));
        $this->upload($this->token, 'import_files/ab/aero.png', (string) base64_decode(self::PNG));
        $this->upload($this->token, 'import_files/ab/aero-side.png', (string) base64_decode(self::PNG));
    }

    private function product(string $guid): Product
    {
        return Product::query()->findOrFail(Link::find1c(Link::PRODUCT, $guid));
    }

    public function test_the_catalogue_file_becomes_sections_and_products(): void
    {
        $this->sendCatalogue();

        $reply = $this->importXml();

        $this->assertSame("success\nSections: 3 new, 0 updated. Products: 3 new, 0 updated. Prices and stock: 0. Skipped: 0. Errors: 0.\n", $reply);
        $this->assertSame(['Ноутбуки', 'Игровые', 'Мониторы'], CatalogSection::query()->orderBy('id')->pluck('name')->all());
        $this->assertSame(['noutbuki', 'igrovye', 'monitory'], CatalogSection::query()->orderBy('id')->pluck('code')->all());
        $this->assertSame(['Aero 14', 'Монитор 27', 'Старая модель'], array_map(fn (string $name): string => str_replace('Ноутбук ', '', $name), Product::query()->orderBy('id')->pluck('name')->all()));
    }

    public function test_sections_keep_their_nesting(): void
    {
        $this->sendCatalogue();
        $this->importXml();

        $laptops = CatalogSection::query()->where('name', 'Ноутбуки')->sole();
        $gaming = CatalogSection::query()->where('name', 'Игровые')->sole();

        $this->assertNull($laptops->parent_id);
        $this->assertSame($laptops->id, $gaming->parent_id);
        $this->assertNull(CatalogSection::query()->where('name', 'Мониторы')->sole()->parent_id);
    }

    public function test_a_product_gets_its_fields_section_and_properties(): void
    {
        $this->sendCatalogue();
        $this->importXml();

        $product = $this->product('prod-1');

        $this->assertSame('Ноутбук Aero 14', $product->name);
        $this->assertSame('A-100', $product->article);
        $this->assertSame('Лёгкий ноутбук на каждый день', $product->detail_text);
        $this->assertSame('text', $product->detail_text_type);
        $this->assertTrue($product->active);
        $this->assertSame(CatalogSection::query()->where('name', 'Игровые')->value('id'), $product->section_id);
        // A value taken from the dictionary, a plain one, and nothing for an empty or unknown property.
        $this->assertSame(['Бренд: Acme', 'Вес: 1,4 кг'], $product->features);
        $this->assertSame('noutbuk-aero-14', $product->code);
    }

    public function test_a_product_without_a_section_or_properties_has_none(): void
    {
        $this->sendCatalogue();
        $this->importXml();

        $old = $this->product('prod-3');

        $this->assertNull($old->section_id);
        $this->assertNull($old->features);
        $this->assertNull($old->article);
        $this->assertNull($old->detail_text);
    }

    public function test_a_product_marked_deleted_is_switched_off_not_removed(): void
    {
        $this->sendCatalogue();
        $this->importXml();

        $this->assertFalse($this->product('prod-3')->active);
        $this->assertTrue($this->product('prod-2')->active);
    }

    public function test_pictures_are_copied_to_the_public_disk_and_the_first_is_the_cover(): void
    {
        $this->sendCatalogue();
        $this->importXml();

        $product = $this->product('prod-1');

        $this->assertSame('catalog/1c/prod-1-0.png', $product->preview_picture);
        $this->assertSame('catalog/1c/prod-1-0.png', $product->detail_picture);
        $this->assertSame(['catalog/1c/prod-1-0.png', 'catalog/1c/prod-1-1.png'], $product->photos);
        Storage::disk('public')->assertExists('catalog/1c/prod-1-1.png');
        $this->assertSame(base64_decode(self::PNG), Storage::disk('public')->get('catalog/1c/prod-1-0.png'));
        $this->assertNull($this->product('prod-2')->preview_picture);
    }

    public function test_a_second_exchange_updates_and_makes_nothing_twice(): void
    {
        $this->sendCatalogue();
        $this->importXml();
        $first = [CatalogSection::query()->pluck('id')->all(), Product::query()->pluck('id')->all()];

        $reply = $this->importXml();

        $this->assertStringContainsString('Sections: 0 new, 3 updated. Products: 0 new, 3 updated.', $reply);
        $this->assertSame($first, [CatalogSection::query()->pluck('id')->all(), Product::query()->pluck('id')->all()]);
        $this->assertSame(3, Link::query()->where('kind', 'section')->count());
        $this->assertSame(3, Link::query()->where('kind', 'product')->count());
        $this->assertSame(2, Run::query()->count());
    }

    public function test_changes_in_1c_reach_the_site_but_the_address_stays(): void
    {
        $this->sendCatalogue();
        $this->importXml();
        $code = $this->product('prod-1')->code;

        $this->upload($this->token, 'import.xml', '');
        $this->exchange(['type' => 'catalog', 'mode' => 'init'], $this->token);
        $this->upload($this->token, 'import.xml', str_replace(['Ноутбук Aero 14', 'Игровые', '<Артикул>A-100</Артикул>'], ['Ноутбук Aero 15', 'Геймерские', '<Артикул>A-101</Артикул>'], $this->fixture('import.xml')));

        $this->importXml();

        $product = $this->product('prod-1');
        $this->assertSame('Ноутбук Aero 15', $product->name);
        $this->assertSame('A-101', $product->article);
        $this->assertSame($code, $product->code);
        $this->assertSame('Геймерские', CatalogSection::query()->findOrFail($product->section_id)->name);
        $this->assertSame(3, Product::query()->count());
    }

    public function test_a_section_moved_in_1c_moves_here(): void
    {
        $this->sendCatalogue();
        $this->importXml();

        $moved = str_replace(
            ['<Группы><Ид>grp-gaming</Ид></Группы>'],
            ['<Группы><Ид>grp-gaming</Ид></Группы>'],
            $this->fixture('import.xml'),
        );
        $moved = preg_replace('#<Группа>\s*<Ид>grp-gaming</Ид>\s*<Наименование>Игровые</Наименование>\s*</Группа>#u', '', $moved);
        $moved = str_replace('<Ид>grp-monitors</Ид>', '<Ид>grp-monitors</Ид>', $moved);
        $moved = str_replace('<Наименование>Мониторы</Наименование>', '<Наименование>Мониторы</Наименование><Группы><Группа><Ид>grp-gaming</Ид><Наименование>Игровые</Наименование></Группа></Группы>', $moved);
        $this->exchange(['type' => 'catalog', 'mode' => 'init'], $this->token);
        $this->upload($this->token, 'import.xml', (string) $moved);

        $this->importXml();

        $this->assertSame(CatalogSection::query()->where('name', 'Мониторы')->value('id'), CatalogSection::query()->where('name', 'Игровые')->value('parent_id'));
        $this->assertSame(3, CatalogSection::query()->count());
    }

    public function test_a_product_or_section_deleted_here_comes_back_with_the_next_exchange(): void
    {
        $this->sendCatalogue();
        $this->importXml();
        $this->product('prod-2')->delete();
        CatalogSection::query()->where('name', 'Мониторы')->delete();

        $reply = $this->importXml();

        $this->assertStringContainsString('Sections: 1 new, 2 updated. Products: 1 new, 2 updated.', $reply);
        $this->assertSame(3, Product::query()->count());
        $this->assertSame(3, CatalogSection::query()->count());
        $this->assertNotNull(CatalogSection::query()->where('name', 'Мониторы')->value('id'));
    }

    public function test_two_sections_with_one_name_get_two_codes(): void
    {
        $this->exchange(['type' => 'catalog', 'mode' => 'init'], $this->token);
        $this->upload($this->token, 'import.xml', '<КоммерческаяИнформация><Классификатор><Группы>'
            .'<Группа><Ид>a</Ид><Наименование>Мониторы</Наименование></Группа>'
            .'<Группа><Ид>b</Ид><Наименование>Мониторы</Наименование></Группа>'
            .'<Группа><Ид>c</Ид><Наименование>Мониторы</Наименование></Группа>'
            .'<Группа><Ид>d</Ид><Наименование>!!!</Наименование></Группа>'
            .'</Группы></Классификатор></КоммерческаяИнформация>');

        $this->importXml();

        $this->assertSame(['monitory', 'monitory-2', 'monitory-3', 'section'], CatalogSection::query()->orderBy('id')->pluck('code')->all());
    }

    public function test_the_run_is_written_down_and_the_log_gets_a_line(): void
    {
        $this->sendCatalogue();

        $this->importXml();

        $run = Run::query()->sole();
        $this->assertSame('import.xml', $run->file);
        $this->assertSame('done', $run->status);
        $this->assertSame('Done', $run->status_label);
        $this->assertNotNull($run->finished_at);
        $this->assertSame([3, 0, 3, 0, 0, 0, 0], [$run->sections_created, $run->sections_updated, $run->products_created, $run->products_updated, $run->offers_updated, $run->skipped, $run->errors]);
        $this->assertNull($run->error);

        $line = ExchangeLog::query()->where('action', 'catalog.import')->sole();
        $this->assertSame(['1c', 'in', 'ok', 'import.xml'], [$line->integration, $line->direction, $line->status, $line->subject]);
        $this->assertStringContainsString('Products: 3 new', (string) $line->message);
        $this->assertNull($line->error);
    }

    public function test_offers_give_products_a_price_and_say_whether_they_are_in_stock(): void
    {
        $this->sendCatalogue();
        $this->importXml();
        $this->upload($this->token, 'offers.xml', $this->fixture('offers.xml'));

        $reply = $this->importXml('offers.xml');

        $this->assertSame("success\nSections: 0 new, 0 updated. Products: 0 new, 0 updated. Prices and stock: 4. Skipped: 1. Errors: 0.\n", $reply);
        $this->assertEquals('1299.50', $this->product('prod-1')->price);
        $this->assertSame('Y', $this->product('prod-1')->in_stock);
        $this->assertEquals('300.00', $this->product('prod-2')->price);
        $this->assertNull($this->product('prod-2')->in_stock);
        $this->assertSame('offers.xml', Run::query()->latest('id')->value('file'));
    }

    public function test_a_product_with_several_offers_stays_in_stock_while_one_has_goods(): void
    {
        $this->sendCatalogue();
        $this->importXml();
        $this->upload($this->token, 'offers.xml', $this->fixture('offers.xml'));
        $this->importXml('offers.xml');

        // prod-3 has a blue offer with two in stock and a red one with none, in that order.
        $this->assertSame('Y', $this->product('prod-3')->in_stock);
        $this->assertEquals('10.00', $this->product('prod-3')->price);
    }

    public function test_an_offer_without_a_price_keeps_the_old_one_and_a_sold_out_product_goes_off_the_shelf(): void
    {
        $this->sendCatalogue();
        $this->importXml();
        $this->product('prod-2')->update(['price' => 99, 'in_stock' => 'Y']);
        $this->upload($this->token, 'offers.xml', '<КоммерческаяИнформация><ПакетПредложений><Предложения>'
            .'<Предложение><Ид>prod-2</Ид><Количество>0</Количество></Предложение>'
            .'<Предложение><Ид>prod-1</Ид><Количество>x</Количество><Цены><Цена><ЦенаЗаЕдиницу>n/a</ЦенаЗаЕдиницу></Цена></Цены></Предложение>'
            .'</Предложения></ПакетПредложений></КоммерческаяИнформация>');

        $this->importXml('offers.xml');

        $this->assertEquals('99.00', $this->product('prod-2')->price);
        $this->assertNull($this->product('prod-2')->in_stock);
        $this->assertNull($this->product('prod-1')->price);
    }

    public function test_an_offer_for_a_product_removed_here_is_skipped(): void
    {
        $this->sendCatalogue();
        $this->importXml();
        $this->product('prod-1')->delete();
        $this->upload($this->token, 'offers.xml', $this->fixture('offers.xml'));

        $this->assertStringContainsString('Prices and stock: 3. Skipped: 2.', $this->importXml('offers.xml'));
    }

    public function test_problems_are_counted_listed_and_alert_the_log(): void
    {
        $this->exchange(['type' => 'catalog', 'mode' => 'init'], $this->token);
        $this->upload($this->token, 'import.xml', '<КоммерческаяИнформация><Классификатор><Группы>'
            .'<Группа><Ид></Ид><Наименование>Без номера</Наименование></Группа>'
            .'<Группа><Ид>g</Ид><Наименование> </Наименование></Группа>'
            .'</Группы></Классификатор><Каталог><Товары>'
            .'<Товар><Ид>p1</Ид><Наименование></Наименование></Товар>'
            .'<Товар><Ид></Ид><Наименование>Без номера</Наименование></Товар>'
            .'<Товар><Ид>p3</Ид><Наименование>Три картинки</Наименование>'
            .'<Картинка>import_files/gone.png</Картинка><Картинка>import_files/virus.exe</Картинка><Картинка>../outside.png</Картинка></Товар>'
            .'</Товары></Каталог></КоммерческаяИнформация>');

        $reply = $this->importXml();

        $this->assertStringContainsString('Products: 1 new, 0 updated. Prices and stock: 0. Skipped: 0. Errors: 7.', $reply);
        $run = Run::query()->sole();
        $this->assertSame('done', $run->status);
        $this->assertSame(7, $run->errors);
        $this->assertSame(implode("\n", [
            'A group has no id or no name.',
            'A group has no id or no name.',
            'A product has no id or no name.',
            'A product has no id or no name.',
            'The picture import_files/gone.png of the product p3 did not come.',
            'The picture import_files/virus.exe of the product p3 did not come.',
            'The picture ../outside.png of the product p3 did not come.',
        ]), (string) $run->error);

        $line = ExchangeLog::query()->where('action', 'catalog.import')->sole();
        $this->assertSame('failed', $line->status);
        $this->assertStringContainsString('The picture import_files/gone.png', (string) $line->error);
    }

    public function test_a_run_keeps_the_text_of_twenty_errors_and_counts_on(): void
    {
        $products = str_repeat('<Товар><Ид>x</Ид><Наименование></Наименование></Товар>', 25);
        $this->upload($this->token, 'import.xml', "<КоммерческаяИнформация><Каталог><Товары>{$products}</Товары></Каталог></КоммерческаяИнформация>");

        $this->importXml();

        $run = Run::query()->sole();
        $this->assertSame(25, $run->errors);
        $this->assertCount(20, explode("\n", (string) $run->error));
    }

    public function test_a_catalogue_is_read_in_steps_and_the_run_is_one(): void
    {
        config(['atrium-1c.step' => 2]);
        $this->sendCatalogue();

        $first = $this->importXml();
        $this->assertSame("progress\nImported items: 2.\n", $first);
        $this->assertSame(3, CatalogSection::query()->count());
        $this->assertSame(2, Product::query()->count());
        $this->assertSame('running', Run::query()->sole()->status);
        $this->assertNull(Run::query()->sole()->finished_at);

        $second = $this->importXml();
        $this->assertStringStartsWith("success\nSections: 3 new, 0 updated. Products: 3 new, 0 updated.", $second);
        $this->assertSame(1, Run::query()->count());
        $this->assertSame('done', Run::query()->sole()->status);
        $this->assertSame(1, ExchangeLog::query()->where('action', 'catalog.import')->count());
        $this->assertSame(3, Product::query()->count());
    }

    public function test_a_step_that_ends_exactly_at_the_end_of_the_file_is_the_last(): void
    {
        config(['atrium-1c.step' => 3]);
        $this->sendCatalogue();

        $this->assertStringStartsWith('success', $this->importXml());
        $this->assertSame(1, Run::query()->count());
    }

    public function test_a_step_size_of_nothing_still_moves_forward(): void
    {
        config(['atrium-1c.step' => 0]);
        $this->sendCatalogue();

        $this->assertSame("progress\nImported items: 1.\n", $this->importXml());
    }

    public function test_a_new_file_after_an_unfinished_one_starts_its_own_run(): void
    {
        config(['atrium-1c.step' => 1]);
        $this->sendCatalogue();
        $this->importXml();
        $this->upload($this->token, 'offers.xml', $this->fixture('offers.xml'));

        $this->importXml('offers.xml');

        $this->assertSame(['import.xml', 'offers.xml'], Run::query()->orderBy('id')->pluck('file')->all());
    }

    public function test_a_session_starts_afresh_when_a_run_it_remembers_is_gone(): void
    {
        config(['atrium-1c.step' => 1]);
        $this->sendCatalogue();
        $this->importXml();
        Run::query()->delete();

        $this->assertSame("progress\nImported items: 1.\n", $this->importXml());
        $this->assertSame(1, Run::query()->count());
    }

    public function test_a_file_in_a_folder_finds_its_pictures_beside_it(): void
    {
        $this->upload($this->token, 'sub/import.xml', '<КоммерческаяИнформация><Каталог><Товары><Товар><Ид>q</Ид><Наименование>Q</Наименование><Картинка>pics/q.png</Картинка></Товар></Товары></Каталог></КоммерческаяИнформация>');
        $this->upload($this->token, 'sub/pics/q.png', (string) base64_decode(self::PNG));

        $this->assertStringContainsString('Errors: 0', $this->importXml('sub/import.xml'));
        $this->assertSame('catalog/1c/q-0.png', $this->product('q')->preview_picture);
    }

    public function test_a_file_in_windows_1251_is_read(): void
    {
        $xml = '<?xml version="1.0" encoding="windows-1251"?><КоммерческаяИнформация><Каталог><Товары><Товар><Ид>w</Ид><Наименование>Чайник</Наименование></Товар></Товары></Каталог></КоммерческаяИнформация>';
        $this->upload($this->token, 'import.xml', (string) iconv('UTF-8', 'CP1251', $xml));

        $this->importXml();

        $this->assertSame('Чайник', $this->product('w')->name);
    }

    public function test_ids_with_a_characteristic_after_the_hash_find_their_product(): void
    {
        $this->upload($this->token, 'import.xml', '<КоммерческаяИнформация><Каталог><Товары><Товар><Ид>abc#def</Ид><Наименование>Футболка</Наименование></Товар></Товары></Каталог></КоммерческаяИнформация>');
        $this->importXml();

        $this->assertNotNull(Link::find1c(Link::PRODUCT, 'abc'));
        $this->assertNull(Link::find1c(Link::PRODUCT, 'abc#def'));
    }

    public function test_a_file_that_did_not_come_is_a_failure(): void
    {
        $this->assertSame("failure\nThe file offers.xml did not come.\n", $this->importXml('offers.xml'));
        $this->assertSame("failure\nThe file ../x.xml did not come.\n", $this->importXml('../x.xml'));
        $this->assertSame("failure\nThe file  did not come.\n", $this->exchange(['type' => 'catalog', 'mode' => 'import'], $this->token)->getContent());
        $this->assertSame(0, Run::query()->count());
    }

    public function test_the_import_of_orders_does_nothing(): void
    {
        $this->sendCatalogue();

        $this->assertSame("success\n", $this->exchange(['type' => 'sale', 'mode' => 'import', 'filename' => 'import.xml'], $this->token)->getContent());
        $this->assertSame(0, Product::query()->count());
    }

    public function test_a_broken_file_fails_the_run_and_the_log_and_leaves_the_catalogue_alone(): void
    {
        $this->upload($this->token, 'import.xml', '<КоммерческаяИнформация><Каталог><Товары><Товар><Ид>1</Ид><Наим');

        $reply = $this->importXml();

        $this->assertSame("failure\nThe file holds an element that cannot be read.\n", $reply);
        $run = Run::query()->sole();
        $this->assertSame(['failed', 'The file holds an element that cannot be read.'], [$run->status, $run->error]);
        $this->assertNotNull($run->finished_at);
        $line = ExchangeLog::query()->where('action', 'catalog.import')->sole();
        $this->assertSame(['failed', 'import.xml', 'The file holds an element that cannot be read.'], [$line->status, $line->subject, $line->error]);
        $this->assertSame(0, Product::query()->count());
    }

    public function test_a_file_cut_short_is_not_well_formed(): void
    {
        $this->upload($this->token, 'import.xml', '<КоммерческаяИнформация><Каталог><Товары></Товары>');

        $this->assertSame("failure\nThe file import.xml is not well-formed XML.\n", $this->importXml());
    }

    public function test_a_failed_step_forgets_where_it_was(): void
    {
        config(['atrium-1c.step' => 1]);
        $this->upload($this->token, 'import.xml', '<КоммерческаяИнформация><Каталог><Товары><Товар><Ид>1</Ид><Наименование>A</Наименование></Товар><Товар><Ид>2</Ид><Наим');
        $this->assertStringStartsWith('progress', $this->importXml());

        $this->assertStringStartsWith('failure', $this->importXml());
        $this->assertNull(Cache::get('atrium-1c:step:'.$this->token.':import.xml'));
    }

    public function test_a_zip_comes_in_pieces_is_opened_and_imported(): void
    {
        $zip = $this->zip(['import.xml' => $this->fixture('import.xml'), 'import_files/ab/aero.png' => (string) base64_decode(self::PNG), 'import_files/ab/aero-side.png' => 'png', 'empty/' => '']);
        foreach (str_split($zip, 700) as $piece) {
            $this->upload($this->token, 'bundle.zip', $piece);
        }

        $reply = $this->importXml();

        $this->assertStringStartsWith("success\nSections: 3 new", $reply);
        Storage::disk('local')->assertMissing('atrium-1c/bundle.zip');
        $this->assertSame(['catalog/1c/prod-1-0.png', 'catalog/1c/prod-1-1.png'], $this->product('prod-1')->photos);
    }

    public function test_a_second_import_call_after_a_zip_finds_nothing_left_to_open(): void
    {
        $this->upload($this->token, 'bundle.zip', $this->zip(['import.xml' => $this->fixture('import.xml')]));

        $this->assertStringStartsWith('success', $this->importXml());
        $this->assertStringStartsWith('success', $this->importXml());
    }

    public function test_a_zip_that_is_not_a_zip_fails_without_a_run(): void
    {
        $this->upload($this->token, 'bundle.zip', 'this is not an archive');

        $this->assertSame("failure\nThe zip archive bundle.zip cannot be opened.\n", $this->importXml());
        $this->assertSame(0, Run::query()->count());
        $this->assertSame('The zip archive bundle.zip cannot be opened.', ExchangeLog::query()->where('action', 'catalog.import')->sole()->error);
    }

    public function test_a_zip_may_not_write_outside_the_folder(): void
    {
        $this->upload($this->token, 'bundle.zip', $this->zip(['../evil.xml' => 'x']));

        $this->assertSame("failure\nThe zip archive holds a file with a forbidden name: ../evil.xml\n", $this->importXml());
        $this->assertSame([], array_filter(Storage::disk('local')->allFiles(), fn (string $file): bool => str_ends_with($file, 'evil.xml')));
    }

    public function test_a_letter_case_does_not_hide_a_zip(): void
    {
        $this->upload($this->token, 'BUNDLE.ZIP', $this->zip(['import.xml' => $this->fixture('import.xml')]));

        $this->assertStringStartsWith('success', $this->importXml());
    }

    /**
     * @param  array<string,string>  $files
     */
    private function zip(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'onec');
        $zip = new ZipArchive;
        $zip->open((string) $path, ZipArchive::OVERWRITE);

        foreach ($files as $name => $content) {
            str_ends_with($name, '/') ? $zip->addEmptyDir(rtrim($name, '/')) : $zip->addFromString($name, $content);
        }

        $zip->close();
        $bytes = (string) file_get_contents((string) $path);
        unlink((string) $path);

        return $bytes;
    }
}
