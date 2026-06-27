<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['error' => 'Method not allowed.'], 405);
}

$data = readJsonBody();
$name = trim((string)($data['name'] ?? ''));
$email = trim((string)($data['email'] ?? ''));
$tempPassword = trim((string)($data['password'] ?? ''));

if ($name === '' || $email === '' || $tempPassword === '') {
    sendJson(['error' => 'Name, email, and temporary password are required.'], 400);
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

    $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);
    $displayName = explode(' ', $name)[0];

    $insert = $pdo->prepare('INSERT INTO users (name, display_name, email, password_hash, role, status, created_at) VALUES (:name, :display_name, :email, :password_hash, :role, :status, NOW())');
    $insert->execute([
        ':name' => $name,
        ':display_name' => $displayName,
        ':email' => $email,
        ':password_hash' => $passwordHash,
        ':role' => 'patient',
        ':status' => 'active'
    ]);

    sendJson(['ok' => true, 'message' => 'Patient account created.', 'user' => ['email' => $email, 'displayName' => $displayName, 'role' => 'patient']]);
} catch (PDOException $e) {
    sendJson(['error' => 'Database error', 'message' => $e->getMessage()], 500);
}
