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

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    // If database doesn't exist, try to create it
    if ($conn->connect_error == "Unknown database '$db'") {
        $conn_temp = new mysqli($host, $user, $pass, '', $port);
        if ($conn_temp->connect_error) {
            $msg = "Database connection failed: " . $conn_temp->connect_error . "\n" .
                "Check quantum_Bank/.env or environment variables and ensure MySQL is running.";
            die(nl2br(htmlspecialchars($msg)));
        }
        $sql = "CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
        if ($conn_temp->query($sql) === TRUE) {
            $conn_temp->close();
            $conn = new mysqli($host, $user, $pass, $db, $port);
            if ($conn->connect_error) {
                $msg = "Database connection failed after creation: " . $conn->connect_error;
                die(nl2br(htmlspecialchars($msg)));
            }
            // Import the schema
            $sql_file = __DIR__ . '/../../quantum_bank.sql';
            if (file_exists($sql_file)) {
                $sql_content = file_get_contents($sql_file);
                if ($conn->multi_query($sql_content)) {
                    do {
                        if ($result = $conn->store_result()) {
                            $result->free();
                        }
                    } while ($conn->more_results() && $conn->next_result());
                }
            }
        } else {
            $msg = "Failed to create database: " . $conn_temp->error;
            $conn_temp->close();
            die(nl2br(htmlspecialchars($msg)));
        }
    } else {
        $msg = "Database connection failed: " . $conn->connect_error . "\n" .
            "Check quantum_Bank/.env or environment variables and ensure MySQL is running.";
        die(nl2br(htmlspecialchars($msg)));
    }
}

// Set charset to utf8mb4 for consistency
$conn->set_charset("utf8mb4");

?>