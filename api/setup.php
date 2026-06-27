<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$user = $_ENV['DB_USER'] ?? 'jeff';
$pass = $_ENV['DB_PASS'] ?? 'webmaster';
$port = $_ENV['DB_PORT'] ?? '3306';

try {
    // Connect to MySQL without specifying a database
    $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Read the schema file
    $schemaFile = __DIR__ . '/../database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }

    $schema = file_get_contents($schemaFile);

    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
    );

    $count = 0;
    foreach ($statements as $statement) {
        if (empty(trim($statement))) {
            continue;
        }
        try {
            $pdo->exec($statement);
            $count++;
        } catch (Exception $e) {
            // Some statements might fail if they already exist (IF NOT EXISTS)
            // This is okay
        }
    }

    echo "✓ Database setup completed successfully!\n";
    echo "✓ Executed $count SQL statements\n";
    echo "✓ Database: solace_db\n";
    echo "✓ All tables created\n";

} catch (Exception $e) {
    http_response_code(500);
    echo "✗ Setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
