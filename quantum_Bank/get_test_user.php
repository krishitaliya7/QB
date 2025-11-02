<?php
require_once 'includes/db_connect.php';
require_once 'includes/session.php';
require_once 'includes/audit.php';

// Get the test user email
$stmt = $conn->prepare('SELECT email FROM users WHERE id = 31');
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$email = $user['email'];
echo 'Test user email: ' . $email . PHP_EOL;
echo 'Test user password: password123' . PHP_EOL;
?>
