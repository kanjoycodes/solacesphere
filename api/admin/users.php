<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDbConnection();

    if ($method === 'GET') {
        $search = trim((string)($_GET['search'] ?? ''));
        $role = trim((string)($_GET['role'] ?? ''));

        $sql = 'SELECT id, name, display_name, email, role, status, created_at FROM users WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (name LIKE :search OR email LIKE :search2)';
            $params[':search'] = "%$search%";
            $params[':search2'] = "%$search%";
        }

        if ($role !== '' && in_array($role, ['patient', 'professional', 'admin'], true)) {
            $sql .= ' AND role = :role';
            $params[':role'] = $role;
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        sendJson(['ok' => true, 'users' => $users]);
    }

    if ($method === 'POST') {
        $data = readJsonBody();
        $userId = (int)($data['user_id'] ?? 0);
        $newRole = trim((string)($data['role'] ?? ''));
        $newStatus = trim((string)($data['status'] ?? ''));

        if ($userId <= 0) {
            sendJson(['error' => 'Invalid user ID.'], 400);
        }

        if ($newRole !== '' && in_array($newRole, ['patient', 'professional', 'admin'], true)) {
            $stmt = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
            $stmt->execute([':role' => $newRole, ':id' => $userId]);
        }

        if ($newStatus !== '' && in_array($newStatus, ['pending', 'active', 'deactivated'], true)) {
            $stmt = $pdo->prepare('UPDATE users SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $newStatus, ':id' => $userId]);
        }

        sendJson(['ok' => true, 'message' => 'User updated.']);
    }

    sendJson(['error' => 'Method not allowed.'], 405);
} catch (PDOException $e) {
    sendJson(['error' => 'Database error', 'message' => $e->getMessage()], 500);
}
