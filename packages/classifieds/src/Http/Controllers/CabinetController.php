<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Http\Controllers;

use Atrium\Classifieds\Board;
use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Enums\BoostType;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\Category;
use Atrium\Classifieds\Models\City;
use Atrium\Classifieds\Models\Favourite;
use Atrium\Classifieds\Models\SavedSearch;
use Atrium\Classifieds\Support\Accounts;
use Atrium\Classifieds\Support\AdWorkflow;
use Atrium\Classifieds\Support\Boosts;
use Atrium\Classifieds\Support\Cards;
use Atrium\Classifieds\Support\Conversations;
use Atrium\Classifieds\Support\Owner;
use Atrium\Classifieds\Support\Properties;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The seller's and the buyer's side of the board in the personal cabinet:
 * my ads (a person's own, or all of the company's), the posting form,
 * favourites, messages and saved searches.
 */
final class CabinetController extends Controller
{
    public function __construct(
        private readonly Board $board,
        private readonly Accounts $accounts,
        private readonly AdWorkflow $workflow,
        private readonly Properties $properties,
    ) {}

    public function ads(Request $request): View
    {
        $owner = $this->owner();
        $status = AdStatus::tryFrom((string) $request->query('status'));
        $query = $owner->scope(Ad::query())->with(['category', 'city'])->orderByDesc('updated_at')->orderByDesc('id');

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $this->page('ads', [
            'ads' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            'boosts' => array_map(static fn (BoostType $type): array => ['type' => $type, ...app(Boosts::class)->offer($type)], BoostType::cases()),
            'renewals' => $this->board->int('renewals'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->owner();

        return $this->form(new Ad(['category_id' => (int) ($request->query('category_id') ?? $request->query('category')) ?: null]));
    }

    public function store(Request $request): RedirectResponse
    {
        $owner = $this->owner();
        $ad = $this->workflow->save($owner, null, $this->validated($request, null), $request->boolean('submit'));

        return $this->toAds($ad);
    }

    public function edit(int $ad): View
    {
        return $this->form($this->mine($ad));
    }

    public function update(Request $request, int $ad): RedirectResponse
    {
        $record = $this->mine($ad);
        $record = $this->workflow->save($this->owner(), $record, $this->validated($request, $record), $request->boolean('submit'));

        return $this->toAds($record);
    }

    public function submit(int $ad): RedirectResponse
    {
        $record = $this->mine($ad);
        $this->workflow->submit($this->owner(), $record);

        return $this->toAds($record);
    }

    public function sold(int $ad): RedirectResponse
    {
        $record = $this->mine($ad);
        $this->workflow->markSold($this->owner(), $record);

        return $this->toAds($record);
    }

    public function renew(int $ad): RedirectResponse
    {
        $record = $this->mine($ad);
        $this->workflow->renew($this->owner(), $record);

        return $this->toAds($record);
    }

    public function destroy(int $ad): RedirectResponse
    {
        $this->mine($ad)->delete();

        return redirect()->to($this->board->route('cabinet.ads'))->with('atrium-classifieds.status', __('atrium-classifieds::classifieds.flash.deleted'));
    }

    /**
     * Raise, highlight or VIP: paid through the shop's checkout when it is
     * there, at once when the site gives it for free, else waiting for the
     * administrator to mark it paid.
     */
    public function boost(Request $request, int $ad, Boosts $boosts): RedirectResponse
    {
        $record = $this->mine($ad);
        $data = $request->validate(['type' => ['required', Rule::enum(BoostType::class)]]);
        $boost = $boosts->order($this->owner(), $record, BoostType::from($data['type']));
        $checkout = $boosts->checkoutUrl($boost);

        if ($checkout !== null) {
            return redirect()->to($checkout);
        }

        return redirect()->to($this->board->route('cabinet.ads'))->with('atrium-classifieds.status', __(
            $boost->status === $boost::PAID ? 'atrium-classifieds::classifieds.flash.boosted' : 'atrium-classifieds::classifieds.flash.boost_pending',
        ));
    }

    public function favourites(Cards $cards): View
    {
        $ids = Favourite::query()->where('user_id', $this->owner()->userId)->orderByDesc('id')->pluck('ad_id');
        $ads = Ad::query()->published()->whereIn('id', $ids)->get(['id', 'updated_at'])->sortBy(static fn (Ad $ad): int|false => $ids->search($ad->id));

        return $this->page('favourites', ['cards' => $cards->many($ads->values()->all())]);
    }

    public function messages(Conversations $conversations): View
    {
        $owner = $this->owner();

        return $this->page('messages', ['threads' => $conversations->threads($owner), 'owner' => $owner]);
    }

    public function thread(int $ad, int $buyer, Conversations $conversations): View
    {
        $record = Ad::query()->find($ad);
        abort_if($record === null, 404);
        $owner = $this->owner();

        return $this->page('thread', [
            'ad' => $record,
            'buyer' => $buyer,
            'messages' => $conversations->open($owner, $record, $buyer),
            'seller' => $owner->owns($record),
        ], $record->title);
    }

    public function reply(Request $request, int $ad, int $buyer, Conversations $conversations): RedirectResponse
    {
        $record = Ad::query()->find($ad);
        abort_if($record === null, 404);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $conversations->send($this->owner(), $record, $buyer, $data['body']);

        return redirect()->to($this->board->route('cabinet.thread', ['ad' => $record->id, 'buyer' => $buyer]));
    }

    public function searches(): View
    {
        return $this->page('searches', [
            'searches' => SavedSearch::query()->where('user_id', $this->owner()->userId)->orderByDesc('id')->get(),
        ]);
    }

    public function toggleSearch(int $search): RedirectResponse
    {
        $saved = $this->search($search);
        $saved->notify = ! $saved->notify;
        $saved->save();

        return redirect()->to($this->board->route('cabinet.searches'));
    }

    public function destroySearch(int $search): RedirectResponse
    {
        $this->search($search)->delete();

        return redirect()->to($this->board->route('cabinet.searches'));
    }

    /**
     * @return array<string,mixed>
     */
    private function validated(Request $request, ?Ad $ad): array
    {
        $max = max(1, $this->board->int('max_photos'));
        $category = Category::query()->where('active', true)->find((int) $request->input('category_id'));

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'text' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'integer'],
            'city_id' => ['nullable', 'integer'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'negotiable' => ['nullable', 'boolean'],
            'keep' => ['nullable', 'array'],
            'keep.*' => ['string'],
            'photos' => ['nullable', 'array', 'max:'.$max],
            'photos.*' => ['image', 'max:8192'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-\s]{5,40}$/', 'required_without:contact_email'],
            'contact_email' => ['nullable', 'email', 'max:191'],
            ...$this->properties->rules($category),
        ]);

        if ($category === null) {
            throw ValidationException::withMessages(['category_id' => __('atrium-classifieds::classifieds.errors.unknown_category')]);
        }

        if (isset($data['city_id']) && ! City::query()->whereKey($data['city_id'])->exists()) {
            throw ValidationException::withMessages(['city_id' => __('atrium-classifieds::classifieds.errors.unknown_city')]);
        }

        // Kept photos are only ones the ad already has: a path from the
        // form cannot point at somebody else's file.
        $photos = array_values(array_intersect($ad->photos ?? [], (array) ($data['keep'] ?? [])));

        foreach ($request->file('photos', []) as $file) {
            if ($file instanceof UploadedFile && count($photos) < $max) {
                $photos[] = (string) $file->store('classifieds/'.now()->format('Y/m'), 'public');
            }
        }

        $data['photos'] = $photos;
        $data['location'] = isset($data['lat'], $data['lng'])
            ? ['lat' => (float) $data['lat'], 'lng' => (float) $data['lng'], 'address' => $data['address'] ?? null]
            : null;

        return $data;
    }

    private function form(Ad $ad): View
    {
        $category = $ad->category_id !== null ? Category::query()->find($ad->category_id) : null;

        return $this->page('form', [
            'ad' => $ad,
            'categories' => $this->categoryOptions(),
            'cities' => City::query()->orderBy('sort')->orderBy('id')->get(),
            'fields' => $this->properties->for($category),
            'values' => $ad->exists ? $this->properties->raw($ad, $category) : [],
            'maxPhotos' => max(1, $this->board->int('max_photos')),
            'premoderation' => (bool) $this->board->option('premoderation'),
        ], $ad->exists ? $ad->title : __('atrium-classifieds::classifieds.cabinet.new'));
    }

    /**
     * The tree flattened with its depth, for one select.
     *
     * @return array<int,string>
     */
    private function categoryOptions(): array
    {
        $all = Category::query()->where('active', true)->orderBy('sort')->orderBy('id')->get()->groupBy(static fn (Category $c): int => (int) $c->parent_id);
        $options = [];
        $walk = static function (int $parent, int $depth) use (&$walk, &$options, $all): void {
            foreach ($all->get($parent, []) as $node) {
                $options[$node->id] = str_repeat('— ', $depth).$node->name;
                $walk($node->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $options;
    }

    /**
     * @param  array<string,mixed>  $data
     */
    private function page(string $view, array $data, ?string $title = null): View
    {
        return view('atrium-classifieds::cabinet.'.$view, [
            ...$data,
            'title' => $title ?? __('atrium-classifieds::classifieds.cabinet.'.$view),
            'cabinet' => $this->accounts->hasCabinet(),
            'metaTitle' => $title ?? __('atrium-classifieds::classifieds.cabinet.'.$view),
        ]);
    }

    private function owner(): Owner
    {
        $owner = $this->accounts->owner();
        abort_if($owner === null, 403);

        return $owner;
    }

    private function mine(int $id): Ad
    {
        $ad = Ad::query()->find($id);
        abort_if($ad === null, 404);
        $this->workflow->assertOwner($this->owner(), $ad);

        return $ad;
    }

    private function search(int $id): SavedSearch
    {
        $saved = SavedSearch::query()->where('user_id', $this->owner()->userId)->find($id);
        abort_if($saved === null, 404);

        return $saved;
    }

    private function toAds(Ad $ad): RedirectResponse
    {
        return redirect()->to($this->board->route('cabinet.ads'))
            ->with('atrium-classifieds.status', __('atrium-classifieds::classifieds.flash.status.'.$ad->status->value, ['title' => $ad->title]));
    }
}
