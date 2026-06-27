<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$body = readJsonBody();
$signals = is_array($body['signals'] ?? null) ? $body['signals'] : [];

sendJson([
    'prompts' => derivePrompts($signals)
]);
