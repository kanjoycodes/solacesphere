<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

session_start();

$data = readJsonBody();
$email = trim((string)($data['email'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($email === '' || $password === '') {
    sendJson(['error' => 'Missing email or password.'], 400);
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT id, name, display_name, email, password_hash, role FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if (!$user) {
        sendJson(['error' => 'Invalid credentials.'], 401);
    }

    if (!password_verify($password, $user['password_hash'])) {
        sendJson(['error' => 'Invalid credentials.'], 401);
    }

    if ($user['role'] === 'professional' && ($user['status'] ?? 'active') === 'pending') {
        sendJson(['error' => 'Your account is pending verification. Please wait for admin approval.'], 403);
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];

    // Optionally record a login_sessions row
    try {
      $ins = $pdo->prepare('INSERT INTO login_sessions (user_id, session_token, user_agent, ip_address, last_seen_at, expires_at, created_at) VALUES (:uid, :token, :ua, :ip, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())');
      $token = bin2hex(random_bytes(16));
      $ins->execute([
        ':uid' => $user['id'],
        ':token' => $token,
        ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null
      ]);
    } catch (Exception $e) {
      // non-fatal
    }

    $displayName = $user['display_name'] ?: explode(' ', $user['name'])[0];

    sendJson(['ok' => true, 'user' => ['email' => $user['email'], 'displayName' => $displayName, 'role' => $user['role']]]);
} catch (PDOException $e) {
    sendJson(['error' => 'Database error', 'message' => $e->getMessage()], 500);
}
