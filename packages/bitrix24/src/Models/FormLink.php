<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24\Models;

use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\BelongsToField;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Bitrix24\Support\Payload;
use Atrium\Forms\Models\Form;
use Closure;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How one form goes to Bitrix24: as a lead or as a contact with a deal, and
 * which form field fills which Bitrix24 field. A form without a link is sent
 * as a lead with the fields placed by their type; an inactive link keeps the
 * form out of Bitrix24.
 *
 * @property int $id
 * @property int $form_id
 * @property string $mode
 * @property bool $active
 * @property string|null $mapping
 * @property-read string $form_name
 * @property-read string $mode_label
 */
class FormLink extends Model implements Administrable
{
    use InteractsWithAdmin;

    public const LEAD = 'lead';

    public const DEAL = 'deal';

    protected $table = 'bitrix24_form_links';

    protected $fillable = ['form_id', 'mode', 'active', 'mapping'];

    /** @var list<string> */
    protected $appends = ['form_name', 'mode_label'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /**
     * @return BelongsTo<Form,$this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class)->withoutGlobalScopes();
    }

    /** @return Attribute<string,never> */
    protected function formName(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->form?->name);
    }

    /** @return Attribute<string,never> */
    protected function modeLabel(): Attribute
    {
        return Attribute::get(fn (): string => __('atrium-bitrix24::bitrix24.modes.'.($this->mode === self::DEAL ? 'deal' : 'lead')));
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-bitrix24::bitrix24.title_links');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'bitrix24_forms';
    }

    public static function getAdministrationFields(): array
    {
        return [
            BelongsToField::make('form_id')->label(__('atrium-bitrix24::bitrix24.fields.form'))->model(Form::class)->required()->unique('bitrix24_form_links', 'form_id'),
            SelectField::make('mode')->label(__('atrium-bitrix24::bitrix24.fields.mode'))
                ->options([self::LEAD => __('atrium-bitrix24::bitrix24.modes.lead'), self::DEAL => __('atrium-bitrix24::bitrix24.modes.deal')])
                ->default(self::LEAD)->required(),
            CheckboxField::make('active')->label(__('atrium-bitrix24::bitrix24.fields.active'))->default(true),
            TextareaField::make('mapping')->label(__('atrium-bitrix24::bitrix24.fields.mapping'))
                ->hint(__('atrium-bitrix24::bitrix24.fields.mapping_hint'))
                ->placeholder("phone = PHONE\nmessage = COMMENTS")
                ->rows(8)
                ->rules(['max:5000', static function (string $attribute, mixed $value, Closure $fail): void {
                    $line = Payload::badLine(is_string($value) ? $value : null);

                    if ($line !== null) {
                        $fail(__('atrium-bitrix24::bitrix24.errors.mapping_line', ['line' => $line]));
                    }
                }]),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('form_name')->label(__('atrium-bitrix24::bitrix24.fields.form')),
            TextColumn::make('mode_label')->label(__('atrium-bitrix24::bitrix24.fields.mode')),
            BooleanColumn::make('active')->label(__('atrium-bitrix24::bitrix24.fields.active')),
        ];
    }
}
