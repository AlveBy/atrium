<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Integrations;

use Atrium\Classifieds\Support\SeoTarget;
use Atrium\Seo\SeoPage;
use Illuminate\Database\Eloquent\Model;

/**
 * The SEO module (alveby/atrium-seo) receives the board's pages: the record,
 * its breadcrumbs and the schema.org nodes, printed in its own @graph.
 */
final class SeoBridge implements SeoTarget
{
    public function page(?Model $record, bool $isRecord, array $breadcrumbs, array $nodes): void
    {
        $page = app(SeoPage::class);

        if ($record !== null) {
            $page->show($record);
        }

        $page->kind($isRecord ? SeoPage::RECORD : SeoPage::SECTION)->breadcrumbs($breadcrumbs);

        foreach ($nodes as $node) {
            $page->addSchema($node);
        }
    }
}
