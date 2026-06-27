<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function getDbConnection(): PDO
{
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
    $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'solace_db';
    $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
    $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: 'webmaster';
    $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $opts);
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
        exit;
    }
}

function ensureDbExists(): void
{
    // noop for now; assume DB exists. Migration script will create tables.
}

?>
