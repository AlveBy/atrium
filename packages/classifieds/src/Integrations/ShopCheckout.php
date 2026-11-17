<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Integrations;

use Atrium\Classifieds\Models\Boost;
use Atrium\Classifieds\Support\Boosts;
use Atrium\Classifieds\Support\Checkout;
use Atrium\Shop\Cart\CartManager;
use Atrium\Shop\Cart\ExternalLine;
use Atrium\Shop\Cart\PurchasableResolver;
use Atrium\Shop\Models\OrderItem;
use Atrium\Shop\Money\Money;
use Atrium\Shop\ShopUrls;

/**
 * Boosts sold through the shop (alveby/atrium-shop): a cart line
 * "classifieds:boost:{id}" with the boost's price, applied when the
 * order is paid. Registered only when the shop is installed.
 */
final class ShopCheckout implements Checkout, PurchasableResolver
{
    public function start(Boost $boost): ?string
    {
        return app(CartManager::class)->addReference($boost->cartReference(), 1) !== null
            ? app(ShopUrls::class)->route('cart')
            : null;
    }

    public function resolve(string $reference): ?ExternalLine
    {
        $boost = app(Boosts::class)->find($reference);
        $ad = $boost?->ad;

        if ($boost === null || $boost->status !== Boost::PENDING || $ad === null || ! $ad->isPublished()) {
            return null;
        }

        return new ExternalLine(
            name: $boost->type->label().' · '.$ad->title,
            unitPrice: Money::parse($boost->amount, $boost->currency),
            available: 1,
            url: $ad->url(),
            picture: $ad->photoUrl(),
            details: trans_choice('atrium-classifieds::classifieds.boost.days', $boost->days, ['days' => $boost->days]),
        );
    }

    public function placed(OrderItem $item): void {}

    public function paid(OrderItem $item): void
    {
        $boost = app(Boosts::class)->find((string) $item->reference);

        if ($boost !== null) {
            app(Boosts::class)->paid($boost, (string) ($item->order->number ?? $item->order_id));
        }
    }

    public function cancelled(OrderItem $item): void
    {
        $boost = app(Boosts::class)->find((string) $item->reference);

        if ($boost !== null) {
            app(Boosts::class)->cancel($boost);
        }
    }
}
