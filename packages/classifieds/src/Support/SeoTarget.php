<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Where the board's schema.org nodes go when the SEO module is installed.
 */
interface SeoTarget
{
    /**
     * @param  array<int,array{label:string,url:string}>  $breadcrumbs
     * @param  array<int,array<string,mixed>>  $nodes
     */
    public function page(?Model $record, bool $isRecord, array $breadcrumbs, array $nodes): void;
}
