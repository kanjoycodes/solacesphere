<?php
// Run from CLI: php database/seed.php
// Creates demo users for testing all roles

declare(strict_types=1);

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../api/db.php';

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

try {
    $pdo = getDbConnection();

    $users = [
        ['Admin', 'Admin', 'admin@solacesphere.com', 'admin123', 'admin', 'active'],
        ['Joy A.', 'Joy', 'joy@example.com', 'password', 'patient', 'active'],
        ['Dr. Sarah Wilson', 'Sarah', 'sarah.wilson@example.com', 'password', 'professional', 'pending'],
        ['Dr. Ahmed Khan', 'Ahmed', 'ahmed.khan@example.com', 'password', 'professional', 'pending'],
    ];

    $insert = $pdo->prepare(
        'INSERT INTO users (name, display_name, email, password_hash, role, status, created_at, updated_at)
         VALUES (:name, :display_name, :email, :password_hash, :role, :status, NOW(), NOW())
         ON DUPLICATE KEY UPDATE id=id'
    );

    foreach ($users as $user) {
        $plainPassword = $user[3];
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $insert->execute([
            ':name' => $user[0],
            ':display_name' => $user[1],
            ':email' => $user[2],
            ':password_hash' => $hash,
            ':role' => $user[4],
            ':status' => $user[5],
        ]);
        echo "Created: {$user[0]} ({$user[2]}) as {$user[4]}\n";
    }

    echo "\nDone. Login passwords:\n";
    echo "  admin@solacesphere.com / admin123\n";
    echo "  joy@example.com / password\n";
    echo "  sarah.wilson@example.com / password (pending approval)\n";
    echo "  ahmed.khan@example.com / password (pending approval)\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
