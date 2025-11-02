<?php
require_once 'includes/db_connect.php';
require_once 'includes/session.php';
require_once 'includes/audit.php';

// Test invalid login
$email = 'invalid@example.com';
$password = 'wrongpassword';

$stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    if (isset($user['verified']) && !$user['verified']) {
        echo 'Error: Please verify your email before logging in.' . PHP_EOL;
    } else {
        echo 'Login successful!' . PHP_EOL;
    }
} else {
    echo 'Error: Invalid email or password.' . PHP_EOL;
}
?>
