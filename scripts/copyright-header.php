<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The copyright header in the files Pint does not format: Blade views, Vue,
 * JavaScript, CSS, shell scripts, Dockerfiles and YAML. PHP files get it from
 * Pint's header_comment rule (pint.json).
 *
 *   php scripts/copyright-header.php        lists the files without it, exits 1 if any
 *   php scripts/copyright-header.php --fix  adds it to them
 *
 * Built bundles (dist), vendor and node_modules are skipped; JSON, XML,
 * Markdown and SVG have no header.
 */

const LINES = [
    'This file is part of Atrium (https://github.com/AlveBy/atrium).',
    'Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.',
];

const SKIP = '#(^|/)(vendor|node_modules|dist|build)/|\.min\.(js|css)$#';

function kind(string $path): ?string
{
    return match (true) {
        str_ends_with($path, '.blade.php') => 'blade',
        str_ends_with($path, '.vue') => 'vue',
        (bool) preg_match('/\.(js|mjs|cjs|ts|css)$/', $path) => 'block',
        (bool) preg_match('/\.(sh|ya?ml)$/', $path), basename($path) === 'Dockerfile' => 'hash',
        default => null,
    };
}

function withHeader(string $kind, string $code): string
{
    $block = "/*\n * ".implode("\n * ", LINES)."\n */\n";

    switch ($kind) {
        case 'blade':
            return "{{--\n    ".implode("\n    ", LINES)."\n--}}\n".$code;
        case 'block':
            return $block."\n".$code;
        case 'vue':
            if (preg_match('/<script\b[^>]*>\n/', $code, $tag, PREG_OFFSET_CAPTURE)) {
                $at = $tag[0][1] + strlen($tag[0][0]);

                return substr($code, 0, $at).$block."\n".substr($code, $at);
            }

            return "<!--\n  ".implode("\n  ", LINES)."\n-->\n".$code;
        default:
            $hash = '# '.implode("\n# ", LINES)."\n\n";
            // A shebang or a Dockerfile parser directive must stay on the first line.
            if (preg_match('/^(#!|# *(syntax|escape|check)=)/', $code)) {
                $end = strpos($code, "\n") + 1;

                return substr($code, 0, $end).$hash.substr($code, $end);
            }

            return $hash.$code;
    }
}

$root = dirname(__DIR__);
$fix = in_array('--fix', $argv, true);

exec('git -C '.escapeshellarg($root).' ls-files --cached --others --exclude-standard', $files, $status);
if ($status !== 0) {
    fwrite(STDERR, "git ls-files failed\n");
    exit(2);
}

$missing = [];
foreach (array_unique($files) as $path) {
    $kind = kind($path);
    if ($kind === null || preg_match(SKIP, $path) || ! is_file("{$root}/{$path}")) {
        continue;
    }

    $code = (string) file_get_contents("{$root}/{$path}");
    if (str_contains($code, LINES[0])) {
        continue;
    }

    $missing[] = $path;
    if ($fix) {
        file_put_contents("{$root}/{$path}", withHeader($kind, $code));
    }
}

if ($missing === []) {
    exit(0);
}

echo ($fix ? 'Added the copyright header to:' : 'No copyright header in:')."\n  ".implode("\n  ", $missing)."\n";
if (! $fix) {
    echo "Run: php scripts/copyright-header.php --fix\n";
}

exit($fix ? 0 : 1);
