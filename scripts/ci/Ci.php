<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Ci;

use DOMDocument;
use DOMElement;
use PDO;
use Throwable;

/**
 * bin/ci: the checks GitHub Actions runs, on this machine. See docs/testing.md.
 */
final class Ci
{
    public const STEPS = ['lint', 'static', 'tests', 'coverage', 'mutation', 'dusk'];

    public const DEFAULT_STEPS = ['lint', 'static', 'tests', 'coverage', 'mutation'];

    /** The mutation score the changed code must reach. */
    public const MIN_MSI = 85;

    private const USAGE = <<<'TXT'
        Usage: bin/ci [step ...] [options]

        Steps (default: all but dusk):
          lint       Pint and the copyright header
          static     PHPStan
          tests      PHPUnit of every package, one process each, on every --db
          coverage   PHPUnit with coverage on SQLite: changed lines 100% covered,
                     no package with more uncovered lines than .ci/coverage says
          mutation   Infection on the changed lines: MSI at least 85%
          dusk       browser tests (needs .env.dusk.local and Chrome)

        Options:
          --changed          only the packages the change can break
          --package=a,b      only these packages (app = the demo application)
          --db=sqlite,mysql,pgsql
                             databases for "tests" (default: SQLite, plus MySQL and
                             PostgreSQL when Docker or a server is there)
          --base=REV         what the change is compared with (default: the merge
                             base with origin/main)
          --dusk             add the dusk step
          --all-lines        mutation: every line of the packages, not only the
                             changed ones (a report, not a gate)
          --jobs=N           processes at a time (default: half the CPUs)
          -h, --help
        TXT;

    /** @var list<string> */
    private array $steps = [];

    /** @var list<string>|null */
    private ?array $dbs = null;

    private bool $changed = false;

    private bool $withDusk = false;

    private bool $allLines = false;

    /** @var list<string>|null */
    private ?array $only = null;

    private ?string $base = null;

    private int $jobs;

    /** @var list<array{string, string, bool, float}> step, label, passed, seconds */
    private array $results = [];

    /** @var list<string> */
    private array $notes = [];

    /** @var list<array{string, string}> driver, database */
    private array $databases = [];

    private string $build;

    public function __construct(private readonly string $root)
    {
        $this->build = $root.'/build/ci';
        $this->jobs = max(1, intdiv($this->cpus(), 2));
    }

    /**
     * @param  list<string>  $argv
     */
    public function run(array $argv): int
    {
        try {
            $this->parse($argv);
        } catch (CiException $e) {
            fwrite(STDERR, $e->getMessage()."\n\n".self::USAGE."\n");

            return 2;
        }

        if ($this->steps === ['help']) {
            echo self::USAGE."\n";

            return 0;
        }

        register_shutdown_function($this->dropDatabases(...));

        try {
            $packages = Packages::discover($this->root);
            $this->base ??= $this->defaultBase();
            $targets = $this->targets($packages);

            $this->say(sprintf('Base %s · targets: %s', substr($this->base, 0, 10), $targets === [] ? 'none' : implode(', ', $targets)));

            $this->runParallelSteps($targets);
        } catch (CiException $e) {
            fwrite(STDERR, $e->getMessage()."\n");

            return 2;
        }

        if (in_array('coverage', $this->steps, true) || in_array('mutation', $this->steps, true)) {
            $this->coverage($targets);
        }

        if (in_array('mutation', $this->steps, true)) {
            $this->mutation($targets);
        }

        if (in_array('dusk', $this->steps, true)) {
            $this->dusk();
        }

        return $this->report();
    }

    /**
     * @param  list<string>  $argv
     */
    private function parse(array $argv): void
    {
        foreach ($argv as $arg) {
            match (true) {
                $arg === '-h', $arg === '--help' => $this->steps = ['help'],
                $arg === '--changed' => $this->changed = true,
                $arg === '--dusk' => $this->withDusk = true,
                $arg === '--all-lines' => $this->allLines = true,
                str_starts_with($arg, '--db=') => $this->dbs = $this->list($arg, ['sqlite', 'mysql', 'pgsql']),
                str_starts_with($arg, '--package=') => $this->only = $this->list($arg),
                str_starts_with($arg, '--base=') => $this->base = substr($arg, 7),
                str_starts_with($arg, '--jobs=') => $this->jobs = max(1, (int) substr($arg, 7)),
                in_array($arg, self::STEPS, true) => $this->steps[] = $arg,
                default => throw new CiException("Unknown argument: {$arg}"),
            };
        }

        if ($this->steps === ['help']) {
            return;
        }

        if ($this->steps === []) {
            $this->steps = self::DEFAULT_STEPS;
        }

        if ($this->withDusk && ! in_array('dusk', $this->steps, true)) {
            $this->steps[] = 'dusk';
        }
    }

    /**
     * @param  list<string>|null  $allowed
     * @return list<string>
     */
    private function list(string $arg, ?array $allowed = null): array
    {
        $values = array_values(array_filter(explode(',', substr($arg, (int) strpos($arg, '=') + 1))));

        foreach ($values as $value) {
            if ($allowed !== null && ! in_array($value, $allowed, true)) {
                throw new CiException("Unknown value {$value} in {$arg}");
            }
        }

        return $values;
    }

    private function defaultBase(): string
    {
        $base = trim($this->git(['merge-base', 'HEAD', 'origin/main']));

        return $base !== '' ? $base : trim($this->git(['rev-parse', 'HEAD']));
    }

    /**
     * @return list<string>
     */
    private function targets(Packages $packages): array
    {
        $targets = $this->changed ? $packages->affectedBy($this->changedFiles()) : $packages->all();

        if ($this->only !== null) {
            $unknown = array_diff($this->only, $packages->all());

            if ($unknown !== []) {
                throw new CiException('Unknown package: '.implode(', ', $unknown));
            }

            $targets = array_values(array_intersect($targets, $this->only));
        }

        return $targets;
    }

    /**
     * Files changed since the base, committed or not, and new files.
     *
     * @return list<string>
     */
    private function changedFiles(): array
    {
        $files = [
            ...explode("\n", $this->git(['diff', '--name-only', (string) $this->base])),
            ...explode("\n", $this->git(['ls-files', '--others', '--exclude-standard'])),
        ];

        return array_values(array_unique(array_filter(array_map('trim', $files))));
    }

    /**
     * Added and rewritten lines of the PHP source since the base.
     *
     * @return array<string, list<int>>
     */
    private function changedLines(): array
    {
        $lines = Diff::addedLines($this->git(['diff', '--unified=0', '--no-color', '--diff-filter=AM', (string) $this->base, '--', 'app', 'packages']));

        foreach (explode("\n", $this->git(['ls-files', '--others', '--exclude-standard', '--', 'app', 'packages'])) as $file) {
            if ($file !== '' && is_file($this->root.'/'.$file)) {
                $lines[$file] = range(1, max(1, count(file($this->root.'/'.$file) ?: [])));
            }
        }

        return array_filter($lines, static fn (string $file): bool => Packages::targetOf($file) !== null, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Lint, PHPStan and the PHPUnit legs, side by side.
     *
     * @param  list<string>  $targets
     */
    private function runParallelSteps(array $targets): void
    {
        $jobs = [];

        if (in_array('lint', $this->steps, true)) {
            $jobs[] = ['lint', 'pint', [PHP_BINARY, 'vendor/bin/pint', '--test'], ['XDEBUG_MODE' => 'off']];
            $jobs[] = ['lint', 'copyright header', [PHP_BINARY, 'scripts/copyright-header.php'], ['XDEBUG_MODE' => 'off']];
        }

        if (in_array('static', $this->steps, true)) {
            $jobs[] = ['static', 'phpstan', [PHP_BINARY, '-d', 'memory_limit=2G', 'vendor/bin/phpstan', 'analyse', '--no-progress'], ['XDEBUG_MODE' => 'off']];
        }

        if (in_array('tests', $this->steps, true)) {
            foreach ($this->testDatabases() as $db) {
                // With coverage on, the SQLite leg is the coverage run.
                if ($db === 'sqlite' && in_array('coverage', $this->steps, true)) {
                    continue;
                }

                foreach ($targets as $target) {
                    $jobs[] = ["tests {$db}", $target, [PHP_BINARY, 'vendor/bin/phpunit', '--configuration', $this->phpunitConfig($target, $db)], ['XDEBUG_MODE' => 'off', ...$this->databaseEnv($db, $target)]];
                }
            }
        }

        $this->pool($jobs);
    }

    /**
     * @return list<string>
     */
    private function testDatabases(): array
    {
        if ($this->dbs !== null) {
            foreach (array_diff($this->dbs, ['sqlite']) as $driver) {
                if (! $this->server($driver, true)) {
                    throw new CiException("No {$driver} server on 127.0.0.1:{$this->port($driver)} and no Docker to start one.");
                }
            }

            return $this->dbs;
        }

        $dbs = ['sqlite'];

        foreach (['mysql', 'pgsql'] as $driver) {
            if ($this->server($driver, false)) {
                $dbs[] = $driver;
            } else {
                $this->notes[] = "{$driver}: skipped, no server and no Docker. GitHub Actions still runs it.";
            }
        }

        return $dbs;
    }

    /**
     * @param  list<string>  $targets
     */
    private function coverage(array $targets): void
    {
        $driver = extension_loaded('pcov') ? ['-d', 'pcov.enabled=1', '-d', 'pcov.directory='.$this->root] : [];

        if (! $this->coverageDriver()) {
            if (getenv('CI') !== false) {
                $this->results[] = ['coverage', 'no coverage driver: install PCOV or Xdebug', false, 0.0];
            } else {
                $this->notes[] = 'coverage and mutation skipped: install PCOV or Xdebug to run them here; CI runs them';
            }

            return;
        }

        $jobs = [];

        foreach ($targets as $target) {
            $dir = "{$this->build}/coverage/{$target}";
            $this->clean($dir);
            $jobs[] = ['coverage', $target, [PHP_BINARY, ...$driver, 'vendor/bin/phpunit', '--configuration', $this->phpunitConfig($target, 'coverage'),
                '--coverage-clover', "{$dir}/clover.xml", '--coverage-xml', "{$dir}/coverage-xml", '--log-junit', "{$dir}/junit.xml"],
                ['XDEBUG_MODE' => 'coverage', ...$this->databaseEnv('sqlite', $target)]];
        }

        $this->pool($jobs);

        if (! in_array('coverage', $this->steps, true)) {
            return;
        }

        $changed = $this->changedLines();
        $baseline = new Baseline($this->root.'/.ci/coverage');
        $rows = [];

        foreach ($targets as $target) {
            $file = "{$this->build}/coverage/{$target}/clover.xml";

            if (! is_file($file)) {
                continue;
            }

            $clover = Clover::fromXml((string) file_get_contents($file), $this->root);
            $mine = array_filter($changed, static fn (string $path): bool => Packages::targetOf($path) === $target, ARRAY_FILTER_USE_KEY);
            $totals = $clover->totals();
            $missed = $clover->uncovered($mine);
            $executable = $clover->executable($mine);
            $missedCount = array_sum(array_map('count', $missed));
            $stored = $baseline->get($target);
            $uncovered = $totals['total'] - $totals['covered'];
            $verdict = $baseline->compare($target, $totals['percent'], $uncovered);

            if ($verdict === 'fell') {
                $this->results[] = ['coverage', sprintf('%s: %d lines not covered, %d before (%.2f%% of lines, %.2f%% before)', $target, $uncovered, $stored['uncovered'] ?? 0, $totals['percent'], $stored['percent'] ?? 0), false, 0.0];
            } elseif ($verdict !== 'same' && getenv('CI') === false) {
                $baseline->set($target, $totals['percent'], $uncovered);
                $this->notes[] = sprintf('%s: baseline %s → %.2f%%, commit .ci/coverage/%s', $target, $stored === null ? 'none' : sprintf('%.2f%%', $stored['percent']), $totals['percent'], $target);
            }

            foreach ($missed as $path => $numbers) {
                $this->results[] = ['coverage', "{$path}: lines ".self::ranges($numbers).' changed and not covered', false, 0.0];

                if (getenv('GITHUB_ACTIONS') === 'true') {
                    echo "::error file={$path},line={$numbers[0]}::Changed lines ".self::ranges($numbers)." are not covered by a test\n";
                }
            }

            $rows[] = [$target, $totals['percent'], $stored['percent'] ?? null, $totals['covered'], $totals['total'], $executable, $missedCount];
        }

        $this->coverageSummary($rows);
    }

    /**
     * @param  list<array{string, float, ?float, int, int, int, int}>  $rows
     */
    private function coverageSummary(array $rows): void
    {
        $markdown = "### Coverage\n\n| Package | Lines | Baseline | Covered / total | Changed lines | Not covered |\n|---|--:|--:|--:|--:|--:|\n";
        $this->say(sprintf("\n%-10s %8s %9s %15s %8s %8s", 'package', 'lines', 'baseline', 'covered/total', 'changed', 'missed'));

        foreach ($rows as [$target, $percent, $stored, $covered, $total, $executable, $missed]) {
            $base = $stored === null ? '—' : sprintf('%.2f%%', $stored);
            $this->say(sprintf('%-10s %7.2f%% %9s %15s %8d %8d', $target, $percent, $base, "{$covered}/{$total}", $executable, $missed));
            $markdown .= sprintf("| %s | %.2f%% | %s | %d / %d | %d | %s |\n", $target, $percent, $base, $covered, $total, $executable, $missed === 0 ? '0' : "**{$missed}**");
        }

        $this->summary($markdown);
    }

    /**
     * @param  list<string>  $targets
     */
    private function mutation(array $targets): void
    {
        if (! $this->coverageDriver() && getenv('CI') === false) {
            return;
        }

        $changed = $this->changedLines();
        $rows = [];

        foreach ($targets as $target) {
            if (! $this->allLines && ! array_filter(array_keys($changed), static fn (string $path): bool => Packages::targetOf($path) === $target)) {
                continue;
            }

            if (! is_file("{$this->build}/coverage/{$target}/junit.xml")) {
                $this->results[] = ['mutation', "{$target}: no coverage to mutate against", false, 0.0];

                continue;
            }

            $config = $this->infectionConfig($target);
            $command = [PHP_BINARY, 'vendor/bin/infection', '--configuration='.$config, '--coverage='."{$this->build}/coverage/{$target}",
                '--skip-initial-tests', '--threads='.$this->jobs, '--no-progress', '--no-interaction', '--show-mutations=50', '--ignore-msi-with-no-mutations',
                ...($this->allLines ? [] : ['--git-diff-lines', '--git-diff-base='.$this->base, '--min-msi='.self::MIN_MSI])];

            if (getenv('GITHUB_ACTIONS') === 'true') {
                $command[] = '--logger-github';
            }

            // PAO_DISABLE: laravel/pao turns PHPUnit's output into JSON when an
            // AI agent runs it, Infection then cannot tell a passing run, and
            // every mutant would count as killed.
            $this->pool([['mutation', $target, $command, ['XDEBUG_MODE' => 'off', 'PAO_DISABLE' => '1', ...$this->databaseEnv('sqlite', $target)]]], 1);
            $summary = "{$this->build}/infection/{$target}/summary.json";
            $stats = is_file($summary) ? json_decode((string) file_get_contents($summary), true) : null;

            if (is_array($stats) && isset($stats['stats'])) {
                $rows[] = [$target, $stats['stats']['msi'] ?? 0, $stats['stats']['totalMutantsCount'] ?? 0, $stats['stats']['escapedCount'] ?? 0, $stats['stats']['notCoveredCount'] ?? 0];
            }
        }

        if ($rows === []) {
            $this->notes[] = 'mutation: no changed PHP source, nothing to mutate.';

            return;
        }

        $markdown = '### Mutation score of the changed lines (min '.self::MIN_MSI."%)\n\n| Package | MSI | Mutants | Escaped | Not covered |\n|---|--:|--:|--:|--:|\n";

        foreach ($rows as [$target, $msi, $total, $escaped, $uncovered]) {
            $this->say(sprintf('mutation %-10s MSI %6.2f%%  mutants %d  escaped %d  not covered %d', $target, $msi, $total, $escaped, $uncovered));
            $markdown .= sprintf("| %s | %.2f%% | %d | %d | %d |\n", $target, $msi, $total, $escaped, $uncovered);
        }

        $this->summary($markdown);
    }

    private function dusk(): void
    {
        if (! is_file($this->root.'/.env.dusk.local')) {
            $this->results[] = ['dusk', '.env.dusk.local is missing: cp .env.dusk.example .env.dusk.local && php artisan key:generate --env=dusk.local', false, 0.0];

            return;
        }

        // Ports of its own, so two checkouts can run Dusk at the same time.
        $app = $this->freePort();
        $driver = $this->freePort();
        $url = "http://127.0.0.1:{$app}";
        $env = ['APP_URL' => $url, 'DUSK_DRIVER_URL' => "http://127.0.0.1:{$driver}", 'DUSK_CHROMEDRIVER_PORT' => (string) $driver, 'XDEBUG_MODE' => 'off'];

        // Dusk swaps .env for .env.dusk.<environment> and reloads it over the
        // process environment, so the ports go into a copy of .env.dusk.local
        // that `--env=ci` picks.
        $file = $this->root.'/.env.dusk.ci';
        $lines = preg_grep('/^\s*('.implode('|', array_keys($env)).')\s*=/', file($this->root.'/.env.dusk.local') ?: [], PREG_GREP_INVERT) ?: [];
        file_put_contents($file, rtrim(implode('', $lines))."\n".implode('', array_map(fn (string $key, string $value): string => "{$key}={$value}\n", array_keys($env), $env)));

        // PHP's own server, as `artisan serve` starts it, but with APP_URL: the
        // admin builds its links from it, and serve would not pass it on.
        $server = proc_open([PHP_BINARY, '-S', "127.0.0.1:{$app}", $this->root.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, $this->root.'/public', [...getenv(), ...$env]);

        try {
            $this->pool([['dusk', 'php artisan dusk', [PHP_BINARY, 'artisan', 'dusk', '--env=ci'], $env]], 1);
        } finally {
            if (is_resource($server)) {
                proc_terminate($server);
            }

            @unlink($file);
        }
    }

    /**
     * Runs commands a few at a time and records how each ended.
     *
     * @param  list<array{string, string, list<string>, array<string, string>}>  $jobs
     */
    private function pool(array $jobs, ?int $size = null): void
    {
        $size ??= $this->jobs;
        $running = [];
        $logs = "{$this->build}/logs";

        if (! is_dir($logs)) {
            mkdir($logs, 0777, true);
        }

        while ($jobs !== [] || $running !== []) {
            while ($jobs !== [] && count($running) < $size) {
                [$step, $label, $command, $env] = array_shift($jobs);
                $log = $logs.'/'.preg_replace('/[^a-z0-9]+/i', '-', "{$step}-{$label}").'.log';
                file_put_contents($log, '');
                $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, $this->root, [...getenv(), ...$env]);
                $running[] = [$step, $label, $process, microtime(true), $log];
            }

            usleep(100_000);

            foreach ($running as $key => [$step, $label, $process, $started, $log]) {
                $status = proc_get_status($process);

                if ($status['running']) {
                    continue;
                }

                $passed = $status['exitcode'] === 0;
                $seconds = microtime(true) - $started;
                $this->results[] = [$step, $label, $passed, $seconds];
                $this->say(sprintf('%s %-14s %-22s %5.1fs', $passed ? 'ok  ' : 'FAIL', $step, $label, $seconds));

                if (! $passed) {
                    $this->say('     '.str_replace("\n", "\n     ", rtrim($this->tail($log, 40)))."\n     full log: {$log}");
                }

                unset($running[$key]);
            }
        }
    }

    /**
     * A PHPUnit configuration for one target and leg: the root one with only
     * the target's tests and source, so its coverage is measured by its own
     * tests, and a Testbench application of its own, so targets running side
     * by side do not share storage/.
     */
    private function phpunitConfig(string $target, string $leg): string
    {
        $dir = "{$this->build}/phpunit/{$target}-{$leg}";
        $file = "{$dir}/phpunit.xml";
        $this->clean($dir);
        shell_exec(sprintf('cp -R %s %s', escapeshellarg($this->root.'/vendor/orchestra/testbench-core/laravel'), escapeshellarg("{$dir}/laravel")));

        // Without what earlier runs left in it: a copied compiled view is newer
        // than its source, so Blade would keep serving the old template.
        foreach (['storage/framework/views', 'storage/framework/cache/data', 'storage/framework/sessions'] as $state) {
            shell_exec(sprintf('rm -rf %1$s && mkdir -p %1$s', escapeshellarg("{$dir}/laravel/{$state}")));
        }

        $document = new DOMDocument;
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        $document->load($this->root.'/phpunit.xml');
        $phpunit = $document->documentElement;
        assert($phpunit instanceof DOMElement);
        $phpunit->setAttribute('bootstrap', $this->root.'/vendor/autoload.php');
        $phpunit->setAttribute('cacheDirectory', "{$dir}/.cache");
        $paths = Packages::paths($target);

        foreach (['testsuites', 'source'] as $name) {
            foreach (iterator_to_array($document->getElementsByTagName($name)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        $suites = $document->createElement('testsuites');
        $suite = $suites->appendChild($document->createElement('testsuite'));
        assert($suite instanceof DOMElement);
        $suite->setAttribute('name', $target);

        foreach ($paths['tests'] as $path) {
            $suite->appendChild($document->createElement('directory', "{$this->root}/{$path}"));
        }

        $source = $document->createElement('source');
        $include = $source->appendChild($document->createElement('include'));

        foreach ($paths['source'] as $path) {
            $include->appendChild($document->createElement('directory', "{$this->root}/{$path}"));
        }

        $phpunit->insertBefore($source, $phpunit->firstChild);
        $phpunit->insertBefore($suites, $phpunit->firstChild);

        foreach ($target === Packages::APP ? [] : $document->getElementsByTagName('php') as $php) {
            $env = $php->appendChild($document->createElement('env'));
            assert($env instanceof DOMElement);
            $env->setAttribute('name', 'APP_BASE_PATH');
            $env->setAttribute('value', "{$dir}/laravel");
            $env->setAttribute('force', 'true');
        }

        $document->save($file);

        return $file;
    }

    private function coverageDriver(): bool
    {
        return extension_loaded('pcov') || extension_loaded('xdebug');
    }

    private function infectionConfig(string $target): string
    {
        $dir = "{$this->build}/infection/{$target}";
        $this->clean($dir);
        file_put_contents("{$dir}/infection.json5", (string) json_encode([
            'source' => ['directories' => array_map(fn (string $path): string => "{$this->root}/{$path}", Packages::paths($target)['source'])],
            'phpUnit' => ['configDir' => "{$this->build}/phpunit/{$target}-coverage"],
            'tmpDir' => "{$dir}/tmp",
            'timeout' => 30,
            'logs' => ['text' => "{$dir}/infection.log", 'summaryJson' => "{$dir}/summary.json"],
            'mutators' => ['@default' => true],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return "{$dir}/infection.json5";
    }

    /**
     * @return array<string, string>
     */
    private function databaseEnv(string $driver, string $target): array
    {
        if ($driver === 'sqlite') {
            return ['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:'];
        }

        // One database per run and target: targets run side by side, and so
        // do other checkouts sharing the server.
        $name = strtolower(sprintf('atrium_ci_%d_%s', getmypid(), preg_replace('/\W/', '_', $target)));
        $this->connect($driver)?->exec($driver === 'mysql' ? "CREATE DATABASE `{$name}`" : "CREATE DATABASE \"{$name}\"");
        $this->databases[] = [$driver, $name];

        return ['DB_CONNECTION' => $driver, 'DB_HOST' => '127.0.0.1', 'DB_PORT' => (string) $this->port($driver),
            'DB_DATABASE' => $name, 'DB_USERNAME' => $this->user($driver), 'DB_PASSWORD' => $this->password()];
    }

    private function dropDatabases(): void
    {
        foreach ($this->databases as [$driver, $name]) {
            try {
                $this->connect($driver)?->exec($driver === 'mysql' ? "DROP DATABASE IF EXISTS `{$name}`" : "DROP DATABASE IF EXISTS \"{$name}\" WITH (FORCE)");
            } catch (Throwable) {
                // The server is gone; so is the database.
            }
        }

        $this->databases = [];
    }

    /**
     * Is a server there? Locally starts one in Docker when it is not.
     */
    private function server(string $driver, bool $required): bool
    {
        if ($this->connect($driver) instanceof PDO) {
            return true;
        }

        if (getenv('CI') !== false || trim((string) shell_exec('command -v docker')) === '' || ! $this->dockerUp()) {
            return false;
        }

        $name = "atrium-ci-{$driver}";
        $image = $driver === 'mysql' ? 'mysql:8.4' : 'postgres:17';
        $data = $driver === 'mysql' ? '/var/lib/mysql' : '/var/lib/postgresql/data';
        $internal = $driver === 'mysql' ? 3306 : 5432;
        $this->say("Starting {$name} in Docker on port {$this->port($driver)}…");
        shell_exec("docker rm -f {$name} >/dev/null 2>&1");
        $password = escapeshellarg($this->password());
        shell_exec("docker run -d --name {$name} -e MYSQL_ROOT_PASSWORD={$password} -e POSTGRES_PASSWORD={$password} -p 127.0.0.1:{$this->port($driver)}:{$internal} --tmpfs {$data} {$image} >/dev/null 2>&1");

        for ($i = 0; $i < 90; $i++) {
            if ($this->connect($driver) instanceof PDO) {
                return true;
            }

            sleep(1);
        }

        if ($required) {
            throw new CiException("{$name} did not start: docker logs {$name}");
        }

        return false;
    }

    private function dockerUp(): bool
    {
        exec('docker info >/dev/null 2>&1', $output, $code);

        return $code === 0;
    }

    private function connect(string $driver): ?PDO
    {
        $dsn = $driver === 'mysql'
            ? "mysql:host=127.0.0.1;port={$this->port($driver)}"
            : "pgsql:host=127.0.0.1;port={$this->port($driver)};dbname=postgres";

        try {
            return new PDO($dsn, $this->user($driver), $this->password(), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 2]);
        } catch (Throwable) {
            return null;
        }
    }

    private function port(string $driver): int
    {
        $value = getenv($driver === 'mysql' ? 'ATRIUM_CI_MYSQL_PORT' : 'ATRIUM_CI_PGSQL_PORT');

        return $value !== false && $value !== '' ? (int) $value : ($driver === 'mysql' ? 33061 : 54321);
    }

    private function user(string $driver): string
    {
        return $driver === 'mysql' ? 'root' : 'postgres';
    }

    private function password(): string
    {
        $value = getenv('ATRIUM_CI_DB_PASSWORD');

        return $value !== false && $value !== '' ? $value : 'password';
    }

    private function report(): int
    {
        foreach ($this->notes as $note) {
            $this->say("note: {$note}");
        }

        $failed = array_filter($this->results, static fn (array $result): bool => ! $result[2]);
        $markdown = "### bin/ci\n\n";

        foreach ($failed as [$step, $label]) {
            $markdown .= "- ❌ {$step}: {$label}\n";
        }

        $markdown .= $failed === [] ? '✅ '.count($this->results)." checks passed\n" : '';
        $this->summary($markdown);

        if ($failed === []) {
            $this->say(sprintf("\nAll %d checks passed.", count($this->results)));

            return 0;
        }

        $this->say(sprintf("\n%d of %d checks failed:", count($failed), count($this->results)));

        foreach ($failed as [$step, $label]) {
            $this->say("  {$step}: {$label}");
        }

        return 1;
    }

    /**
     * 3, 4, 5, 9 → "3-5, 9".
     *
     * @param  list<int>  $numbers
     */
    public static function ranges(array $numbers): string
    {
        sort($numbers);
        $parts = [];
        $start = $previous = null;

        foreach ([...$numbers, null] as $number) {
            if ($start !== null && $number === $previous + 1) {
                $previous = $number;

                continue;
            }

            if ($start !== null) {
                $parts[] = $start === $previous ? (string) $start : "{$start}-{$previous}";
            }

            $start = $previous = $number;
        }

        return implode(', ', $parts);
    }

    private function summary(string $markdown): void
    {
        $file = getenv('GITHUB_STEP_SUMMARY');

        if ($file !== false && $file !== '') {
            file_put_contents($file, $markdown."\n", FILE_APPEND);
        }
    }

    /**
     * @param  list<string>  $args
     */
    private function git(array $args): string
    {
        $command = 'git '.implode(' ', array_map('escapeshellarg', $args)).' 2>/dev/null';

        return (string) shell_exec('cd '.escapeshellarg($this->root).' && '.$command);
    }

    private function tail(string $file, int $lines): string
    {
        return implode("\n", array_slice(file($file, FILE_IGNORE_NEW_LINES) ?: [], -$lines));
    }

    private function clean(string $dir): void
    {
        if (is_dir($dir)) {
            shell_exec('rm -rf '.escapeshellarg($dir));
        }

        mkdir($dir, 0777, true);
    }

    private function freePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $name = $socket === false ? '' : (string) stream_socket_get_name($socket, false);

        if ($socket !== false) {
            fclose($socket);
        }

        return (int) substr($name, (int) strrpos($name, ':') + 1);
    }

    private function cpus(): int
    {
        $count = (int) trim((string) shell_exec('nproc 2>/dev/null || sysctl -n hw.ncpu 2>/dev/null'));

        return $count > 0 ? $count : 2;
    }

    private function say(string $line): void
    {
        echo $line."\n";
    }
}
