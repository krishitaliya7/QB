<?php
// CLI helper to run the SQL file and seed the database (requires CLI PHP and MySQL access)
$dsn = 'mysql:host=127.0.0.1';
$user = 'root';
$pass = '';
$sqlFile = __DIR__ . '/../quantum_bank.sql';
if (!file_exists($sqlFile)) {
    echo "SQL file not found: $sqlFile\n";
    exit(1);
}
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);
    echo "Database and schema created successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";
?>