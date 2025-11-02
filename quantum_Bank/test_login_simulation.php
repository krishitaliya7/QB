<?php
require_once 'includes/db_connect.php';
require_once 'includes/session.php';
require_once 'includes/audit.php';

// Simulate login process
$email = 'test1761992783@example.com';
$password = 'password123';

$stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    if (isset($user['verified']) && !$user['verified']) {
        echo 'Error: Please verify your email before logging in.' . PHP_EOL;
    } else {
        echo 'Login successful! User ID: ' . $user['id'] . ', Username: ' . $user['username'] . ', Role: ' . ($user['role'] ?? 'user') . PHP_EOL;
        echo 'Session would be set and regenerated.' . PHP_EOL;
        echo 'Audit log would record login.success' . PHP_EOL;
        echo 'Redirect to dashboard.php' . PHP_EOL;
    }
} else {
    echo 'Error: Invalid email or password.' . PHP_EOL;
}
?>
