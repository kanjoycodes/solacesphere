<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
session_start();

if (empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'user' => null]);
    exit;
}

$uid = (int)$_SESSION['user_id'];
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT id, name, display_name, email, role FROM users WHERE id = :id');
    $stmt->execute([':id' => $uid]);
    $user = $stmt->fetch();
    if (!$user) {
        echo json_encode(['ok' => false, 'user' => null]);
        exit;
    }
    $displayName = $user['display_name'] ?: explode(' ', $user['name'])[0];
    echo json_encode(['ok' => true, 'user' => ['id' => $user['id'], 'email' => $user['email'], 'displayName' => $displayName, 'role' => $user['role']]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}

