<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Feature;

use GuzzleHttp\Client;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * A copy of this application whose web root is called as the hosting wants
 * it (`www`, `public_html`): installed with artisan and served by PHP's own
 * server from that folder, the way a shared hosting serves it.
 */
final class WebRootTest extends TestCase
{
    private string $project;

    /** @var resource|null */
    private $server = null;

    private int $port = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $root = dirname(__DIR__, 2);
        $this->project = sys_get_temp_dir().'/atrium-web-root-'.getmypid().'-'.uniqid();
        $files = new Filesystem;

        foreach (['bootstrap/cache', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $dir) {
            $files->ensureDirectoryExists($this->project.'/'.$dir);
        }
        foreach (['artisan', 'composer.json', 'bootstrap/app.php', 'bootstrap/providers.php'] as $file) {
            copy($root.'/'.$file, $this->project.'/'.$file);
        }
        // What the install writes into is a copy; the rest is only read.
        foreach (['config', 'database', 'routes'] as $dir) {
            $files->copyDirectory($root.'/'.$dir, $this->project.'/'.$dir);
        }
        foreach (['app', 'resources', 'vendor'] as $dir) {
            symlink($root.'/'.$dir, $this->project.'/'.$dir);
        }
        touch($this->project.'/database/web-root.sqlite');
    }

    protected function tearDown(): void
    {
        if ($this->server !== null) {
            proc_terminate($this->server);
            proc_close($this->server);
        }
        (new Filesystem)->deleteDirectory($this->project);

        parent::tearDown();
    }

    /**
     * @return array<string,array{string}>
     */
    public static function webRoots(): array
    {
        return [
            'public, as in Laravel' => ['public'],
            'www' => ['www'],
            'public_html' => ['public_html'],
            'a name of the site' => ['example.com'],
        ];
    }

    #[DataProvider('webRoots')]
    public function test_the_install_writes_into_the_web_root_whatever_it_is_called(string $name): void
    {
        $this->webRoot($name);

        $output = $this->install();

        $this->assertStringContainsString("Linking {$name}/storage", $output);
        $this->assertTrue(is_link($this->project."/{$name}/storage"), $output);
        $this->assertSame(realpath($this->project.'/storage/app/public'), realpath($this->project."/{$name}/storage"));
        $this->assertFileExists($this->project."/{$name}/vendor/atrium/manifest.json");
        $this->assertDirectoryExists($this->project."/{$name}/vendor/atrium-theme/fonts");
        $this->assertSame([$name], $this->webRootsOnDisk(), 'Nothing is written into a folder the web server does not serve.');
    }

    #[DataProvider('webRoots')]
    public function test_the_site_the_admin_their_assets_and_uploaded_files_are_served(string $name): void
    {
        $this->webRoot($name);
        $this->install();
        mkdir($this->project.'/storage/app/public/uploads');
        file_put_contents($this->project.'/storage/app/public/uploads/note.txt', 'uploaded in the admin');
        $this->serve($name);

        $home = $this->get('/');
        $this->assertSame(200, $home['status'], $home['body']);

        $admin = $this->get('/admin/login');
        $this->assertSame(200, $admin['status'], $admin['body']);
        $this->assertMatchesRegularExpression('#/vendor/atrium/assets/app-[^"]+\.js#', $admin['body']);

        preg_match('#/vendor/atrium/assets/app-[^"]+\.js#', $admin['body'], $script);
        $asset = $this->get($script[0]);
        $this->assertSame(200, $asset['status']);
        $this->assertSame(md5_file($this->project."/{$name}".$script[0]), md5($asset['body']), 'The script is the published one.');

        $this->assertSame(['status' => 200, 'body' => 'uploaded in the admin'], $this->get('/storage/uploads/note.txt'));
        // No such file behind the link: Laravel's route of the private disk
        // answers, and without a signature it refuses.
        $this->assertSame(403, $this->get('/storage/uploads/missing.txt')['status']);
        $this->assertSame(404, $this->get('/vendor/atrium/assets/missing.js')['status']);
    }

    public function test_with_two_web_roots_the_console_does_not_guess(): void
    {
        $this->webRoot('www');
        $this->webRoot('public_html');

        $output = $this->install();

        $this->assertStringContainsString('Linking public/storage', $output);
        $this->assertFileDoesNotExist($this->project.'/www/storage');
        $this->assertFileDoesNotExist($this->project.'/public_html/storage');
        $this->assertFileDoesNotExist($this->project.'/www/vendor/atrium/manifest.json');
        $this->assertFileDoesNotExist($this->project.'/public_html/vendor/atrium/manifest.json');
    }

    public function test_a_request_knows_its_web_root_when_the_console_cannot_tell(): void
    {
        $this->webRoot('www');
        $this->webRoot('public_html');
        $this->install();
        rename($this->project.'/public/vendor', $this->project.'/www/vendor');
        $this->serve('www');

        $admin = $this->get('/admin/login');

        $this->assertSame(200, $admin['status'], $admin['body']);
        $this->assertMatchesRegularExpression('#/vendor/atrium/assets/app-[^"]+\.js#', $admin['body']);
    }

    /**
     * The folders of the copy that hold an index.php, the storage link or
     * the published admin bundle.
     *
     * @return list<string>
     */
    private function webRootsOnDisk(): array
    {
        $found = [];

        foreach (glob($this->project.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (is_file($dir.'/index.php') || is_link($dir.'/storage') || is_dir($dir.'/vendor/atrium')) {
                $found[] = basename($dir);
            }
        }

        return $found;
    }

    private function webRoot(string $name): void
    {
        $from = dirname(__DIR__, 2).'/public';
        mkdir($this->project.'/'.$name);

        foreach (['index.php', '.htaccess', 'favicon.ico'] as $file) {
            copy($from.'/'.$file, $this->project.'/'.$name.'/'.$file);
        }
    }

    /**
     * The settings of the copy, passed as the environment: the test run's
     * own (an in-memory database, APP_ENV=testing) must not reach it.
     *
     * @return array<string,string>
     */
    private function environment(int $port = 80): array
    {
        return [
            'PATH' => (string) getenv('PATH'),
            'HOME' => (string) getenv('HOME'),
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'true',
            'APP_KEY' => 'base64:'.base64_encode(str_repeat('k', 32)),
            'APP_URL' => "http://127.0.0.1:{$port}",
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $this->project.'/database/web-root.sqlite',
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array',
            'PAO_DISABLE' => '1',
        ];
    }

    private function install(): string
    {
        $process = proc_open(
            [PHP_BINARY, 'artisan', 'atrium:install', '--no-interaction', '--name=Admin', '--email=admin@example.com', '--password=a long enough password'],
            [1 => ['pipe', 'w'], 2 => ['redirect', 1]],
            $pipes,
            $this->project,
            $this->environment(),
        );
        $this->assertIsResource($process);
        $output = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $this->assertSame(0, proc_close($process), $output);

        return $output;
    }

    /**
     * PHP's own server in the web root, on a free port, as `artisan serve`
     * runs it.
     */
    private function serve(string $name): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertIsResource($socket);
        $port = (int) substr((string) strrchr((string) stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        $server = proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:{$port}", dirname(__DIR__, 2).'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
            $this->project.'/'.$name,
            $this->environment($port),
        );
        $this->assertIsResource($server);
        $this->server = $server;
        $this->port = $port;

        for ($try = 0; $try < 200; $try++) {
            $connection = @fsockopen('127.0.0.1', $port, timeout: 0.1);

            if ($connection !== false) {
                fclose($connection);

                return;
            }
            usleep(25_000);
        }

        throw new RuntimeException("PHP's server did not start on port {$port}.");
    }

    /**
     * @return array{status:int,body:string}
     */
    private function get(string $path): array
    {
        $response = (new Client(['http_errors' => false, 'timeout' => 30]))->get("http://127.0.0.1:{$this->port}{$path}");

        return ['status' => $response->getStatusCode(), 'body' => (string) $response->getBody()];
    }
}
