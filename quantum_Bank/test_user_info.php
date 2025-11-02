<?php
require_once 'includes/db_connect.php';

$stmt = $conn->prepare('SELECT id, email FROM users WHERE id = 49');
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if ($user) {
    echo 'Test user ID: ' . $user['id'] . ', Email: ' . $user['email'] . PHP_EOL;
} else {
    echo 'Test user not found.' . PHP_EOL;
}
?>
