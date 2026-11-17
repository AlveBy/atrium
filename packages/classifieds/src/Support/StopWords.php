<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

use Atrium\Classifieds\Models\StopWord;
use Illuminate\Support\Str;

/**
 * The words that keep an ad from going live by itself: an ad that has one
 * waits for a moderator, whatever the site's moderation setting.
 */
class StopWords
{
    /** @var array<int,array<int,string>> site id => words */
    private array $words = [];

    /**
     * The stop words found in the texts, as written in the list.
     *
     * @return array<int,string>
     */
    public function find(string ...$texts): array
    {
        $haystack = ' '.Str::lower(implode(' ', $texts)).' ';
        $found = [];

        foreach ($this->words() as $word) {
            $needle = Str::lower(trim($word));

            if ($needle === '') {
                continue;
            }

            // A whole word or phrase: "spam" must not catch "spammer-free".
            if (preg_match('/(?<![\\p{L}\\p{N}])'.preg_quote($needle, '/').'(?![\\p{L}\\p{N}])/u', $haystack) === 1) {
                $found[] = $word;
            }
        }

        return array_values(array_unique($found));
    }

    public function flush(): void
    {
        $this->words = [];
    }

    /**
     * @return array<int,string>
     */
    private function words(): array
    {
        $site = (int) app(\Atrium\Admin\Support\Sites::class)->current()->id;

        return $this->words[$site] ??= StopWord::query()->pluck('word')->map(static fn ($word): string => (string) $word)->all();
    }
}
