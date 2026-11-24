<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC\Http;

use Atrium\Integrations\Enums\Direction;
use Atrium\Integrations\Enums\Status;
use Atrium\Integrations\Exchange;
use Atrium\OneC\Models\Run;
use Atrium\OneC\Support\Access;
use Atrium\OneC\Support\Importer;
use Atrium\OneC\Support\Inbox;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * The CommerceML 2 exchange the way 1C:Enterprise speaks it: one address, the
 * step in `mode`, the kind of exchange in `type`.
 *
 *   type=catalog   checkauth → init → file (chunks, zip) → import → success
 *   type=sale      orders: the shop does not exist yet, so 1C is told there are none
 *
 * Every answer is plain text, "success", "progress" or "failure" on the first
 * line, which is what 1C reads.
 */
final class ExchangeController
{
    /** What 1C may send in one request, in bytes (it cuts a bigger file into chunks). */
    private const FILE_LIMIT = 2_097_152;

    public function __construct(
        private readonly Access $access,
        private readonly Inbox $inbox,
        private readonly Importer $importer,
        private readonly Exchange $exchange,
    ) {}

    public function __invoke(Request $request): Response
    {
        $mode = (string) $request->query('mode');
        $type = (string) $request->query('type');

        if ($mode === 'checkauth') {
            return $this->checkauth($request);
        }

        if (! in_array($type, ['catalog', 'sale'], true)) {
            return $this->reply('failure', __('atrium-1c::onec.reply.unknown_type', ['type' => $type]));
        }

        if (! $this->access->allows($request)) {
            return $this->reply('failure', __('atrium-1c::onec.reply.denied'));
        }

        return match ($mode) {
            'init' => $this->init($type),
            'file' => $this->file($request, $type),
            'import' => $this->import($request, $type),
            'query' => $this->query($type),
            'success' => $this->reply('success'),
            default => $this->reply('failure', __('atrium-1c::onec.reply.unknown_mode', ['mode' => $mode])),
        };
    }

    private function checkauth(Request $request): Response
    {
        $token = $this->access->login($request);

        if ($token === null) {
            $this->exchange->record('1c', 'checkauth', Status::Failed, error: __('atrium-1c::onec.reply.wrong_login'), direction: Direction::In);

            return $this->reply('failure', __('atrium-1c::onec.reply.wrong_login'));
        }

        return $this->reply('success', Access::COOKIE, $token);
    }

    private function init(string $type): Response
    {
        if ($type === 'catalog') {
            $this->inbox->reset();
        }

        return $this->reply('zip=yes', 'file_limit='.self::FILE_LIMIT);
    }

    private function file(Request $request, string $type): Response
    {
        if ($type === 'sale') {
            return $this->reply('success');
        }

        $name = (string) $request->query('filename');

        if (! $this->inbox->valid($name)) {
            return $this->reply('failure', __('atrium-1c::onec.reply.bad_name'));
        }

        $this->inbox->append($name, $request->getContent());

        return $this->reply('success');
    }

    private function query(string $type): Response
    {
        if ($type !== 'sale') {
            return $this->reply('failure', __('atrium-1c::onec.reply.unknown_mode', ['mode' => 'query']));
        }

        $this->exchange->record('1c', 'sale.query', Status::Ok, message: __('atrium-1c::onec.reply.no_orders'), direction: Direction::In);

        $document = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<КоммерческаяИнформация ВерсияСхемы="2.08" ДатаФормирования="'.now()->format('Y-m-d\TH:i:s').'"></КоммерческаяИнформация>';

        return response($document, 200, ['Content-Type' => 'text/xml; charset=UTF-8']);
    }

    private function import(Request $request, string $type): Response
    {
        if ($type === 'sale') {
            return $this->reply('success');
        }

        $file = (string) $request->query('filename');

        try {
            $this->inbox->extract();
        } catch (Throwable $e) {
            return $this->failed(null, $file, $e);
        }

        if (! $this->inbox->valid($file) || ! $this->inbox->has($file)) {
            return $this->reply('failure', __('atrium-1c::onec.reply.no_file', ['file' => $file]));
        }

        $key = 'atrium-1c:step:'.$this->access->token($request).':'.$file;
        $state = Cache::get($key);
        $state = is_array($state) ? $state : ['run' => 0, 'next' => 0];
        $run = Run::query()->find((int) $state['run']);
        $offset = $run === null ? 0 : (int) $state['next'];
        $run ??= Run::query()->create(['file' => $file, 'status' => Run::RUNNING, 'started_at' => now()]);

        try {
            $result = $this->importer->run($file, $run, $offset, max(1, (int) config('atrium-1c.step', 500)));
        } catch (Throwable $e) {
            Cache::forget($key);

            return $this->failed($run, $file, $e);
        }

        if (! $result['done']) {
            $run->save();
            Cache::put($key, ['run' => $run->id, 'next' => $result['next']], 3600);

            return $this->reply('progress', __('atrium-1c::onec.reply.progress', ['count' => $result['next']]));
        }

        Cache::forget($key);
        $run->forceFill(['status' => Run::DONE, 'finished_at' => now()])->save();
        $summary = __('atrium-1c::onec.reply.summary', [
            'sections_created' => $run->sections_created, 'sections_updated' => $run->sections_updated,
            'products_created' => $run->products_created, 'products_updated' => $run->products_updated,
            'offers' => $run->offers_updated, 'skipped' => $run->skipped, 'errors' => $run->errors,
        ]);
        $this->exchange->record('1c', 'catalog.import', $run->errors > 0 ? Status::Failed : Status::Ok, subject: $file, message: $summary, error: $run->error, direction: Direction::In);

        return $this->reply('success', $summary);
    }

    private function failed(?Run $run, string $file, Throwable $e): Response
    {
        $run?->forceFill(['status' => Run::FAILED, 'finished_at' => now(), 'error' => $e->getMessage()])->save();
        $this->exchange->record('1c', 'catalog.import', Status::Failed, subject: $file, error: $e->getMessage(), direction: Direction::In);

        return $this->reply('failure', $e->getMessage());
    }

    private function reply(string ...$lines): Response
    {
        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
