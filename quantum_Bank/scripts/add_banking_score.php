<?php
include __DIR__ . '/../includes/db_connect.php';

$sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS banking_score DECIMAL(3,1) DEFAULT 5.0";

if ($conn->query($sql) === TRUE) {
    echo "Column 'banking_score' added successfully to users table.\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

$conn->close();
?>
