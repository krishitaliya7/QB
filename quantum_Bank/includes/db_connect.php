<?php
// Simple .env loader (optional): reads quantum_Bank/.env and sets env vars if not present.
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        $v = trim($v, "'\"");
        if ($k !== '' && getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
}

// Prefer environment variables for configuration.
$host = getenv('QB_DB_HOST') ?: 'localhost';
$db = getenv('QB_DB_NAME') ?: 'quantum_bank';
$user = getenv('QB_DB_USER') ?: 'root';
$pass = getenv('QB_DB_PASS') ?: '';
$port = getenv('QB_DB_PORT') ?: 3306;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ));
} catch (PDOException $e) {
    $msg = "Database connection failed: " . $e->getMessage() . "\n" .
        "Check quantum_Bank/.env or environment variables and ensure MySQL is running.";
    die(nl2br(htmlspecialchars($msg)));
}

?>