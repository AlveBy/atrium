<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * A stand-in for Ollama's chat API, for the browser tests of the AI helper:
 * `php -S 127.0.0.1:PORT tests/Browser/Fakes/ollama.php`. The app under test
 * talks to it as to a real provider, so nothing leaves the machine.
 *
 * It answers with the question it was asked and how many questions the
 * conversation holds; a question with "broken" in it gets an error. An
 * "Improve the text" request gets its text back with the typos "veiw" and
 * "teh" corrected: what comes back shows which piece was sent.
 */

$body = json_decode((string) file_get_contents('php://input'), true) ?: [];
$questions = array_values(array_filter($body['messages'] ?? [], static fn (array $message): bool => ($message['role'] ?? '') === 'user'));
$question = (string) (end($questions)['content'] ?? '');

header('Content-Type: application/json');

if (str_contains($question, 'broken')) {
    http_response_code(500);
    echo json_encode(['error' => 'The model is down.']);

    return;
}

$improved = str_starts_with($question, 'Improve the text of the field')
    ? str_replace(['veiw', 'teh'], ['view', 'the'], (string) substr($question, (int) strpos($question, "Text:\n") + 6))
    : null;

echo json_encode([
    'model' => 'fake',
    'message' => [
        'role' => 'assistant',
        'content' => $improved ?? implode("\n", [
            '**Question '.count($questions).'**: '.$question,
            '- [Articles](/admin/articles)',
            '- [Elsewhere](https://example.com/)',
        ]),
    ],
    'done' => true,
    'done_reason' => 'stop',
    'prompt_eval_count' => 12,
    'eval_count' => 34,
]);
