<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Who acts in the cabinet: a person on their own (no company), or a person
 * on behalf of a company, whose ads every member shares. The same rule as
 * the personal cabinet's AccountContext.
 */
final class Owner
{
    public function __construct(
        public readonly int $userId,
        public readonly ?int $companyId = null,
        public readonly string $name = '',
    ) {}

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scope(Builder $query): Builder
    {
        if ($this->companyId !== null) {
            return $query->where($query->qualifyColumn('account_company_id'), $this->companyId);
        }

        return $query->where($query->qualifyColumn('account_user_id'), $this->userId)
            ->whereNull($query->qualifyColumn('account_company_id'));
    }

    public function owns(Model $record): bool
    {
        $company = $record->getAttribute('account_company_id');

        if ($this->companyId !== null) {
            return $company !== null && (int) $company === $this->companyId;
        }

        return $company === null && (int) $record->getAttribute('account_user_id') === $this->userId;
    }

    /**
     * @return array{account_user_id:int,account_company_id:int|null}
     */
    public function attributes(): array
    {
        return ['account_user_id' => $this->userId, 'account_company_id' => $this->companyId];
    }

    /**
     * The key rate limits count by: a company's members share its limit.
     */
    public function key(): string
    {
        return $this->companyId !== null ? 'c'.$this->companyId : 'u'.$this->userId;
    }
}
