<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$body = readJsonBody();
$message = trim((string)($body['message'] ?? ''));
$signals = is_array($body['signals'] ?? null) ? $body['signals'] : [];
$history = is_array($body['history'] ?? null) ? $body['history'] : [];

if ($message === '') {
    sendJson(['error' => 'Message is required.'], 400);
}

$ipHash = getClientIpHash();
$state = getClientState($ipHash);
$flags = classifyInput($message);

$score = (float)$state['score'];
if (!empty($flags['injection']) || !empty($flags['harm'])) {
    $score += 2.0;
} else {
    $score += 0.2;
}
updateClientState($ipHash, $score);

$strictMode = $score >= 8.0 || !empty($flags['injection']);
$softDelayMs = $score >= 12.0 ? 900 : 0;

if (!empty($flags['crisis'])) {
    logEvent(['ipHash' => $ipHash, 'type' => 'crisis', 'score' => $score]);
    sendJson([
        'reply' => buildCrisisResponse(),
        'prompts' => derivePrompts($signals),
        'safety' => ['level' => 'crisis', 'strictMode' => $strictMode, 'softDelayMs' => $softDelayMs]
    ]);
}

if (!empty($flags['clinical']) && $strictMode) {
    logEvent(['ipHash' => $ipHash, 'type' => 'clinical_boundary', 'score' => $score]);
    sendJson([
        'reply' => buildBoundaryResponse(),
        'prompts' => derivePrompts($signals),
        'safety' => ['level' => 'high', 'strictMode' => $strictMode, 'softDelayMs' => $softDelayMs]
    ]);
}

if ($softDelayMs > 0) {
    usleep($softDelayMs * 1000);
}

$rawReply = queryGeminiChat($message, $history, $strictMode);
$reply = safeOutputFilter($rawReply);

logEvent([
    'ipHash' => $ipHash,
    'type' => 'chat',
    'score' => $score,
    'level' => (string)($flags['level'] ?? 'normal')
]);

sendJson([
    'reply' => $reply,
    'prompts' => derivePrompts($signals),
    'safety' => ['level' => (string)($flags['level'] ?? 'normal'), 'strictMode' => $strictMode, 'softDelayMs' => $softDelayMs]
]);
