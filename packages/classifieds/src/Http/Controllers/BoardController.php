<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Http\Controllers;

use Atrium\Admin\Support\ContentLanguages;
use Atrium\Classifieds\Board;
use Atrium\Classifieds\Enums\ComplaintReason;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\Category;
use Atrium\Classifieds\Models\City;
use Atrium\Classifieds\Models\Favourite;
use Atrium\Classifieds\Models\Region;
use Atrium\Classifieds\Support\Accounts;
use Atrium\Classifieds\Support\AdSearch;
use Atrium\Classifieds\Support\Properties;
use Atrium\Classifieds\Support\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * The public board: the whole list, landings of a category and of a city
 * (both are real addresses search engines index), a company's page and
 * the page of an ad.
 */
final class BoardController extends Controller
{
    public function __construct(
        private readonly Board $board,
        private readonly AdSearch $search,
        private readonly Properties $properties,
        private readonly Accounts $accounts,
        private readonly Schema $schema,
    ) {}

    public function index(Request $request): View
    {
        return $this->listing($request, null, null);
    }

    public function category(Request $request, string $path): View
    {
        $category = Category::findByPath($path);
        abort_if($category === null, 404);

        return $this->listing($request, $category, null);
    }

    public function city(Request $request, string $city, ?string $path = null): View
    {
        $place = City::query()->where('code', $city)->first();
        $category = $path !== null ? Category::findByPath($path) : null;
        abort_if($place === null || ($path !== null && $category === null), 404);

        return $this->listing($request, $category, $place);
    }

    public function company(Request $request, int $company): View
    {
        $name = $this->accounts->companyName($company);
        abort_if($name === null, 404);

        return $this->listing($request, null, null, $company, $name);
    }

    public function show(Request $request, int $ad, string $slug): View|RedirectResponse
    {
        $record = Ad::query()->with(['category', 'city'])->find($ad);
        $owner = $this->accounts->owner();
        $mine = $record !== null && $owner?->owns($record);

        // Only the owner sees an ad that is not on the site (a draft, one
        // waiting for a moderator, sold): everyone else gets "not found".
        abort_if($record === null || (! $record->isPublished() && ! $mine), 404);

        if ($slug !== $record->code) {
            return redirect()->to($record->url(), 301);
        }

        if (! $mine) {
            $record->newQuery()->whereKey($record->id)->increment('views');
        }

        $breadcrumbs = $this->breadcrumbs($record->category, null);
        $breadcrumbs[] = ['label' => $record->title, 'url' => $record->url()];
        $userId = $this->accounts->userId();

        return view('atrium-classifieds::ad', [
            'ad' => $record,
            'mine' => $mine,
            'properties' => $this->properties->values($record, $record->category),
            'favourite' => $userId !== null && Favourite::query()->where('user_id', $userId)->where('ad_id', $record->id)->exists(),
            'signedIn' => $userId !== null,
            'company' => $record->account_company_id !== null ? $this->accounts->companyName($record->account_company_id) : null,
            'reasons' => ComplaintReason::options(),
            'metaTitle' => $record->title.' — '.$record->priceText(),
            'metaDescription' => mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $record->text) ?? ''), 0, 160),
            'canonical' => $record->url(),
            'alternates' => $this->alternates(static fn (string $code): string => $record->url($code)),
            'breadcrumbs' => $breadcrumbs,
            'ogImage' => $record->photoUrl(),
            'jsonLd' => $record->isPublished() ? $this->schema->emit($record, $breadcrumbs, [$this->schema->ad($record)]) : null,
        ]);
    }

    private function listing(Request $request, ?Category $category, ?City $city, ?int $company = null, ?string $companyName = null): View
    {
        $input = $request->query();
        // A landing's own category, city and company are its address, not
        // something the query string may change.
        $input['category'] = $category?->id ?? ($input['category'] ?? null);
        $input['city'] = $city?->id ?? ($input['city'] ?? null);
        $input['company'] = $company;
        $filters = $this->search->normalize($input);
        $ads = $this->search->paginate($filters, max(1, $this->board->int('per_page')))->withQueryString();

        $title = match (true) {
            $companyName !== null => $companyName,
            $category !== null && $city !== null => __('atrium-classifieds::classifieds.seo.category_city', ['category' => $category->name, 'city' => $city->name]),
            $category !== null => (string) $category->name,
            $city !== null => __('atrium-classifieds::classifieds.seo.city', ['city' => $city->name]),
            default => __('atrium-classifieds::classifieds.title'),
        };

        $url = fn (?string $code = null): string => match (true) {
            $company !== null => $this->board->route('company', ['company' => $company], $code),
            $city !== null => $this->board->route('city', array_filter(['city' => $city->code, 'path' => $category?->path()]), $code),
            $category !== null => $category->url($code),
            default => $this->board->route('index', [], $code),
        };

        $filtered = array_diff_key($request->query(), ['page' => true]) !== [];
        $page = $ads->currentPage();
        $canonical = $page > 1 ? $url().'?page='.$page : $url();
        $breadcrumbs = $this->breadcrumbs($category, $city);
        $cards = $ads->items();

        return view('atrium-classifieds::list', [
            'ads' => $ads,
            'cards' => $cards,
            'filters' => $filters,
            'category' => $category,
            'city' => $city,
            'company' => $company,
            'heading' => $title,
            'categories' => Category::query()->where('active', true)->where('parent_id', $category?->id)->orderBy('sort')->orderBy('id')->get(),
            'regions' => Region::query()->orderBy('sort')->orderBy('id')->get(),
            'cities' => City::query()->orderBy('sort')->orderBy('id')->get(),
            'fields' => $this->properties->for($category),
            'counts' => $category !== null ? $this->properties->counts($category, $this->search->filtered(array_diff_key($filters, ['props' => true])), $filters['props'] ?? []) : [],
            'signedIn' => $this->accounts->userId() !== null,
            'metaTitle' => $page > 1 ? $title.' — '.__('atrium-classifieds::classifieds.seo.page', ['page' => $page]) : $title,
            'metaDescription' => $category?->description !== null && $category->description !== ''
                ? mb_substr(strip_tags((string) $category->description), 0, 160)
                : __('atrium-classifieds::classifieds.seo.description', ['title' => $title, 'count' => $ads->total()]),
            // A filtered list is not a page of its own: no index, and the
            // canonical points at the landing.
            'noindex' => $filtered,
            'canonical' => $canonical,
            'alternates' => $filtered || $page > 1 ? [] : $this->alternates($url),
            'breadcrumbs' => $breadcrumbs,
            'jsonLd' => $this->schema->emit(null, $breadcrumbs, [$this->schema->list($title, $url(), $cards)]),
        ]);
    }

    /**
     * @return array<int,array{label:string,url:string}>
     */
    private function breadcrumbs(?Category $category, ?City $city): array
    {
        $crumbs = [['label' => __('atrium-classifieds::classifieds.title'), 'url' => $this->board->route('index')]];

        if ($city !== null) {
            $crumbs[] = ['label' => (string) $city->name, 'url' => $this->board->route('city', ['city' => $city->code])];
        }

        foreach ($category?->lineage() ?? [] as $node) {
            $crumbs[] = [
                'label' => (string) $node->name,
                'url' => $city !== null ? $this->board->route('city', ['city' => $city->code, 'path' => $node->path()]) : $node->url(),
            ];
        }

        return $crumbs;
    }

    /**
     * @param  callable(string):string  $url
     * @return array<string,string>
     */
    private function alternates(callable $url): array
    {
        $switch = [];

        foreach (app(ContentLanguages::class)->codes() as $code) {
            $switch[$code] = $url($code);
        }

        return $switch;
    }
}
