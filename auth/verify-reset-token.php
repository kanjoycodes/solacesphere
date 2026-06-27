<?php
header('Content-Type: application/json');

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if (empty($token) || empty($email)) {
    echo json_encode(['valid' => false, 'message' => 'Token and email are required.']);
    exit;
}

$resetFile = __DIR__ . '/reset_tokens.json';

if (!file_exists($resetFile)) {
    echo json_encode(['valid' => false, 'message' => 'No reset tokens found.']);
    exit;
}

$resetTokens = json_decode(file_get_contents($resetFile), true) ?: [];

// Find matching token
foreach ($resetTokens as $resetData) {
    if ($resetData['token'] === $token && $resetData['email'] === $email) {
        // Check if token hasn't expired
        if ($resetData['expiry'] > time()) {
            echo json_encode(['valid' => true, 'message' => 'Token is valid.']);
            exit;
        } else {
            echo json_encode(['valid' => false, 'message' => 'This reset link has expired. Please request a new one.']);
            exit;
        }
    }
}

echo json_encode(['valid' => false, 'message' => 'Invalid reset token.']);
?>
