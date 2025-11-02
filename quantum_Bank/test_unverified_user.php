<?php
require_once 'includes/db_connect.php';
require_once 'includes/session.php';
require_once 'includes/audit.php';

// Create an unverified test user
$username = 'unverifieduser';
$email = 'unverified' . time() . '@example.com';
$password = password_hash('password123', PASSWORD_DEFAULT);

$stmt = $conn->prepare('INSERT INTO users (username, email, password, verified) VALUES (?, ?, ?, 0)');
$stmt->bind_param('sss', $username, $email, $password);
if ($stmt->execute()) {
    $userId = $conn->insert_id;
    echo 'Unverified test user created. ID: ' . $userId . PHP_EOL;
    echo 'Email: ' . $email . PHP_EOL;
    echo 'Password: password123' . PHP_EOL;

    // Now test login with unverified user
    $stmt2 = $conn->prepare('SELECT * FROM users WHERE id = ?');
    $stmt2->bind_param('i', $userId);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify('password123', $user['password'])) {
        if (isset($user['verified']) && !$user['verified']) {
            echo 'Error: Please verify your email before logging in.' . PHP_EOL;
        } else {
            echo 'Login successful!' . PHP_EOL;
        }
    } else {
        echo 'Error: Invalid email or password.' . PHP_EOL;
    }
    $stmt2->close();
} else {
    echo 'Failed to create test user: ' . $stmt->error . PHP_EOL;
}
$stmt->close();
?>
