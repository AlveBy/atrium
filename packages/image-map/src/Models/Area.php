<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Models;

use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\BelongsToField;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\ContentField;
use Atrium\Admin\Fields\DisplayField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Modules\Soft;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\HasAdminTabs;
use Atrium\Admin\Traits\HasSoftRelations;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Atrium\ImageMap\Action;
use Atrium\ImageMap\ContentType;
use Atrium\ImageMap\Geometry\Geometry;
use Atrium\ImageMap\ImageMapSettings;
use Atrium\ImageMap\Rules\ValidGeometry;
use Atrium\ImageMap\Shape;
use Atrium\ImageMap\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\ValidationException;

/**
 * An area drawn on a layer: a shop, an office, a lot, an exit.
 *
 * It has a title and a short description, an action (tooltip, popover,
 * modal window or link) and what that shows: rich text, custom HTML, or
 * the card of a record of another module. A status (free, booked, sold)
 * colours and patterns it.
 *
 * @property int $id
 * @property int|null $site_id
 * @property int $layer_id
 * @property int|null $status_id
 * @property string $shape
 * @property array<string,mixed> $geometry
 * @property string $action
 * @property string $content_type
 * @property string|null $url
 * @property string|null $linkable_type
 * @property int|null $linkable_id
 * @property bool $active
 * @property int $sort
 * @property-read Layer|null $layer
 * @property-read Status|null $status
 * @property-read Model|null $linkable
 */
class Area extends Model implements Administrable
{
    use BelongsToSite, HasAdminTabs, HasSoftRelations, InteractsWithAdmin, Translatable;

    /** Who may write custom HTML: a permission of its own, given in the role form. */
    public const HTML_PERMISSION = 'image_map.html';

    protected $table = 'image_map_areas';

    /** @var array<int,string> */
    protected array $translatable = ['title', 'description', 'content', 'html'];

    protected $fillable = [
        'site_id', 'layer_id', 'status_id', 'shape', 'geometry', 'title', 'description', 'action',
        'content_type', 'content', 'html', 'url', 'linkable_type', 'linkable_id', 'active', 'sort',
    ];

    protected $attributes = ['action' => 'popover', 'content_type' => 'text', 'active' => true, 'sort' => 500];

    /** @var array<int,string> */
    protected $appends = ['geometry_json'];

    protected function casts(): array
    {
        return [
            'geometry' => 'array',
            'layer_id' => 'integer',
            'status_id' => 'integer',
            'linkable_id' => 'integer',
            'active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Area $area): void {
            $layer = $area->layer_id === null ? null : Layer::query()->withoutGlobalScopes()->find($area->layer_id);

            if ($area->site_id === null && $layer !== null) {
                $area->site_id = $layer->site_id;
            }

            $area->guardGeometry($layer);
            $area->sanitize();
        });
    }

    /**
     * @return BelongsTo<Layer,$this>
     */
    public function layer(): BelongsTo
    {
        return $this->belongsTo(Layer::class);
    }

    /**
     * @return BelongsTo<Status,$this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * The record of another module the area shows: a store, a lot, an
     * office. Null when it was deleted or its module was removed.
     *
     * @return MorphTo<covariant Model,$this>
     */
    public function linkable(): MorphTo
    {
        return $this->softMorphTo();
    }

    public function shapeCase(): Shape
    {
        return Shape::tryFrom($this->shape) ?? Shape::Rect;
    }

    public function actionCase(): Action
    {
        return Action::tryFrom($this->action) ?? Action::Popover;
    }

    public function contentTypeCase(): ContentType
    {
        return ContentType::tryFrom($this->content_type) ?? ContentType::Text;
    }

    /**
     * The geometry as text for the admin form and the API, where a JSON
     * field is edited as a string.
     */
    public function getGeometryJsonAttribute(): string
    {
        return json_encode($this->geometry ?? new \stdClass, JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * @return array<string,mixed>
     */
    public function scaledGeometry(float $x, float $y): array
    {
        $geometry = $this->geometry ?? [];

        $scaled = match ($this->shapeCase()) {
            Shape::Rect => ['x' => $geometry['x'] * $x, 'y' => $geometry['y'] * $y, 'width' => $geometry['width'] * $x, 'height' => $geometry['height'] * $y],
            Shape::Circle => ['cx' => $geometry['cx'] * $x, 'cy' => $geometry['cy'] * $y, 'r' => $geometry['r'] * min($x, $y)],
            Shape::Point => ['x' => $geometry['x'] * $x, 'y' => $geometry['y'] * $y],
            Shape::Polygon => ['points' => array_map(static fn (array $p): array => [$p[0] * $x, $p[1] * $y], $geometry['points'] ?? [])],
        };

        return Geometry::normalize($this->shapeCase(), $scaled);
    }

    /**
     * Whether the signed-in administrator may write custom HTML.
     */
    public static function canWriteHtml(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof AdminUser && $user->hasAdminPermission(self::HTML_PERMISSION);
    }

    /**
     * The geometry comes from the form as JSON text; an empty link type
     * clears the link.
     */
    public function transformAdminData(array $data): array
    {
        if (array_key_exists('geometry_json', $data)) {
            $decoded = is_array($data['geometry_json']) ? $data['geometry_json'] : json_decode((string) $data['geometry_json'], true);
            $data['geometry'] = is_array($decoded) ? $decoded : [];
            unset($data['geometry_json']);
        }

        if (array_key_exists('linkable_type', $data) && blank($data['linkable_type'])) {
            $data['linkable_type'] = null;
            $data['linkable_id'] = null;
        }

        // Without the permission the field is not on the form; whatever came
        // in under its name anyway is not taken.
        if (! self::canWriteHtml()) {
            unset($data['html']);
        }

        return $data;
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-image-map::image-map.modules.areas');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-image-map::image-map.create.area');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'image_map_areas';
    }

    public function getAdminTitle(): string
    {
        return (string) ($this->getTranslation('title', app()->getLocale()) ?: "#{$this->id}");
    }

    public static function getDefaultSortColumn(): string
    {
        return 'sort';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    public function scopeAdminList(Builder $query): Builder
    {
        return $query->with(['layer', 'status']);
    }

    public static function getAdminTabs(): array
    {
        return [
            ['name' => 'params', 'label' => __('atrium-image-map::image-map.tabs.params')],
            ['name' => 'shape', 'label' => __('atrium-image-map::image-map.tabs.shape')],
            ['name' => 'content', 'label' => __('atrium-image-map::image-map.tabs.content')],
        ];
    }

    public static function getAdministrationFields(): array
    {
        $k = 'atrium-image-map::image-map.';

        return [
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true)->tab('params'),
            BelongsToField::make('layer_id')->label(__($k.'fields.layer'))->model(Layer::class)
                ->displayColumn('option_label')->required()->tab('params'),
            TextField::make('title')->label(__($k.'fields.title'))->required()->tab('params'),
            TextareaField::make('description')->label(__($k.'fields.description'))
                ->hint(__($k.'hints.description'))->tab('params'),
            BelongsToField::make('status_id')->label(__($k.'fields.status'))->model(Status::class)->tab('params'),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500)->tab('params'),

            SelectField::make('shape')->label(__($k.'fields.shape'))->options(Shape::options())
                ->required()->default(Shape::Rect->value)->tab('shape'),
            TextareaField::make('geometry_json')->label(__($k.'fields.geometry'))
                ->hint(__($k.'hints.geometry'))->required()->rules([new ValidGeometry])->tab('shape'),
            DisplayField::make('drawing')->label(__($k.'fields.drawing'))
                ->html(fn (Area $area): string => view('atrium-image-map::admin.area-drawing', ['area' => $area])->render())
                ->tab('shape'),

            SelectField::make('action')->label(__($k.'fields.action'))->options(Action::options())
                ->required()->default(Action::Popover->value)->tab('content'),
            SelectField::make('content_type')->label(__($k.'fields.content_type'))
                ->options(self::canWriteHtml() ? ContentType::options() : array_diff_key(ContentType::options(), [ContentType::Html->value => true]))
                ->required()->default(ContentType::Text->value)->tab('content'),
            ContentField::make('content')->label(__($k.'fields.content'))->tab('content'),
            ...(self::canWriteHtml() ? [
                TextareaField::make('html')->label(__($k.'fields.html'))->hint(__($k.'hints.html'))->tab('content'),
            ] : []),
            SelectField::make('linkable_type')->label(__($k.'fields.linkable_type'))
                ->options(array_diff_key(Soft::targets(), array_flip([ImageMap::class, Layer::class, self::class, Status::class])))
                ->hint(__($k.'hints.linkable'))->tab('content'),
            NumberField::make('linkable_id')->label(__($k.'fields.linkable_id'))
                ->min(1)->rules(['nullable', 'required_with:linkable_type', 'integer'])->tab('content'),
            TextField::make('url')->label(__($k.'fields.url'))->hint(__($k.'hints.url'))
                ->rules(['nullable', 'max:2000', 'regex:#^(https?://|/|\#|mailto:|tel:)#i'])->tab('content'),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        $k = 'atrium-image-map::image-map.';

        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('title')->label(__($k.'fields.title'))->sortable()->searchable(),
            TextColumn::make('layer.name')->label(__($k.'fields.layer')),
            TextColumn::make('status.name')->label(__($k.'fields.status')),
            TextColumn::make('action')->label(__($k.'fields.action')),
            TextColumn::make('sort')->label(__('atrium::fields.standard.sort'))->sortable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
        ];
    }

    /**
     * No area is saved with a shape the map could not draw — whoever saves
     * it: the admin form, the editor, the API, a seeder.
     */
    private function guardGeometry(?Layer $layer): void
    {
        $shape = Shape::tryFrom((string) $this->shape);

        if ($shape === null) {
            throw ValidationException::withMessages(['shape' => __('atrium-image-map::image-map.geometry.invalid_shape')]);
        }

        $errors = Geometry::check($shape, $this->geometry, $layer?->width, $layer?->height);

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'geometry_json' => array_map(
                    static fn (array $error): string => __('atrium-image-map::image-map.'.$error[0], $error[1]),
                    $errors,
                ),
            ]);
        }

        $this->geometry = Geometry::normalize($shape, $this->geometry);
    }

    /**
     * Rich text keeps only text markup; custom HTML keeps more, but never
     * scripts, event attributes or javascript: addresses.
     */
    private function sanitize(): void
    {
        $hosts = ImageMapSettings::iframeHosts();

        foreach (['content' => HtmlSanitizer::TEXT, 'html' => HtmlSanitizer::CUSTOM] as $attribute => $level) {
            if (! $this->isDirty($attribute)) {
                continue;
            }

            $sanitizer = new HtmlSanitizer($level === HtmlSanitizer::CUSTOM ? $hosts : []);
            $clean = [];

            foreach ($this->getTranslations($attribute) as $language => $html) {
                $value = $sanitizer->clean(is_string($html) ? $html : null, $level);

                if ($value !== '') {
                    $clean[$language] = $value;
                }
            }

            if ($clean === []) {
                $this->attributes[$attribute] = null;
            } else {
                $this->setTranslations($attribute, $clean);
            }
        }
    }
}
