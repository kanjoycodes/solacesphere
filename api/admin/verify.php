<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['error' => 'Method not allowed.'], 405);
}

$data = readJsonBody();
$userId = (int)($data['user_id'] ?? 0);
$action = trim((string)($data['action'] ?? ''));

if ($userId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    sendJson(['error' => 'Invalid request.'], 400);
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT id, role, status FROM users WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || $user['role'] !== 'professional') {
        sendJson(['error' => 'User not found or not a professional.'], 404);
    }

    if ($action === 'approve') {
        $stmt = $pdo->prepare('UPDATE users SET status = :status WHERE id = :id');
        $stmt->execute([':status' => 'active', ':id' => $userId]);
        sendJson(['ok' => true, 'message' => 'Professional approved.']);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET status = :status WHERE id = :id');
        $stmt->execute([':status' => 'deactivated', ':id' => $userId]);
        sendJson(['ok' => true, 'message' => 'Professional rejected.']);
    }
} catch (PDOException $e) {
    sendJson(['error' => 'Database error', 'message' => $e->getMessage()], 500);
}
