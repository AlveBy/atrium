<?php

declare(strict_types=1);

namespace Atrium\Jobs;

use Atrium\Admin\Modules\Modules;
use Atrium\Jobs\Enums\EmploymentType;
use Atrium\Jobs\Enums\WorkMode;
use Atrium\Jobs\Models\Department;
use Atrium\Jobs\Models\Location;
use Atrium\Jobs\Models\Vacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * The open vacancies a visitor sees and the filter over them.
 */
final class Jobs
{
    /** The filter's fields in the address: ?department=2&location=1&type=full_time&mode=remote */
    public const FILTERS = ['department', 'location', 'type', 'mode'];

    public static function installed(): bool
    {
        return app(Modules::class)->available(Vacancy::class);
    }

    /**
     * Open vacancies in the editors' order: by sort, the newest first among
     * equals, then by id so pages do not swap records.
     *
     * @return Builder<Vacancy>
     */
    public function open(): Builder
    {
        return Vacancy::query()->open()
            ->orderBy('sort')
            ->orderByDesc('active_from')
            ->orderBy('id');
    }

    /**
     * The filter values of a request, the unknown ones dropped.
     *
     * @return array{department:?int,location:?int,type:?string,mode:?string}
     */
    public function filters(Request $request): array
    {
        $id = static function (mixed $value): ?int {
            return is_string($value) && ctype_digit($value) && (int) $value > 0 ? (int) $value : null;
        };

        $type = $request->query('type');
        $mode = $request->query('mode');

        return [
            'department' => $id($request->query('department')),
            'location' => $id($request->query('location')),
            'type' => is_string($type) && EmploymentType::tryFrom($type) !== null ? $type : null,
            'mode' => is_string($mode) && WorkMode::tryFrom($mode) !== null ? $mode : null,
        ];
    }

    /**
     * @param  array{department:?int,location:?int,type:?string,mode:?string}  $filters
     * @return Builder<Vacancy>
     */
    public function filtered(array $filters): Builder
    {
        return $this->open()
            ->when($filters['department'], fn (Builder $q, int $id) => $q->where('department_id', $id))
            ->when($filters['location'], fn (Builder $q, int $id) => $q->where('location_id', $id))
            ->when($filters['type'], fn (Builder $q, string $type) => $q->where('employment_type', $type))
            ->when($filters['mode'], fn (Builder $q, string $mode) => $q->where('work_mode', $mode))
            ->with(['department', 'location']);
    }

    /**
     * What the filter offers: only values some open vacancy has, so no
     * choice leads to an empty list.
     *
     * @return array{department:array<int,string>,location:array<int,string>,type:array<string,string>,mode:array<string,string>}
     */
    public function options(): array
    {
        $open = $this->open()->reorder()->toBase();
        $used = fn (string $column): array => (clone $open)->whereNotNull($column)->distinct()->pluck($column)->all();

        $named = function (string $model, array $ids): array {
            if ($ids === []) {
                return [];
            }

            /** @var class-string<Department|Location> $model */
            return $model::query()->whereKey($ids)->where('active', true)->orderBy('sort')->orderBy('id')->get()
                ->mapWithKeys(fn (Department|Location $record): array => [$record->id => (string) $record->name])
                ->filter(fn (string $name): bool => $name !== '')
                ->all();
        };

        $types = $used('employment_type');
        $modes = $used('work_mode');

        return [
            'department' => $named(Department::class, $used('department_id')),
            'location' => $named(Location::class, $used('location_id')),
            'type' => array_filter(EmploymentType::options(), fn (string $value): bool => in_array($value, $types, true), ARRAY_FILTER_USE_KEY),
            'mode' => array_filter(WorkMode::options(), fn (string $value): bool => in_array($value, $modes, true), ARRAY_FILTER_USE_KEY),
        ];
    }
}
