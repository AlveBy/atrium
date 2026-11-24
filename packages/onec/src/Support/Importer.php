<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC\Support;

use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Atrium\OneC\Models\Link;
use Atrium\OneC\Models\Run;
use DOMDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use XMLReader;

use function simplexml_import_dom;

/**
 * Reads a CommerceML 2 file 1C sent and writes it into the catalogue.
 *
 * import.xml: the groups become sections, each product (Товар) a product
 * with its name, article, description, pictures and properties (they become
 * lines of "features"). offers.xml: each offer (Предложение) gives its
 * product a price and, by the quantity, whether it is in stock. Everything
 * is matched by the 1C identifier, so a second exchange updates.
 *
 * A file is read in steps of `$limit` items: run() says where the next step
 * starts, and the caller asks again until it is done, so a big catalogue
 * never has to fit one request.
 */
final class Importer
{
    /** How many error lines a run keeps: the count goes on, the text stops. */
    private const ERROR_LINES = 20;

    private const IMAGES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /** @var array<string,array{name:string,values:array<string,string>}> */
    private array $properties = [];

    private Run $run;

    private string $folder = '';

    public function __construct(private readonly Inbox $inbox) {}

    /**
     * @return array{done:bool,next:int}
     */
    public function run(string $file, Run $run, int $offset, int $limit): array
    {
        $this->run = $run;
        $this->folder = dirname($file) === '.' ? '' : dirname($file).'/';
        $this->properties = [];

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            return $this->read($file, $offset, $limit);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * @return array{done:bool,next:int}
     */
    private function read(string $file, int $offset, int $limit): array
    {
        $reader = new XMLReader;
        $reader->open($this->inbox->path($file), null, LIBXML_NONET);

        $index = 0;
        $done = true;
        $more = $reader->read();

        while ($more) {
            $name = $reader->nodeType === XMLReader::ELEMENT ? $reader->localName : '';

            if ($name === 'Классификатор') {
                $this->classifier($this->element($reader), $offset === 0);
                $more = $reader->next();
            } elseif ($name === 'Товар' || $name === 'Предложение') {
                if ($index - $offset >= $limit) {
                    $done = false;
                    break;
                }

                if ($index >= $offset) {
                    $name === 'Товар' ? $this->product($this->element($reader)) : $this->offer($this->element($reader));
                }

                $index++;
                $more = $reader->next();
            } else {
                $more = $reader->read();
            }
        }

        if (libxml_get_last_error() !== false) {
            throw new RuntimeException('The file '.$file.' is not well-formed XML.');
        }

        return ['done' => $done, 'next' => $index];
    }

    private function element(XMLReader $reader): SimpleXMLElement
    {
        // A broken element makes expand() warn and answer false; the answer is what is checked.
        $node = @$reader->expand(new DOMDocument);
        $element = $node === false ? null : simplexml_import_dom($node);

        return $element ?? throw new RuntimeException('The file holds an element that cannot be read.');
    }

    private function classifier(SimpleXMLElement $classifier, bool $first): void
    {
        foreach ($classifier->Свойства->Свойство ?? [] as $property) {
            $values = [];

            foreach ($property->ВариантыЗначений->Справочник ?? [] as $option) {
                $values[$this->text($option->ИдЗначения)] = $this->text($option->Значение);
            }

            $this->properties[$this->text($property->Ид)] = ['name' => $this->text($property->Наименование), 'values' => $values];
        }

        if ($first) {
            $this->sections($classifier->Группы, null);
        }
    }

    private function sections(?SimpleXMLElement $groups, ?int $parent): void
    {
        foreach ($groups->Группа ?? [] as $group) {
            $guid = $this->guid($group->Ид);
            $name = $this->text($group->Наименование);

            if ($guid === '' || $name === '') {
                $this->fail('A group has no id or no name.');

                continue;
            }

            $section = ($id = Link::find1c(Link::SECTION, $guid)) === null ? null : CatalogSection::query()->find($id);

            if ($section === null) {
                $section = CatalogSection::query()->create(['name' => $name, 'parent_id' => $parent, 'code' => $this->sectionCode($name)]);
                $this->run->sections_created++;
            } else {
                $section->update(['name' => $name, 'parent_id' => $parent]);
                $this->run->sections_updated++;
            }

            Link::remember(Link::SECTION, $guid, $section->id);
            $this->sections($group->Группы, $section->id);
        }
    }

    private function sectionCode(string $name): string
    {
        $base = Str::slug($name) ?: 'section';
        $code = $base;

        for ($n = 2; CatalogSection::query()->where('code', $code)->exists(); $n++) {
            $code = $base.'-'.$n;
        }

        return $code;
    }

    private function product(SimpleXMLElement $item): void
    {
        $guid = $this->guid($item->Ид);
        $name = $this->text($item->Наименование);

        if ($guid === '' || $name === '') {
            $this->fail('A product has no id or no name.');

            return;
        }

        $values = [
            'name' => $name,
            'article' => $this->text($item->Артикул) ?: null,
            'detail_text' => $this->text($item->Описание) ?: null,
            'detail_text_type' => 'text',
            'active' => $this->text($item->Статус) !== 'Удален',
            'features' => $this->features($item),
        ];

        if (($section = Link::find1c(Link::SECTION, $this->guid($item->Группы->Ид))) !== null) {
            $values['section_id'] = $section;
        }

        if (($pictures = $this->pictures($guid, $item)) !== []) {
            $values = [...$values, 'preview_picture' => $pictures[0], 'detail_picture' => $pictures[0], 'photos' => $pictures];
        }

        $product = ($id = Link::find1c(Link::PRODUCT, $guid)) === null ? null : Product::query()->find($id);

        if ($product === null) {
            $product = Product::query()->create($values);
            $this->run->products_created++;
        } else {
            $product->update($values);
            $this->run->products_updated++;
        }

        Link::remember(Link::PRODUCT, $guid, $product->id);
    }

    private function offer(SimpleXMLElement $offer): void
    {
        $guid = $this->guid($offer->Ид);
        $product = ($id = Link::find1c(Link::PRODUCT, $guid)) === null ? null : Product::query()->find($id);

        if ($product === null) {
            $this->run->skipped++;

            return;
        }

        $values = [];

        if (($price = $this->number($offer->Цены->Цена->ЦенаЗаЕдиницу ?? null)) !== null) {
            $values['price'] = $price;
        }

        // One of several offers of a product (colours, sizes) never takes it off the shelf.
        if ($this->number($offer->Количество) > 0) {
            $values['in_stock'] = 'Y';
        } elseif (! str_contains($this->text($offer->Ид), '#')) {
            $values['in_stock'] = null;
        }

        $product->update($values);
        $this->run->offers_updated++;
    }

    /**
     * @return list<string>|null
     */
    private function features(SimpleXMLElement $item): ?array
    {
        $lines = [];

        foreach ($item->ЗначенияСвойств->ЗначенияСвойства ?? [] as $property) {
            $known = $this->properties[$this->text($property->Ид)] ?? null;
            $value = $this->text($property->Значение);
            $value = $known['values'][$value] ?? $value;

            if ($known !== null && $value !== '') {
                $lines[] = $known['name'].': '.$value;
            }
        }

        return $lines === [] ? null : $lines;
    }

    /**
     * The pictures of a product copied to the public disk: the paths there.
     *
     * @return list<string>
     */
    private function pictures(string $guid, SimpleXMLElement $item): array
    {
        $paths = [];

        foreach ($item->Картинка as $picture) {
            $source = $this->text($picture);
            $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));

            if (! in_array($extension, self::IMAGES, true) || ! $this->inbox->has($this->folder.$source)) {
                $this->fail('The picture '.$source.' of the product '.$guid.' did not come.');

                continue;
            }

            $path = 'catalog/1c/'.preg_replace('/[^A-Za-z0-9_-]/', '', $guid).'-'.count($paths).'.'.$extension;
            Storage::disk('public')->put($path, (string) file_get_contents($this->inbox->path($this->folder.$source)));
            $paths[] = $path;
        }

        return $paths;
    }

    private function fail(string $line): void
    {
        $this->run->errors++;

        if ($this->run->errors <= self::ERROR_LINES) {
            $this->run->error = trim($this->run->error."\n".$line);
        }
    }

    /** 1C may write "guid#characteristic": the product is the part before the #. */
    private function guid(?SimpleXMLElement $id): string
    {
        return Str::before($this->text($id), '#');
    }

    private function text(?SimpleXMLElement $node): string
    {
        return trim((string) $node);
    }

    private function number(?SimpleXMLElement $node): ?float
    {
        $text = str_replace([' ', ','], ['', '.'], (string) $node);

        return is_numeric($text) ? (float) $text : null;
    }
}
