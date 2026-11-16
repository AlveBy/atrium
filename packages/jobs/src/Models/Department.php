<?php

declare(strict_types=1);

namespace Atrium\Jobs\Models;

use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A team the vacancies belong to ("Engineering", "Sales"), named per
 * language. Visitors filter the vacancy list by it.
 *
 * @property int $id
 * @property int|null $site_id
 * @property bool $active
 * @property int $sort
 */
class Department extends Model implements Administrable
{
    use BelongsToSite, InteractsWithAdmin, Translatable;

    protected $table = 'job_departments';

    /** @var array<int,string> */
    protected array $translatable = ['name'];

    protected $fillable = ['site_id', 'name', 'active', 'sort'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort' => 'integer'];
    }

    /**
     * @return HasMany<Vacancy,$this>
     */
    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-jobs::jobs.modules.departments');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-jobs::jobs.create.department');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'job_departments';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'sort';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    public static function getAdministrationFields(): array
    {
        return [
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            TextField::make('name')->label(__('atrium::fields.standard.name'))->required(),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium::fields.standard.name'))->sortable()->searchable(),
            TextColumn::make('sort')->label(__('atrium::fields.standard.sort'))->sortable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
        ];
    }
}
