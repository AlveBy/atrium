<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Http\Controllers;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Support\ContentLanguages;
use Atrium\ImageMap\Action;
use Atrium\ImageMap\Geometry\Geometry;
use Atrium\ImageMap\Models\Area;
use Atrium\ImageMap\Models\Layer;
use Atrium\ImageMap\Models\Status;
use Atrium\ImageMap\Rules\ValidGeometry;
use Atrium\ImageMap\Shape;
use Atrium\ImageMap\Support\Assets;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The drawing page of a layer, shown in a frame on the layer's form, and
 * the one request that saves everything drawn there: new areas, moved
 * ones and deleted ones, together or not at all.
 */
final class EditorController
{
    private const MODULE = 'image_map_areas';

    public function show(Request $request, int $layer): View
    {
        $user = $this->user($request);
        abort_unless($user->hasAdminPermission('image_map_layers.view') && $user->hasAdminPermission(self::MODULE.'.view'), 403);

        $layer = Layer::query()->with('map')->findOrFail($layer);
        $language = app(ContentLanguages::class)->default();

        return view('atrium-image-map::admin.editor', [
            'layer' => $layer,
            'state' => [
                'layer' => [
                    'id' => $layer->id,
                    'name' => (string) $layer->option_label,
                    'image' => $layer->imageUrl(),
                    'width' => (int) $layer->width,
                    'height' => (int) $layer->height,
                ],
                'areas' => $this->areas($layer, $language),
                'statuses' => Status::query()->orderBy('sort')->orderBy('id')->get()->map(fn (Status $status): array => [
                    'id' => $status->id,
                    'name' => (string) $status->getTranslation('name', app()->getLocale()),
                    'color' => $status->hex(),
                    'outline' => $status->outlineCase()->value,
                ])->all(),
                'actions' => Action::options(),
                'can' => [
                    'create' => $user->hasAdminPermission(self::MODULE.'.create'),
                    'update' => $user->hasAdminPermission(self::MODULE.'.update'),
                    'delete' => $user->hasAdminPermission(self::MODULE.'.delete'),
                ],
                'language' => $language,
                'limits' => ['minSize' => Geometry::MIN_SIZE, 'maxPoints' => Geometry::MAX_POINTS],
                'urls' => [
                    'sync' => route('admin.image-map.sync', $layer->id),
                    'edit' => route('admin.'.self::MODULE.'.edit', ['id' => '__ID__']),
                ],
                't' => trans('atrium-image-map::image-map.editor'),
            ],
            'css' => Assets::url('editor.css'),
            'js' => Assets::url('editor.js'),
        ]);
    }

    public function sync(Request $request, int $layer): JsonResponse
    {
        $user = $this->user($request);
        $layer = Layer::query()->findOrFail($layer);

        $data = $request->validate([
            'areas' => ['present', 'array', 'max:2000'],
            'areas.*' => ['array'],
            'areas.*.id' => ['nullable', 'integer'],
            'areas.*.shape' => ['required', Rule::enum(Shape::class)],
            'areas.*.geometry' => ['required', 'array'],
            'areas.*.title' => ['required', 'string', 'max:255'],
            'areas.*.status_id' => ['nullable', 'integer', Rule::in(Status::query()->pluck('id')->all())],
            'areas.*.action' => ['required', Rule::enum(Action::class)],
            'deleted' => ['array'],
            'deleted.*' => ['integer'],
        ]);

        $existing = $layer->areas()->get()->keyBy('id');
        $deleted = array_values(array_unique(array_map('intval', $data['deleted'] ?? [])));
        $rows = $data['areas'];

        $this->checkGeometry($rows, $layer);
        $this->checkOwnership($rows, $deleted, $existing->keys()->all());

        $creates = array_filter($rows, fn (array $row): bool => empty($row['id']));
        $updates = array_filter($rows, fn (array $row): bool => ! empty($row['id']) && $this->changed($existing[(int) $row['id']], $row));

        abort_if($creates !== [] && ! $user->hasAdminPermission(self::MODULE.'.create'), 403);
        abort_if($updates !== [] && ! $user->hasAdminPermission(self::MODULE.'.update'), 403);
        abort_if($deleted !== [] && ! $user->hasAdminPermission(self::MODULE.'.delete'), 403);

        $language = app(ContentLanguages::class)->default();
        $saved = [];

        DB::transaction(function () use ($rows, $existing, $deleted, $layer, $language, &$saved): void {
            foreach ($deleted as $id) {
                $area = $existing[$id];
                $area->delete();
                Hook::fire('atrium.record.deleted', $area);
            }

            $sort = (int) $layer->areas()->max('sort');

            foreach ($rows as $row) {
                $area = empty($row['id']) ? new Area(['layer_id' => $layer->id, 'sort' => $sort += 10]) : $existing[(int) $row['id']];

                if ($area->exists && ! $this->changed($area, $row)) {
                    $saved[] = $area;

                    continue;
                }

                $area->shape = $row['shape'];
                $area->geometry = $row['geometry'];
                $area->status_id = $row['status_id'] ?? null;
                $area->action = $row['action'];
                $area->setTranslation('title', $language, trim($row['title']));
                $context = $area->exists ? 'edit' : 'create';
                $area->save();
                Hook::fire('atrium.record.saved', $area, $context);
                $saved[] = $area;
            }
        });

        return response()->json([
            'areas' => array_map(fn (Area $area): array => $this->present($area, $language), $saved),
            'message' => __('atrium-image-map::image-map.editor.saved'),
        ]);
    }

    private function user(Request $request): AdminUser
    {
        $user = $request->user(config('atrium.auth.guard', 'admin'));
        abort_unless($user instanceof AdminUser, 403);

        return $user;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function areas(Layer $layer, string $language): array
    {
        return $layer->areas()->orderBy('sort')->orderBy('id')->get()
            ->map(fn (Area $area): array => $this->present($area, $language))
            ->values()->all();
    }

    /**
     * @return array<string,mixed>
     */
    private function present(Area $area, string $language): array
    {
        return [
            'id' => $area->id,
            'shape' => $area->shape,
            'geometry' => $area->geometry,
            'title' => (string) $area->getTranslation('title', $language),
            'status_id' => $area->status_id,
            'action' => $area->action,
            'active' => (bool) $area->active,
        ];
    }

    /**
     * Each area against its shape and the picture, the errors under its
     * own number, so the editor marks the very area.
     *
     * @param  array<int,array<string,mixed>>  $rows
     */
    private function checkGeometry(array $rows, Layer $layer): void
    {
        $rules = [];

        foreach ($rows as $index => $row) {
            $rules["areas.{$index}.geometry"] = [new ValidGeometry((string) $row['shape'], $layer)];
        }

        Validator::make(['areas' => $rows], $rules)->validate();
    }

    /**
     * @param  array<int,array<string,mixed>>  $rows
     * @param  list<int>  $deleted
     * @param  list<int>  $known
     */
    private function checkOwnership(array $rows, array $deleted, array $known): void
    {
        $errors = [];

        foreach ($rows as $index => $row) {
            if (! empty($row['id']) && (! in_array((int) $row['id'], $known, true) || in_array((int) $row['id'], $deleted, true))) {
                $errors["areas.{$index}.id"] = __('atrium-image-map::image-map.editor.unknown_area');
            }
        }

        foreach ($deleted as $index => $id) {
            if (! in_array($id, $known, true)) {
                $errors["deleted.{$index}"] = __('atrium-image-map::image-map.editor.unknown_area');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string,mixed>  $row
     */
    private function changed(Area $area, array $row): bool
    {
        $shape = Shape::from((string) $row['shape']);

        return $area->shape !== $shape->value
            || Geometry::normalize($shape, $row['geometry']) !== Geometry::normalize($shape, $area->geometry ?? [])
            || (int) ($area->status_id ?? 0) !== (int) ($row['status_id'] ?? 0)
            || $area->action !== $row['action']
            || trim((string) $area->getTranslation('title', app(ContentLanguages::class)->default(), false)) !== trim((string) $row['title']);
    }
}
