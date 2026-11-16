<?php

declare(strict_types=1);

namespace App\Models;

use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\DateColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Contracts\Sitemapable;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\ContentField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\SlugField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Support\ContentLanguages;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Article extends Model implements Administrable, Sitemapable
{
    use BelongsToSite, InteractsWithAdmin, Translatable;

    /** @var array<int,string> */
    protected array $translatable = ['name', 'code', 'preview_text', 'detail_text'];

    protected $fillable = ['name', 'code', 'active', 'sort', 'preview_text', 'detail_text'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public static function getAdminLabel(): string
    {
        return 'Articles';
    }

    public static function getAdministrationFields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            SlugField::make('code')->label('Code')->from('name')->unique('articles', 'code'),
            CheckboxField::make('active')->label('Active')->default(true),
            NumberField::make('sort')->label('Sort')->default(500),
            ContentField::make('preview_text')->label('Preview text'),
            ContentField::make('detail_text')->label('Detail text'),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label('ID')->sortable(),
            TextColumn::make('name')->label('Name')->sortable()->searchable(),
            BooleanColumn::make('active')->label('Active'),
            DateColumn::make('updated_at')->label('Updated')->sortable(),
        ];
    }

    public static function sitemapQuery(): Builder
    {
        return static::query()->where('active', true);
    }

    /**
     * The article's page in a language it is written in: it needs both a
     * name and an address there.
     */
    public function sitemapUrl(string $language): ?string
    {
        $code = $this->getTranslation('code', $language, false);

        if ($code === null || $code === '' || ! $this->getTranslation('name', $language, false)) {
            return null;
        }

        return app(ContentLanguages::class)->route('articles.show', ['code' => $code], $language);
    }
}
