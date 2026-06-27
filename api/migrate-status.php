<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

try {
    $pdo = getDbConnection();

    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('pending','active','deactivated') NOT NULL DEFAULT 'active' AFTER role");
        echo "Column 'status' added to users table.\n";
    } else {
        echo "Column 'status' already exists.\n";
    }

    $stmt = $pdo->exec("UPDATE users SET status = 'active' WHERE role = 'professional' AND status = 'active'");
    echo "Existing professionals set to active.\n";

    echo "Migration complete.\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
