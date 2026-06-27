<?php
// Run from CLI: php create_admin.php email password
require_once __DIR__ . '/api/db.php';
if (php_sapi_name() !== 'cli') { echo "This script must be run from the command line.\n"; exit(1); }
$argv = $_SERVER['argv'];
if (!isset($argv[1]) || !isset($argv[2])) { echo "Usage: php create_admin.php email password\n"; exit(1); }
$email = $argv[1];
$pass = $argv[2];
try {
    $pdo = getDbConnection();
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, display_name, email, password_hash, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, "admin", "active", NOW(), NOW())');
    $name = 'Admin';
    $display = 'Admin';
    $stmt->execute([$name, $display, $email, $hash]);
    echo "Admin created: $email\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
