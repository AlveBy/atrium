<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC\Tests;

use Atrium\Admin\Columns\Column;
use Atrium\Admin\Fields\Field;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Atrium\OneC\Models\Link;
use Atrium\OneC\Models\Run;
use Atrium\OneC\Support\Inbox;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;

/**
 * The small things: how a file is read when 1C pads its values, what the
 * parser leaves behind, how the screen is laid out.
 */
final class DetailsTest extends TestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->token = $this->signIn();
        libxml_use_internal_errors(false);
        libxml_clear_errors();
    }

    private function importXml(string $file = 'import.xml'): string
    {
        return (string) $this->exchange(['type' => 'catalog', 'mode' => 'import', 'filename' => $file], $this->token)->getContent();
    }

    public function test_values_padded_with_spaces_and_line_breaks_are_read_trimmed(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<КоммерческаяИнформация>
  <Классификатор>
    <Свойства><Свойство>
      <Ид>
        p1 </Ид>
      <Наименование>  Цвет </Наименование>
      <ВариантыЗначений><Справочник><ИдЗначения> v1 </ИдЗначения><Значение>
        Красный </Значение></Справочник></ВариантыЗначений>
    </Свойство></Свойства>
    <Группы><Группа><Ид> g1 </Ид><Наименование>
      Группа </Наименование></Группа></Группы>
  </Классификатор>
  <Каталог><Товары><Товар>
    <Ид> t1 </Ид><Наименование>  Вещь </Наименование><Артикул> A-1 </Артикул><Описание>
      Текст </Описание><Статус> Удален </Статус>
    <Группы><Ид> g1 </Ид></Группы>
    <ЗначенияСвойств><ЗначенияСвойства><Ид> p1 </Ид><Значение> v1 </Значение></ЗначенияСвойства></ЗначенияСвойств>
    <Картинка>
      pic/A.PNG </Картинка>
  </Товар></Товары></Каталог>
</КоммерческаяИнформация>
XML;
        $this->upload($this->token, 'import.xml', $xml);
        $this->upload($this->token, 'pic/A.PNG', (string) base64_decode(self::PNG));

        $this->assertStringStartsWith('success', $this->importXml());

        $section = CatalogSection::query()->sole();
        $product = Product::query()->sole();
        $this->assertSame('Группа', $section->name);
        $this->assertSame($section->id, Link::find1c(Link::SECTION, 'g1'));
        $this->assertSame($product->id, Link::find1c(Link::PRODUCT, 't1'));
        $this->assertSame('Вещь', $product->name);
        $this->assertSame('A-1', $product->article);
        $this->assertSame('Текст', $product->detail_text);
        $this->assertFalse((bool) $product->active);
        $this->assertSame($section->id, $product->section_id);
        $this->assertSame(['Цвет: Красный'], $product->features);
        $this->assertSame('catalog/1c/t1-0.png', $product->preview_picture);
        Storage::disk('public')->assertExists('catalog/1c/t1-0.png');
    }

    public function test_an_empty_article_and_description_are_nothing_not_empty_texts(): void
    {
        $this->upload($this->token, 'import.xml', '<КоммерческаяИнформация><Каталог><Товары><Товар><Ид>t1</Ид><Наименование>Вещь</Наименование><Артикул>  </Артикул><Описание></Описание></Товар></Товары></Каталог></КоммерческаяИнформация>');

        $this->importXml();

        $product = Product::query()->sole();
        $this->assertNull($product->article);
        $this->assertNull($product->detail_text);
    }

    public function test_the_parser_is_left_as_it_was_found_after_a_good_file_and_a_bad_one(): void
    {
        $this->upload($this->token, 'import.xml', $this->fixture('import.xml'));
        $this->assertStringStartsWith('success', $this->importXml());
        $this->assertFalse(libxml_use_internal_errors());
        $this->assertFalse(libxml_get_last_error());

        $this->upload($this->token, 'broken.xml', '<КоммерческаяИнформация><Каталог><Товары><Товар>');
        $this->assertStringStartsWith('failure', $this->importXml('broken.xml'));
        $this->assertFalse(libxml_use_internal_errors());
        $this->assertFalse(libxml_get_last_error());
    }

    public function test_an_error_somebody_else_left_in_the_parser_does_not_fail_a_good_file(): void
    {
        libxml_use_internal_errors(true);
        simplexml_load_string('<broken');
        $this->assertNotFalse(libxml_get_last_error());
        $this->upload($this->token, 'import.xml', $this->fixture('import.xml'));

        $this->assertStringStartsWith('success', $this->importXml());
        $this->assertTrue(libxml_use_internal_errors(), 'what was switched on stays on');
        libxml_use_internal_errors(false);
        libxml_clear_errors();
    }

    public function test_a_step_is_remembered_under_the_session_and_the_file_for_an_hour(): void
    {
        Carbon::setTestNow('2026-11-24 10:00:00');
        config(['atrium-1c.step' => 1]);
        $this->upload($this->token, 'import.xml', $this->fixture('import.xml'));

        $this->assertStringStartsWith('progress', $this->importXml());

        $key = 'atrium-1c:step:'.$this->token.':import.xml';
        $this->assertSame(['run' => Run::query()->value('id'), 'next' => 1], Cache::get($key));
        Carbon::setTestNow('2026-11-24 10:59:59');
        $this->assertTrue(Cache::has($key));
        Carbon::setTestNow('2026-11-24 11:00:01');
        $this->assertFalse(Cache::has($key));
        Carbon::setTestNow();
    }

    public function test_a_finished_file_leaves_no_step_behind(): void
    {
        config(['atrium-1c.step' => 1]);
        $this->upload($this->token, 'import.xml', $this->fixture('import.xml'));

        for ($i = 0; $i < 10 && str_starts_with($this->importXml(), 'progress'); $i++) {
            $this->assertTrue(Cache::has('atrium-1c:step:'.$this->token.':import.xml'));
        }

        $this->assertFalse(Cache::has('atrium-1c:step:'.$this->token.':import.xml'));
        $this->assertSame(1, Run::query()->count());
    }

    public function test_the_answer_to_a_question_about_orders_is_an_empty_document_of_the_right_kind(): void
    {
        Carbon::setTestNow('2026-11-24 10:00:00');

        $response = $this->exchange(['type' => 'sale', 'mode' => 'query'], $this->token);

        $response->assertOk();
        $this->assertSame('text/xml; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<КоммерческаяИнформация ВерсияСхемы=\"2.08\" ДатаФормирования=\"2026-11-24T10:00:00\"></КоммерческаяИнформация>", $response->getContent());
        Carbon::setTestNow();
    }

    public function test_a_name_with_a_forbidden_character_or_broken_encoding_is_not_a_name(): void
    {
        $inbox = app(Inbox::class);

        $this->assertTrue($inbox->valid('Каталог/файл 1.xml'));
        $this->assertFalse($inbox->valid("bad\xff.xml"));
        $this->assertFalse($inbox->valid("tab\t.xml"));
    }

    public function test_a_file_with_a_forbidden_name_is_not_there_even_when_something_lies_under_it(): void
    {
        Storage::disk('local')->put('atrium-1c/a:b.xml', 'x');
        Storage::disk('local')->put('atrium-1c/fine.xml', 'x');
        $inbox = app(Inbox::class);

        $this->assertFalse($inbox->has('a:b.xml'));
        $this->assertTrue($inbox->has('fine.xml'));
        $this->assertFalse($inbox->has('missing.xml'));
    }

    public function test_a_zip_is_found_among_other_files(): void
    {
        foreach (['a.xml', 'b.xml', 'd.xml'] as $name) {
            Storage::disk('local')->put('atrium-1c/'.$name, 'x');
        }
        foreach (['c.zip' => 'from-c.txt', 'e.zip' => 'from-e.txt'] as $zip => $inside) {
            $path = tempnam(sys_get_temp_dir(), 'onec');
            $archive = new \ZipArchive;
            $archive->open((string) $path, \ZipArchive::OVERWRITE);
            $archive->addFromString($inside, 'x');
            $archive->close();
            Storage::disk('local')->put('atrium-1c/'.$zip, (string) file_get_contents((string) $path));
            unlink((string) $path);
        }

        app(Inbox::class)->extract();

        Storage::disk('local')->assertExists(['atrium-1c/from-c.txt', 'atrium-1c/from-e.txt', 'atrium-1c/a.xml', 'atrium-1c/d.xml']);
    }

    public function test_the_calls_the_importer_makes_are_public(): void
    {
        foreach (['find1c', 'remember'] as $method) {
            $this->assertTrue((new ReflectionMethod(Link::class, $method))->isPublic());
        }
        $this->assertSame(500, config('atrium-1c.step'));
    }

    public function test_a_zip_with_a_folder_inside_keeps_its_folders(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'onec');
        $zip = new \ZipArchive;
        $zip->open((string) $path, \ZipArchive::OVERWRITE);
        $zip->addEmptyDir('import_files');
        $zip->addFromString('import_files/a.txt', 'hello');
        $zip->close();
        $this->upload($this->token, 'pack.zip', (string) file_get_contents((string) $path));
        unlink((string) $path);

        app(Inbox::class)->extract();

        $this->assertSame('hello', Storage::disk('local')->get('atrium-1c/import_files/a.txt'));
        Storage::disk('local')->assertMissing('atrium-1c/pack.zip');
    }

    public function test_a_new_run_is_dated_and_the_dates_are_dates(): void
    {
        $run = Run::query()->create(['file' => 'x.xml', 'status' => Run::RUNNING, 'started_at' => '2026-11-24 10:00:00']);

        $this->assertInstanceOf(Carbon::class, $run->fresh()?->started_at);
    }

    public function test_the_exchange_screen_has_these_fields_columns_and_filter(): void
    {
        $fields = array_map(static fn (Field $field): array => $field->toArray(), Run::getAdministrationFields());
        $this->assertSame(
            ['started_at', 'finished_at', 'file', 'status_label', 'sections_created', 'sections_updated', 'products_created', 'products_updated', 'offers_updated', 'skipped', 'errors', 'error'],
            array_column($fields, 'name'),
        );
        $this->assertSame([true], array_values(array_unique(array_column($fields, 'readonly'))));
        $this->assertSame(6, array_column($fields, 'rows', 'name')['error']);
        $this->assertSame(['Started', 'Finished', 'File', 'Result', 'New sections', 'Sections updated', 'New products', 'Products updated', 'Prices and stock updated', 'Skipped offers (no such product)', 'Errors', 'What went wrong'], array_column($fields, 'label'));

        $columns = array_map(static fn (Column $column): array => $column->toArray(), Run::getAdministrationListColumns());
        $this->assertSame(['id', 'started_at', 'file', 'status_label', 'products_created', 'products_updated', 'offers_updated', 'errors'], array_column($columns, 'name'));
        $this->assertTrue(array_column($columns, 'sortable', 'name')['id']);

        $filter = Run::getAdministrationFilters()[0]->toArray();
        $this->assertSame('status', $filter['name']);
        $this->assertSame(
            [['value' => 'running', 'label' => 'In progress'], ['value' => 'done', 'label' => 'Done'], ['value' => 'failed', 'label' => 'Failed']],
            $filter['options'],
        );
    }

    public function test_the_model_can_be_asked_from_outside_whether_it_may_be_made_or_changed(): void
    {
        $this->assertFalse(Run::isCreatableInAdmin());
        $this->assertFalse(Run::isEditableInAdmin());
        $this->assertTrue((new ReflectionMethod(Run::class, 'isCreatableInAdmin'))->isPublic());
        $this->assertTrue((new ReflectionMethod(Run::class, 'isEditableInAdmin'))->isPublic());
    }
}
