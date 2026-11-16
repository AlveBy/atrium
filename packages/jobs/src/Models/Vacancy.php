<?php

declare(strict_types=1);

namespace Atrium\Jobs\Models;

use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\BelongsToField;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\ContentField;
use Atrium\Admin\Fields\DateTimeField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Fields\SlugField;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\HasAdminTabs;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Atrium\Jobs\Enums\EmploymentType;
use Atrium\Jobs\Enums\Experience;
use Atrium\Jobs\Enums\SalaryPeriod;
use Atrium\Jobs\Enums\WorkMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

/**
 * An open position. The title, the address code and the texts are written
 * per language; the theme shows it at /jobs/{code} with the application
 * form and the JobPosting data job search engines read.
 *
 * @property int $id
 * @property int|null $site_id
 * @property bool $active
 * @property Carbon|null $active_from
 * @property Carbon|null $active_to
 * @property int $sort
 * @property int|null $department_id
 * @property int|null $location_id
 * @property WorkMode $work_mode
 * @property EmploymentType $employment_type
 * @property Experience|null $experience
 * @property bool $salary_negotiable
 * @property string|null $salary_from
 * @property string|null $salary_to
 * @property string|null $salary_currency
 * @property SalaryPeriod $salary_period
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property Carbon|null $created_at
 * @property-read Department|null $department
 * @property-read Location|null $location
 */
class Vacancy extends Model implements Administrable
{
    use BelongsToSite, HasAdminTabs, InteractsWithAdmin, Translatable;

    /** The texts of the vacancy page, in the order it shows them. */
    public const SECTIONS = ['description', 'duties', 'requirements', 'conditions'];

    protected $table = 'vacancies';

    /** @var array<int,string> */
    protected array $translatable = ['name', 'code', 'preview_text', 'description', 'duties', 'requirements', 'conditions'];

    protected $fillable = [
        'site_id', 'active', 'active_from', 'active_to', 'sort', 'name', 'code',
        'department_id', 'location_id', 'work_mode', 'employment_type', 'experience',
        'salary_negotiable', 'salary_from', 'salary_to', 'salary_currency', 'salary_period',
        'preview_text', 'description', 'duties', 'requirements', 'conditions',
        'contact_name', 'contact_email', 'contact_phone',
    ];

    /** @var array<string,mixed> */
    protected $attributes = [
        'work_mode' => 'office',
        'employment_type' => 'full_time',
        'salary_period' => 'month',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'active_from' => 'datetime',
            'active_to' => 'datetime',
            'sort' => 'integer',
            'department_id' => 'integer',
            'location_id' => 'integer',
            'work_mode' => WorkMode::class,
            'employment_type' => EmploymentType::class,
            'experience' => Experience::class,
            'salary_negotiable' => 'boolean',
            'salary_from' => 'decimal:2',
            'salary_to' => 'decimal:2',
            'salary_period' => SalaryPeriod::class,
        ];
    }

    /**
     * An empty select comes as an empty string; the column wants null.
     */
    public function setExperienceAttribute(mixed $value): void
    {
        $value = $value instanceof Experience ? $value->value : $value;
        $this->attributes['experience'] = $value === '' || $value === null ? null : (string) $value;
    }

    /**
     * Not required: a salary is often not given. Empty means per month.
     */
    public function setSalaryPeriodAttribute(mixed $value): void
    {
        $value = $value instanceof SalaryPeriod ? $value->value : $value;
        $this->attributes['salary_period'] = $value === '' || $value === null ? SalaryPeriod::Month->value : (string) $value;
    }

    public function setSalaryCurrencyAttribute(?string $value): void
    {
        $value = strtoupper(trim((string) $value));
        $this->attributes['salary_currency'] = $value === '' ? null : $value;
    }

    /**
     * @return BelongsTo<Department,$this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Location,$this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return HasMany<Application,$this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Open for applications: active and inside its period.
     *
     * @param  Builder<Vacancy>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('active', true)
            ->where(fn (Builder $q) => $q->whereNull('active_from')->orWhere('active_from', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('active_to')->orWhere('active_to', '>=', now()));
    }

    public function isOpen(): bool
    {
        return $this->active
            && ($this->active_from === null || $this->active_from->lte(now()))
            && ($this->active_to === null || $this->active_to->gte(now()));
    }

    /**
     * The text of a field in the current language, without falling back to
     * another one: a visitor reads a vacancy only in the language of the page.
     */
    public function translated(string $attribute): string
    {
        return trim((string) $this->getTranslation($attribute, app()->getLocale(), false));
    }

    /**
     * "3,000–4,500 EUR per month", "from 3,000 EUR", "By agreement", or null
     * when nothing is said about the money.
     */
    public function salaryText(): ?string
    {
        $from = $this->salary_from !== null ? (float) $this->salary_from : null;
        $to = $this->salary_to !== null ? (float) $this->salary_to : null;

        if ($from === null && $to === null) {
            return $this->salary_negotiable ? __('atrium-jobs::jobs.public.negotiable') : null;
        }

        $locale = app()->getLocale();
        $format = static fn (float $amount): string => (string) Number::format($amount, maxPrecision: 2, locale: $locale);
        $currency = (string) $this->salary_currency;

        $amount = match (true) {
            $from !== null && $to !== null && $from !== $to => __('atrium-jobs::jobs.public.salary_range', ['from' => $format($from), 'to' => $format($to)]),
            $from !== null && $to !== null => $format($from),
            $from !== null => __('atrium-jobs::jobs.public.salary_from', ['amount' => $format($from)]),
            default => __('atrium-jobs::jobs.public.salary_to', ['amount' => $format((float) $to)]),
        };

        return trim($amount.' '.$currency).' '.$this->salary_period->label();
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-jobs::jobs.modules.vacancies');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-jobs::jobs.create.vacancy');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'vacancies';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'sort';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    /**
     * The theme draws the list with the filter and a vacancy with its form.
     */
    public static function themeView(string $page): ?string
    {
        return match ($page) {
            'index' => 'atrium-jobs::index',
            'page' => 'atrium-jobs::vacancy',
            default => null,
        };
    }

    public static function getAdminTabs(): array
    {
        return [
            ['name' => 'params', 'label' => __('atrium::fields.tabs.main')],
            ['name' => 'detail', 'label' => __('atrium-jobs::jobs.tabs.texts')],
            ['name' => 'contact', 'label' => __('atrium-jobs::jobs.tabs.contact')],
        ];
    }

    public static function getAdministrationFields(): array
    {
        $order = fn (Builder $query): Builder => $query->orderBy('sort')->orderBy('id');

        return [
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true)->tab('params'),
            DateTimeField::make('active_from')->label(__('atrium::fields.standard.active_from'))->tab('params'),
            DateTimeField::make('active_to')->label(__('atrium::fields.standard.active_to'))->hint(__('atrium-jobs::jobs.hints.active_to'))->tab('params'),
            TextField::make('name')->label(__('atrium-jobs::jobs.fields.title'))->required()->tab('params'),
            SlugField::make('code')->label(__('atrium::fields.standard.code'))->from('name')->unique('vacancies', 'code')->tab('params'),
            BelongsToField::make('department_id')->label(__('atrium-jobs::jobs.fields.department'))->model(Department::class)->query($order)->tab('params'),
            BelongsToField::make('location_id')->label(__('atrium-jobs::jobs.fields.location'))->model(Location::class)->query($order)->tab('params'),
            SelectField::make('work_mode')->label(__('atrium-jobs::jobs.fields.work_mode'))->options(WorkMode::options())->default('office')->required()->tab('params'),
            SelectField::make('employment_type')->label(__('atrium-jobs::jobs.fields.employment_type'))->options(EmploymentType::options())->default('full_time')->required()->tab('params'),
            SelectField::make('experience')->label(__('atrium-jobs::jobs.fields.experience'))->options(Experience::options())->clearable()->tab('params'),
            NumberField::make('salary_from')->label(__('atrium-jobs::jobs.fields.salary_from'))->min(0)->tab('params'),
            NumberField::make('salary_to')->label(__('atrium-jobs::jobs.fields.salary_to'))->min(0)->tab('params'),
            SelectField::make('salary_currency')->label(__('atrium-jobs::jobs.fields.salary_currency'))->options(self::currencyOptions())->clearable()->tab('params'),
            SelectField::make('salary_period')->label(__('atrium-jobs::jobs.fields.salary_period'))->options(SalaryPeriod::options())->default('month')->tab('params'),
            CheckboxField::make('salary_negotiable')->label(__('atrium-jobs::jobs.fields.salary_negotiable'))->hint(__('atrium-jobs::jobs.hints.salary_negotiable'))->tab('params'),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500)->tab('params'),

            TextareaField::make('preview_text')->label(__('atrium-jobs::jobs.fields.preview_text'))->rows(3)->hint(__('atrium-jobs::jobs.hints.preview_text'))->tab('detail'),
            ContentField::make('description')->label(__('atrium-jobs::jobs.fields.description'))->tab('detail'),
            ContentField::make('duties')->label(__('atrium-jobs::jobs.fields.duties'))->tab('detail'),
            ContentField::make('requirements')->label(__('atrium-jobs::jobs.fields.requirements'))->tab('detail'),
            ContentField::make('conditions')->label(__('atrium-jobs::jobs.fields.conditions'))->tab('detail'),

            TextField::make('contact_name')->label(__('atrium-jobs::jobs.fields.contact_name'))->maxLength(255)->tab('contact'),
            TextField::make('contact_email')->label(__('atrium-jobs::jobs.fields.contact_email'))->email()->hint(__('atrium-jobs::jobs.hints.contact_email'))->tab('contact'),
            TextField::make('contact_phone')->label(__('atrium-jobs::jobs.fields.contact_phone'))->tel()->maxLength(50)->tab('contact'),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium-jobs::jobs.fields.title'))->sortable()->searchable(),
            TextColumn::make('department.name')->label(__('atrium-jobs::jobs.fields.department')),
            TextColumn::make('location.name')->label(__('atrium-jobs::jobs.fields.location')),
            TextColumn::make('active_to')->label(__('atrium::fields.standard.active_to'))->sortable(),
            TextColumn::make('sort')->label(__('atrium::fields.standard.sort'))->sortable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
        ];
    }

    /**
     * @return array<string,string>
     */
    private static function currencyOptions(): array
    {
        $codes = array_map(static fn (mixed $code): string => strtoupper((string) $code), (array) config('atrium-jobs.currencies', []));

        return array_combine($codes, $codes);
    }
}
