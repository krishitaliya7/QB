<?php
require_once 'includes/db_connect.php';
require_once 'includes/session.php';
require_once 'includes/audit.php';

// Create a test user with unique email
$username = 'testuser';
$email = 'test' . time() . '@example.com';
$password = password_hash('password123', PASSWORD_DEFAULT);

$stmt = $conn->prepare('INSERT INTO users (username, email, password, verified) VALUES (?, ?, ?, 1)');
$stmt->bind_param('sss', $username, $email, $password);
if ($stmt->execute()) {
    echo 'Test user created successfully. ID: ' . $conn->insert_id . PHP_EOL;
} else {
    echo 'Failed to create test user: ' . $stmt->error . PHP_EOL;
}
$stmt->close();
?>
