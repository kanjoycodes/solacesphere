<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$data = readJsonBody();
$name = trim((string)($data['name'] ?? ''));
$email = trim((string)($data['email'] ?? ''));
$password = (string)($data['password'] ?? '');
$inviteToken = trim((string)($data['invite_token'] ?? ''));

if ($name === '' || $email === '' || $password === '') {
    sendJson(['error' => 'Missing required fields.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJson(['error' => 'Invalid email address.'], 400);
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        sendJson(['error' => 'A user with that email already exists.'], 409);
    }

    // Default role is patient; only allow professional/admin when a valid invite token exists
    $role = 'patient';
    if ($inviteToken !== '') {
        $t = $pdo->prepare('SELECT id, email, role, used FROM invite_tokens WHERE token = :token LIMIT 1');
        $t->execute([':token' => $inviteToken]);
        $invite = $t->fetch();
        if ($invite && !$invite['used'] && ($invite['email'] === $email || $invite['email'] === '')) {
            if (in_array($invite['role'], ['professional', 'admin'], true)) {
                $role = $invite['role'];
            }
            // mark invite used
            $mark = $pdo->prepare('UPDATE invite_tokens SET used = 1 WHERE id = :id');
            $mark->execute([':id' => $invite['id']]);
        }
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $status = ($role === 'professional') ? 'pending' : 'active';

    $insert = $pdo->prepare('INSERT INTO users (name, display_name, email, password_hash, role, status, created_at) VALUES (:name, :display_name, :email, :password_hash, :role, :status, NOW())');
    $displayName = explode(' ', $name)[0];
    $insert->execute([
        ':name' => $name,
        ':display_name' => $displayName,
        ':email' => $email,
        ':password_hash' => $passwordHash,
        ':role' => $role,
        ':status' => $status
    ]);

    sendJson(['ok' => true, 'user' => ['email' => $email, 'displayName' => $displayName, 'role' => $role]]);
} catch (PDOException $e) {
    sendJson(['error' => 'Database error', 'message' => $e->getMessage()], 500);
}
