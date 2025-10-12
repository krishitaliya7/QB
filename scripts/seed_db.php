<?php
// CLI helper to run the SQL file and seed the database (requires CLI PHP and MySQL access)
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = ''; // leave empty to create database
$sqlFile = __DIR__ . '/../quantum_bank.sql';
if (!file_exists($sqlFile)) {
    echo "SQL file not found: $sqlFile\n";
    exit(1);
}
try {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $sql = file_get_contents($sqlFile);
    if ($conn->multi_query($sql)) {
        echo "Database and schema created successfully.\n";
    } else {
        throw new Exception("Error executing SQL: " . $conn->error);
    }
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";
?>
